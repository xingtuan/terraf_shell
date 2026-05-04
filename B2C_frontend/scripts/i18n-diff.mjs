#!/usr/bin/env node
/**
 * i18n-diff.mjs
 *
 * Compares messages/en.json, messages/ko.json, messages/zh.json and reports:
 *   1. Keys present in one file but missing from others
 *   2. Keys whose value is an empty string in any file
 *   3. Keys in ko/zh whose value is still in English (likely untranslated)
 *
 * Exit code 0 = no differences found
 * Exit code 1 = differences found (use in CI)
 *
 * Usage:
 *   node scripts/i18n-diff.mjs
 */

import { readFileSync } from "node:fs"
import { resolve, dirname } from "node:path"
import { fileURLToPath } from "node:url"

const __dirname = dirname(fileURLToPath(import.meta.url))
const root = resolve(__dirname, "..")

const LOCALES = ["en", "ko", "zh"]
const FILES = Object.fromEntries(
  LOCALES.map((locale) => [
    locale,
    JSON.parse(readFileSync(resolve(root, `messages/${locale}.json`), "utf8")),
  ]),
)

// Known English-only values that are acceptable in all locales
const ALLOWED_ENGLISH_VALUES = new Set([
  "OXP",
  "EN",
  "KO",
  "中文",
  "B2B",
  "B2C",
  "GoFundMe",
  "Kickstarter",
  "OX-W",
  "OX-H",
  "OX-B",
  "OX-S",
  "OX-M",
  "Terrafin",
  "Terrafactory",
  "Terraforming",
  "your_username",                 // technical identifier, intentionally same
  "Contact email pending",         // pending content, not a translation gap
  "name@company.com",              // example value — flagged separately in audit
  "SHELL · SCIENCE · SOURCE CODE", // brand tagline kept in English
  "© 2026 OXP. All rights reserved.", // legal line, intentionally same
  "coming_soon",                   // status enum
  "available",                     // status enum
  "store",                         // URL slug
  "community",                     // URL slug
  "b2b",                           // URL slug
])

// Simple heuristic: a string is "likely English" if it contains
// at least 3 ASCII letters and no CJK or Hangul characters.
const CJK_OR_HANGUL = /[぀-鿿가-퟿]/
const ASCII_WORD = /[a-zA-Z]{3,}/

function isLikelyEnglish(value) {
  if (typeof value !== "string" || value.length === 0) return false
  if (CJK_OR_HANGUL.test(value)) return false
  return ASCII_WORD.test(value)
}

/**
 * Flatten a nested object into dot-notation keys.
 * Arrays are flattened with bracket notation: key[0], key[1], ...
 */
function flatten(obj, prefix = "") {
  const result = {}
  for (const [key, value] of Object.entries(obj)) {
    const fullKey = prefix ? `${prefix}.${key}` : key
    if (Array.isArray(value)) {
      value.forEach((item, index) => {
        if (typeof item === "object" && item !== null) {
          Object.assign(result, flatten(item, `${fullKey}[${index}]`))
        } else {
          result[`${fullKey}[${index}]`] = item
        }
      })
    } else if (typeof value === "object" && value !== null) {
      Object.assign(result, flatten(value, fullKey))
    } else {
      result[fullKey] = value
    }
  }
  return result
}

const flat = Object.fromEntries(
  LOCALES.map((locale) => [locale, flatten(FILES[locale])]),
)

const allKeys = new Set([
  ...Object.keys(flat.en),
  ...Object.keys(flat.ko),
  ...Object.keys(flat.zh),
])

let hasError = false

const missingKeys = []
const emptyValues = []
const untranslated = []

for (const key of allKeys) {
  const presentIn = LOCALES.filter((locale) => key in flat[locale])
  const missingFrom = LOCALES.filter((locale) => !(key in flat[locale]))

  if (missingFrom.length > 0) {
    missingKeys.push({ key, presentIn, missingFrom })
    hasError = true
    continue
  }

  for (const locale of LOCALES) {
    const value = flat[locale][key]
    if (value === "" || value === null || value === undefined) {
      emptyValues.push({ key, locale, value })
      hasError = true
    }
  }

  // Check if ko or zh still holds an English value
  for (const locale of ["ko", "zh"]) {
    const value = flat[locale][key]
    if (
      typeof value === "string" &&
      !ALLOWED_ENGLISH_VALUES.has(value) &&
      isLikelyEnglish(value) &&
      value === flat.en[key]  // same as English → not translated
    ) {
      untranslated.push({ key, locale, value })
      // Don't mark as error by default — log as warning
    }
  }
}

// ── Output ──────────────────────────────────────────────────────────────────

console.log("=== i18n Diff Report ===\n")

if (missingKeys.length === 0 && emptyValues.length === 0 && untranslated.length === 0) {
  console.log("✅  No differences found. All three locale files are in sync.\n")
} else {
  if (missingKeys.length > 0) {
    console.log(`❌  Missing keys (${missingKeys.length}):\n`)
    for (const { key, missingFrom } of missingKeys) {
      console.log(`  ${key}`)
      console.log(`    missing from: ${missingFrom.join(", ")}`)
    }
    console.log()
  }

  if (emptyValues.length > 0) {
    console.log(`❌  Empty values (${emptyValues.length}):\n`)
    for (const { key, locale } of emptyValues) {
      console.log(`  [${locale}] ${key}`)
    }
    console.log()
  }

  if (untranslated.length > 0) {
    console.log(`⚠️   Possibly untranslated (same as English) (${untranslated.length}):\n`)
    for (const { key, locale, value } of untranslated) {
      const truncated = value.length > 60 ? `${value.slice(0, 60)}…` : value
      console.log(`  [${locale}] ${key}: "${truncated}"`)
    }
    console.log()
  }
}

console.log(
  `Summary: ${allKeys.size} total keys · ` +
    `${missingKeys.length} missing · ` +
    `${emptyValues.length} empty · ` +
    `${untranslated.length} possibly untranslated`,
)

process.exit(hasError ? 1 : 0)
