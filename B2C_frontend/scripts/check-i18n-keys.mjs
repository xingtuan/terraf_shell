import { readFileSync } from "node:fs"
import { resolve, dirname } from "node:path"
import { fileURLToPath } from "node:url"

const __dirname = dirname(fileURLToPath(import.meta.url))
const root = resolve(__dirname, "..")

function loadJson(locale) {
  const filePath = resolve(root, "messages", `${locale}.json`)
  return JSON.parse(readFileSync(filePath, "utf8"))
}

function collectKeys(obj, prefix = "") {
  const keys = []
  for (const [key, value] of Object.entries(obj)) {
    const fullKey = prefix ? `${prefix}.${key}` : key
    if (value !== null && typeof value === "object" && !Array.isArray(value)) {
      keys.push(...collectKeys(value, fullKey))
    } else {
      keys.push(fullKey)
    }
  }
  return keys
}

const en = loadJson("en")
const zh = loadJson("zh")
const ko = loadJson("ko")

const enKeys = new Set(collectKeys(en))
const zhKeys = new Set(collectKeys(zh))
const koKeys = new Set(collectKeys(ko))

let failed = false

for (const key of enKeys) {
  if (!zhKeys.has(key)) {
    console.error(`[zh] Missing key: ${key}`)
    failed = true
  }
  if (!koKeys.has(key)) {
    console.error(`[ko] Missing key: ${key}`)
    failed = true
  }
}

if (failed) {
  console.error("\nTranslation key check failed. Add the missing keys above to zh.json and ko.json.")
  process.exit(1)
} else {
  console.log("Translation key check passed. All en keys are present in zh and ko.")
}
