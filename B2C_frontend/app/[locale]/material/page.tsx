import { Fragment, type ReactNode } from "react"

import { ApplicationsSection } from "@/components/sections/applications"
import { CollaborationSection } from "@/components/sections/collaboration"
import { CertificationsAtAGlance } from "@/components/sections/certifications-at-a-glance"
import { CredibilitySection } from "@/components/sections/credibility"
import { FinalCtaSection } from "@/components/sections/final-cta"
import {
  MaterialComparisonSection,
  MaterialProofPointsSection,
  TechnicalDownloadsSection,
} from "@/components/sections/material-proof-sections"
import { MaterialFactsSection } from "@/components/sections/material-facts"
import { MaterialFamilySection } from "@/components/sections/material-family"
import { MaterialStorySection } from "@/components/sections/material-story"
import {
  PilotProjectsSection,
  TrustAndCredibilitySection,
} from "@/components/sections/trust-and-b2b-sections"
import { WhyItMattersSection } from "@/components/sections/why-it-matters"
import { PageIntro } from "@/components/page-intro"
import { getPageSections } from "@/lib/api/page-sections"
import { getServerApiBaseUrl } from "@/lib/api/server-base-url"
import { getLocalizedHref, getMessages } from "@/lib/i18n"
import {
  buildApplicationsContent,
  buildCertificationsContent,
  buildCollaborationContent,
  buildCredibilityContent,
  buildFinalCtaContent,
  buildMaterialComparisonContent,
  buildMaterialFactSpecs,
  buildMaterialFactsContent,
  buildMaterialFamilyContent,
  buildMaterialProofPointsContent,
  buildMaterialStoryContent,
  buildPageIntroContent,
  buildPilotProjectsContent,
  buildTechnicalDownloads,
  buildTechnicalDownloadsContent,
  buildTrustAndCredibilityContent,
  buildWhyItMattersContent,
  resolveCmsHref,
} from "@/lib/page-content"
import { resolveLocale } from "@/lib/resolve-locale"
import type { HomeSection } from "@/lib/types"

type MaterialPageProps = {
  params: Promise<{ locale: string }>
}

function orderedSections(sections: HomeSection[]) {
  return [...sections].sort((a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0))
}

export default async function MaterialPage({ params }: MaterialPageProps) {
  const locale = await resolveLocale(params)
  const apiBaseUrl = await getServerApiBaseUrl()
  const messages = getMessages(locale)

  let pageSections: HomeSection[] = []

  try {
    pageSections = await getPageSections({ baseUrl: apiBaseUrl, locale, page: "material" })
  } catch {
    pageSections = []
  }

  const materialRenderers: Record<string, (section: HomeSection) => ReactNode> = {
    intro: (section) => {
      const intro = buildPageIntroContent(
        messages.materialPage.intro,
        section,
        locale,
        `${getLocalizedHref(locale, "b2b")}?leadType=sample_request#inquiry`,
        getLocalizedHref(locale, "contact"),
      )

      return (
        <PageIntro
          eyebrow={intro.eyebrow}
          title={intro.title}
          description={intro.description}
          primaryAction={{
            label: intro.primaryCta,
            href: intro.primaryHref ?? `${getLocalizedHref(locale, "b2b")}?leadType=sample_request#inquiry`,
          }}
          secondaryAction={{
            label: intro.secondaryCta,
            href: intro.secondaryHref ?? getLocalizedHref(locale, "contact"),
          }}
        />
      )
    },
    material_family: (section) => (
      <MaterialFamilySection
        locale={locale}
        content={buildMaterialFamilyContent(messages.home.materialFamily, section, locale)}
      />
    ),
    why_it_matters: (section) => (
      <WhyItMattersSection
        content={buildWhyItMattersContent(
          messages.home.whyItMatters,
          section,
          locale,
          null,
        )}
      />
    ),
    material_story: (section) => (
      <MaterialStorySection
        content={buildMaterialStoryContent(
          messages.home.materialStory,
          null,
          locale,
          section,
        )}
      />
    ),
    applications: (section) => (
      <ApplicationsSection
        content={buildApplicationsContent(
          messages.home.applications,
          null,
          locale,
          section,
        )}
      />
    ),
    material_facts: (section) => (
      <MaterialFactsSection
        locale={locale}
        content={buildMaterialFactsContent(
          messages.home.materialFacts,
          null,
          section,
          locale,
        )}
        specs={buildMaterialFactSpecs(section, locale)}
        sheetHref={resolveCmsHref(
          locale,
          typeof section.payload?.sheet_cta_url === "string"
            ? section.payload.sheet_cta_url
            : section.cta_url,
          `${getLocalizedHref(locale, "b2b")}?leadType=sample_request#inquiry`,
        )}
      />
    ),
    proof_points: (section) => (
      <MaterialProofPointsSection
        content={buildMaterialProofPointsContent(
          messages.materialProof.proofPoints,
          section,
          locale,
        )}
      />
    ),
    certifications: (section) => {
      const content = buildCertificationsContent(
        messages.certificationsAtAGlance,
        section,
        locale,
      )

      return (
        <CertificationsAtAGlance
          certifications={content.certifications ?? []}
          eyebrow={content.eyebrow}
          title={content.title}
          description={content.description}
          variant="material"
          verifiedLabel={content.verifiedLabel}
          emptyMessage={content.emptyMessage}
          statusLabels={content.statusLabels}
          issuerLabel={content.issuerLabel}
          testedAtLabel={content.testedAtLabel}
          downloadLabel={content.downloadLabel}
        />
      )
    },
    technical_downloads: (section) => (
      <TechnicalDownloadsSection
        content={buildTechnicalDownloadsContent(
          messages.materialProof.technicalDownloads,
          section,
          locale,
        )}
        downloads={buildTechnicalDownloads(section, locale)}
      />
    ),
    comparison: (section) => (
      <MaterialComparisonSection
        content={buildMaterialComparisonContent(
          messages.materialProof.comparison,
          section,
          locale,
        )}
      />
    ),
    credibility: (section) => (
      <CredibilitySection
        content={buildCredibilityContent(
          messages.home.credibility,
          null,
          locale,
          section,
        )}
      />
    ),
    trust_and_credibility: (section) => (
      <TrustAndCredibilitySection
        content={buildTrustAndCredibilityContent(
          messages.trustAndCredibility,
          section,
          locale,
        )}
      />
    ),
    pilot_projects: (section) => (
      <PilotProjectsSection
        content={buildPilotProjectsContent(messages.pilotProjects, section, locale)}
      />
    ),
    collaboration: (section) => {
      const content = buildCollaborationContent(
        messages.home.collaboration,
        section,
        locale,
      )

      return (
        <CollaborationSection
          locale={locale}
          content={content}
          cardHrefs={content.cardHrefs}
        />
      )
    },
    final_cta: (section) => (
      <FinalCtaSection
        locale={locale}
        content={buildFinalCtaContent(messages.home.finalCta, section, locale)}
      />
    ),
  }

  return (
    <>
      {orderedSections(pageSections).map((section) => {
        const renderSection = materialRenderers[section.key]

        return renderSection ? (
          <Fragment key={`${section.page_key}-${section.key}`}>
            {renderSection(section)}
          </Fragment>
        ) : null
      })}
    </>
  )
}
