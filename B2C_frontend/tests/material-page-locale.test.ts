import { describe, it } from "node:test"
import assert from "node:assert/strict"
import { existsSync, readFileSync } from "node:fs"
import { join } from "node:path"

import { hasPublishedCmsSection } from "../lib/cms-section-visibility.ts"
import { payloadArray } from "../lib/payload-array.ts"

type MockSection = Parameters<typeof hasPublishedCmsSection>[0]

function readMaterialPageSource() {
  const candidatePaths = [
    join(process.cwd(), "app", "[locale]", "material", "page.tsx"),
    join(process.cwd(), "B2C_frontend", "app", "[locale]", "material", "page.tsx"),
  ]
  const pagePath = candidatePaths.find((path) => existsSync(path))

  assert.ok(pagePath, "Material page source file was not found")

  return readFileSync(pagePath, "utf8")
}

function findMockSection(sections: MockSection[], key: string) {
  return sections.find((section) => section?.key === key) ?? null
}

function renderCmsBlock(
  componentName: string,
  section: MockSection,
  fallbackText: string,
) {
  if (!hasPublishedCmsSection(section)) {
    return ""
  }

  return `${componentName}\n${section.title ?? fallbackText}`
}

describe("MaterialPage locale handling", () => {
  it("loads material content only from material Page Sections", () => {
    const source = readMaterialPageSource()

    assert.match(
      source,
      /getPageSections\(\{ baseUrl: apiBaseUrl, locale, page: "material" \}\)/,
    )
    assert.doesNotMatch(source, /getMaterialInfo/)
    assert.doesNotMatch(source, /getMaterialSpecs/)
    assert.doesNotMatch(source, /materialInfoToDetail/)
    assert.doesNotMatch(source, /materialInfoToSpecs/)
  })
})

describe("CMS section visibility guards", () => {
  it("renders homepage audience_paths only when the public response includes it", () => {
    const fallbackText = "Default audience paths title"
    const sections: MockSection[] = [
      { id: 1, key: "audience_paths", title: "CMS audience paths" },
    ]

    const rendered = renderCmsBlock(
      "AudiencePathsSection",
      findMockSection(sections, "audience_paths"),
      fallbackText,
    )

    assert.match(rendered, /AudiencePathsSection/)
    assert.match(rendered, /CMS audience paths/)

    const hidden = renderCmsBlock(
      "AudiencePathsSection",
      findMockSection([], "audience_paths"),
      fallbackText,
    )

    assert.equal(hidden, "")
    assert.doesNotMatch(hidden, new RegExp(fallbackText))
  })

  it("does not render missing B2B process fallback content", () => {
    const rendered = renderCmsBlock(
      "B2BProcessSection",
      findMockSection([], "process"),
      "Default B2B process title",
    )

    assert.equal(rendered, "")
    assert.doesNotMatch(rendered, /Default B2B process title/)
  })

  it("does not render missing contact details fallback content", () => {
    const rendered = renderCmsBlock(
      "ContactDetailsSection",
      findMockSection([], "details"),
      "Default contact details title",
    )

    assert.equal(rendered, "")
    assert.doesNotMatch(rendered, /Default contact details title/)
  })

  it("does not render missing store product grid fallback content", () => {
    const rendered = renderCmsBlock(
      "ProductGridSection",
      findMockSection([], "product_grid"),
      "Default catalogue title",
    )

    assert.equal(rendered, "")
    assert.doesNotMatch(rendered, /Default catalogue title/)
  })
})

