import { existsSync, readdirSync, readFileSync, statSync } from "node:fs"
import { resolve, dirname, relative } from "node:path"
import { fileURLToPath } from "node:url"

const __dirname = dirname(fileURLToPath(import.meta.url))
const root = resolve(__dirname, "..")
const scanRoot = resolve(root, process.argv[2] ?? ".next/server/app")

const forbiddenEnglish = [
  "Weight: Lightweight",
  "Strength: High compressive stability",
  "Flexibility: Process-dependent",
  "Designed for premium interior objects, hospitality programs, and future collaborative product development.",
  "A premium, science-backed material platform built from recovered shell.",
  "Weight",
  "Lightweight",
  "Suitable for portable premium objects and interior accessory systems.",
  "From Shell Waste to Premium Feedstock",
  "Material Science for Commercial Credibility",
]

const renderedExtensions = new Set([".html", ".rsc", ".txt"])
const targetRoutePattern =
  /^zh(?:\.(?:html|rsc|txt)$|\.segments[\\/]|[\\/]material(?:[\\/.]|$))/

function extensionOf(path) {
  const match = path.match(/(\.[^.\\/]+)$/)

  return match?.[1] ?? ""
}

function walkFiles(dir) {
  const entries = readdirSync(dir)
  const files = []

  for (const entry of entries) {
    const fullPath = resolve(dir, entry)
    const stats = statSync(fullPath)

    if (stats.isDirectory()) {
      files.push(...walkFiles(fullPath))
    } else if (renderedExtensions.has(extensionOf(fullPath))) {
      files.push(fullPath)
    }
  }

  return files
}

if (!existsSync(scanRoot)) {
  console.warn(
    `Rendered i18n check skipped: ${scanRoot} does not exist. Run it after a Next build or pass a rendered-output directory.`,
  )
  process.exit(0)
}

const files = walkFiles(scanRoot).filter((file) =>
  targetRoutePattern.test(relative(scanRoot, file)),
)

if (files.length === 0) {
  console.warn(
    `Rendered i18n check skipped: no zh route artifacts found under ${scanRoot}.`,
  )
  process.exit(0)
}

const failures = []

for (const file of files) {
  const content = readFileSync(file, "utf8")

  for (const text of forbiddenEnglish) {
    if (content.includes(text)) {
      failures.push({ file, text })
    }
  }
}

if (failures.length > 0) {
  console.error("Rendered i18n check failed. Forbidden English text was found:")

  for (const failure of failures) {
    console.error(`  ${failure.file}: ${failure.text}`)
  }

  process.exit(1)
}

console.log(`Rendered i18n check passed across ${files.length} zh artifact(s).`)
