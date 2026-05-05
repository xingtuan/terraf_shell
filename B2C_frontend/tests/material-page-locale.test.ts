import { describe, it } from "node:test"
import assert from "node:assert/strict"
import { existsSync, readFileSync } from "node:fs"
import { join } from "node:path"

function readMaterialPageSource() {
  const candidatePaths = [
    join(process.cwd(), "app", "[locale]", "material", "page.tsx"),
    join(process.cwd(), "B2C_frontend", "app", "[locale]", "material", "page.tsx"),
  ]
  const pagePath = candidatePaths.find((path) => existsSync(path))

  assert.ok(pagePath, "Material page source file was not found")

  return readFileSync(pagePath, "utf8")
}

describe("MaterialPage locale handling", () => {
  it("passes the route locale into material API request options", () => {
    const source = readMaterialPageSource()

    assert.match(
      source,
      /const materialRequestOptions = {\s*baseUrl: apiBaseUrl,\s*locale,\s*} as const/,
    )
    assert.match(source, /getMaterialInfo\(materialRequestOptions\)/)
    assert.match(source, /getMaterialSpecs\(locale,\s*materialRequestOptions\)/)
    assert.doesNotMatch(
      source,
      /getMaterialInfo\(\{\s*baseUrl: apiBaseUrl\s*\}\)/,
    )
    assert.doesNotMatch(
      source,
      /getMaterialSpecs\(locale,\s*\{\s*baseUrl: apiBaseUrl\s*\}\)/,
    )
  })
})