describe("CMS payload helpers", () => {
  it("preserves explicit payload item order when sort_order or order is present", () => {
    assert.deepEqual(
      payloadArray(
        {
          payload: {
            items: [
              { title: "Second", sort_order: 2 },
              { title: "First", sort_order: 1 },
            ],
          },
        },
        "items",
      ),
      [
        { title: "First", sort_order: 1 },
        { title: "Second", sort_order: 2 },
      ],
    )

    assert.deepEqual(
      payloadArray(
        {
          payload: {
            cards: {
              second: { title: "Second", order: 20 },
              first: { title: "First", order: 10 },
            },
          },
        },
        "cards",
      ),
      [
        { title: "First", order: 10 },
        { title: "Second", order: 20 },
      ],
    )
  })

  it("wires material facts, contact details, and lead form copy to CMS payload fields", () => {
    const pageContentSource = readFileSync(
      join(process.cwd(), "lib", "page-content.ts"),
      "utf8",
    )
    const formSource = readFileSync(
      join(process.cwd(), "components", "sections", "b2b-inquiry-form.tsx"),
      "utf8",
    )
    const contactSource = readFileSync(
      join(process.cwd(), "components", "sections", "contact-details.tsx"),
      "utf8",
    )

    assert.match(pageContentSource, /export function buildMaterialFactSpecs/)
    assert.match(pageContentSource, /payloadArray\(section, "metrics"\)/)
    assert.match(pageContentSource, /payloadArray\(section, "items"\)/)
    assert.match(pageContentSource, /payloadArray\(section, "interest_options"\)/)
    assert.match(pageContentSource, /payload\.panel_copy/)
    assert.match(pageContentSource, /localizedPayloadRecord\(\s*payload,\s*"fields"/)
    assert.match(pageContentSource, /localizedPayloadRecord\(\s*payload,\s*"placeholders"/)
    assert.match(pageContentSource, /localizedPayloadRecord\(\s*payload,\s*"validation"/)
    assert.match(pageContentSource, /icon: nonEmptyString\(rawItem\.icon\)/)

    assert.match(formSource, /content\.interestOptionList/)
    assert.match(formSource, /content\.panelCopy\[values\.interestType\]/)
    assert.doesNotMatch(formSource, /getPanelCopy/)
    assert.doesNotMatch(formSource, /Evaluation kits, material notes/)
    assert.doesNotMatch(formSource, /Pellet supply, raw material buying/)

    assert.match(contactSource, /card\.icon/)
    assert.match(contactSource, /contactIconMap/)
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
      "buildHeroContent",
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
    for (const sectionKey of [
      "heroSection",
      "audiencePathsSection",
      "businessPillarsSection",
      "whyItMattersSection",
      "materialStorySection",
      "openSourceLegacySection",
      "applicationsSection",
      "scienceSection",
      "collaborationSection",
      "credibilitySection",
      "trustSection",
      "articlesSection",
      "pilotProjectsSection",
      "finalCtaSection",
    ]) {
      assert.match(source, new RegExp(`hasPublishedCmsSection\\(${sectionKey}\\)`))
    }
    assert.match(source, /audiencePathsContent \? \(/)
    assert.doesNotMatch(source, /<AudiencePathsSection[\s\S]*buildAudiencePathsContent/)
    assert.doesNotMatch(source, /cardHrefs=\{\[/)
  })

  it("material page fetches material sections and hides missing CMS blocks", () => {
    const source = readMaterialPageSource()

    assert.match(source, /getPageSections\(\{ baseUrl: apiBaseUrl, locale, page: "material" \}\)/)
    assert.match(source, /materialRenderers/)
    assert.match(source, /orderedSections\(pageSections\)\.map/)
    for (const builder of [
      "buildMaterialFamilyContent",
      "buildMaterialProofPointsContent",
      "buildTechnicalDownloadsContent",
      "buildMaterialComparisonContent",
      "buildCertificationsContent",
      "buildMaterialFactSpecs",
      "buildFinalCtaContent",
    ]) {
      assert.match(source, new RegExp(`${builder}\\(`))
    }
    for (const rendererKey of [
      "intro",
      "material_family",
      "why_it_matters",
      "material_story",
      "applications",
      "material_facts",
      "proof_points",
      "certifications",
      "technical_downloads",
      "comparison",
      "credibility",
      "trust_and_credibility",
      "pilot_projects",
      "collaboration",
      "final_cta",
    ]) {
      assert.match(source, new RegExp(`${rendererKey}: \\(section\\)`))
    }
    assert.doesNotMatch(source, /getMaterialInfo|getMaterialSpecs|materialInfoTo/)
    assert.doesNotMatch(source, /open_source_legacy/)
  })

  it("contact page fetches contact sections and only builds existing CMS blocks", () => {
    const source = readFileSync(
      join(process.cwd(), "app", "[locale]", "contact", "page.tsx"),
      "utf8",
    )

    assert.match(source, /getPageSections\(\{ baseUrl: apiBaseUrl, locale, page: "contact" \}\)/)
    assert.match(source, /buildPageIntroContent\(/)
    assert.match(source, /buildContactDetailsContent\(/)
    assert.match(source, /buildB2BFormContent\(/)
    assert.match(source, /buildFooterContent\(/)
    assert.match(source, /contactRenderers/)
    assert.match(source, /orderedSections\(contactSections\)\.map/)
    assert.match(source, /inquiry_form: formRenderer/)
    assert.match(source, /id=\{content\.formAnchorId \?\? "inquiry"\}/)
    assert.doesNotMatch(source, /buildPageIntroContent\(\s*messages\.contactPage\.intro,\s*null/)
  })

  it("b2b page fetches b2b sections and applies CMS visibility guards", () => {
    const source = readFileSync(
      join(process.cwd(), "app", "[locale]", "b2b", "page.tsx"),
      "utf8",
    )

    assert.match(source, /getPageSections\(\{ baseUrl: apiBaseUrl, locale, page: "b2b" \}\)/)
    assert.match(source, /b2bRenderers/)
    assert.match(source, /orderedSections\(b2bSections\)\.map/)
    for (const builder of [
      "buildPageIntroContent",
      "buildCollaborationContent",
      "buildB2BProcessContent",
      "buildB2BCtaStripContent",
      "buildB2BApplicationsContent",
      "buildMaterialFactSpecs",
      "buildB2BFormContent",
      "buildB2BAfterSubmitContent",
    ]) {
      assert.match(source, new RegExp(`${builder}\\(`))
    }
    assert.doesNotMatch(source, /getFeaturedMaterial|getMaterialSpecs/)
    assert.doesNotMatch(source, /findHomeSection/)
    assert.doesNotMatch(source, /cardHrefs=\{\[/)
  })

  it("store page fetches store sections and applies CMS builders with visibility guards", () => {
    const source = readFileSync(
      join(process.cwd(), "app", "[locale]", "store", "page.tsx"),
      "utf8",
    )

    assert.match(source, /getPageSections\(\{[\s\S]*baseUrl: apiBaseUrl,[\s\S]*locale,[\s\S]*page: "store",?[\s\S]*\}\)/)
    for (const builder of [
      "buildPageIntroContent",
      "buildStoreGridContent",
      "buildApplicationsContent",
      "buildCredibilityContent",
      "buildStoreFaqContent",
      "buildFinalCtaContent",
    ]) {
      assert.match(source, new RegExp(`${builder}\\(`))
    }
    for (const sectionKey of [
      "introSection",
      "productGridSection",
      "credibilitySection",
      "faqSection",
      "applicationsSection",
      "finalCtaSection",
    ]) {
      assert.match(source, new RegExp(`hasPublishedCmsSection\\(${sectionKey}\\)`))
    }
    assert.doesNotMatch(source, /shouldUseCmsVisibility/)
    assert.doesNotMatch(source, /shouldRender\(/)
  })

  it("community page fetches community sections and applies CMS builders with visibility guards", () => {
    const source = readFileSync(
      join(process.cwd(), "app", "[locale]", "community", "page.tsx"),
      "utf8",
    )

    assert.match(source, /getPageSections\(\{[\s\S]*baseUrl: apiBaseUrl,[\s\S]*locale,[\s\S]*page: "community",?[\s\S]*\}\)/)
    for (const builder of [
      "buildPageIntroContent",
      "buildCommunityIdeasContent",
      "buildFinalCtaContent",
    ]) {
      assert.match(source, new RegExp(`${builder}\\(`))
    }
    for (const sectionKey of ["introSection", "openConceptsSection", "finalCtaSection"]) {
      assert.match(source, new RegExp(`hasPublishedCmsSection\\(${sectionKey}\\)`))
    }
    assert.doesNotMatch(source, /shouldUseCmsVisibility/)
    assert.doesNotMatch(source, /shouldRender\(/)
  })

  it("articles page fetches article sections and avoids hardcoded editorial copy", () => {
    const source = readFileSync(
      join(process.cwd(), "app", "[locale]", "articles", "page.tsx"),
      "utf8",
    )

    assert.match(source, /getPageSections\(\{[\s\S]*baseUrl: apiBaseUrl,[\s\S]*locale,[\s\S]*page: "articles",?[\s\S]*\}\)/)
    assert.match(source, /buildPageIntroContent\(/)
    assert.match(source, /buildFinalCtaContent\(/)
    assert.match(source, /ArticleFeedSection/)
    assert.match(source, /hasPublishedCmsSection\(introSection\)/)
    assert.match(source, /hasPublishedCmsSection\(articleFeedSection\)/)
    assert.match(source, /hasPublishedCmsSection\(finalCtaSection\)/)
    assert.doesNotMatch(source, /shouldUseCmsVisibility/)
    assert.doesNotMatch(source, /shouldRender\(/)
    assert.doesNotMatch(source, /Articles, lab notes, and material updates/)
    assert.doesNotMatch(source, /Backend-driven editorial content/)
  })

  it("footer renders configured social and legal link groups", () => {
    const source = readFileSync(join(process.cwd(), "components", "footer.tsx"), "utf8")

    assert.match(source, /footer\.socialLinks/)
    assert.match(source, /footer\.legalLinks/)
    assert.match(source, /legalLinks\.map/)
    assert.match(source, /socialLinks\.map/)
  })
})
