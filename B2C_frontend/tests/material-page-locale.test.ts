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

describe("CMS page sections wiring", () => {
  it("home page fetches home sections and builds visible sections CMS-first", () => {
    const source = readFileSync(
      join(process.cwd(), "app", "[locale]", "page.tsx"),
      "utf8",
    )

    assert.match(source, /getHomeSections\(\{ baseUrl: apiBaseUrl, locale, page: "home" \}\)/)
    for (const builder of [
      "buildAudiencePathsContent",
      "buildBusinessPillarsContent",
      "buildWhyItMattersContent",
      "buildOpenSourceLegacyContent",
      "buildCollaborationContent",
      "buildTrustAndCredibilityContent",
      "buildFinalCtaContent",
    ]) {
      assert.match(source, new RegExp(`${builder}\\(`))
    }
    assert.doesNotMatch(source, /cardHrefs=\{\[/)
  })

  it("material page fetches material sections and applies marketing-section overrides", () => {
    const source = readMaterialPageSource()

    assert.match(source, /getPageSections\(\{ \.\.\.materialRequestOptions, page: "material" \}\)/)
    for (const builder of [
      "buildMaterialFamilyContent",
      "buildMaterialProofPointsContent",
      "buildTechnicalDownloadsContent",
      "buildMaterialComparisonContent",
      "buildCertificationsContent",
      "buildFinalCtaContent",
    ]) {
      assert.match(source, new RegExp(`${builder}\\(`))
    }
  })
})
