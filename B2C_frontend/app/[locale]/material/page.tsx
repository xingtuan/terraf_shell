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
import { OpenSourceLegacySection } from "@/components/sections/open-source-legacy"
import {
  PilotProjectsSection,
  TrustAndCredibilitySection,
} from "@/components/sections/trust-and-b2b-sections"
import { WhyItMattersSection } from "@/components/sections/why-it-matters"
import { PageIntro } from "@/components/page-intro"
import {
  getMaterialInfo,
  getMaterialSpecs,
  materialInfoToSpecs,
} from "@/lib/api/materials"
import { getServerApiBaseUrl } from "@/lib/api/server-base-url"
import { getLocalizedHref, getMessages } from "@/lib/i18n"
import { resolveLocale } from "@/lib/resolve-locale"
import type { MaterialInfo } from "@/lib/types"

type MaterialPageProps = {
  params: Promise<{ locale: string }>
}

function cleanCertificationText(value: string | null | undefined) {
  return typeof value === "string" && value.trim().length > 0
    ? value.trim()
    : null
}

export default async function MaterialPage({ params }: MaterialPageProps) {
  const locale = await resolveLocale(params)
  const apiBaseUrl = await getServerApiBaseUrl()
  const messages = getMessages(locale)
  const certificationMessages = messages.certificationsAtAGlance
  const materialRequestOptions = {
    baseUrl: apiBaseUrl,
    locale,
  } as const

  let materialInfo: MaterialInfo | null = null

  try {
    const response = await getMaterialInfo(materialRequestOptions)
    materialInfo = response.data
  } catch {
    materialInfo = null
  }

  const intro = messages.materialPage.intro
  const specs = materialInfo
    ? materialInfoToSpecs(materialInfo)
    : await getMaterialSpecs(locale, materialRequestOptions)
  const certificationSummaries = (materialInfo?.certifications ?? [])
    .map((certification) => {
      const title = cleanCertificationText(
        certification.label ?? certification.name ?? certification.key,
      )
      const measuredResult = [certification.result, certification.unit]
        .filter(Boolean)
        .join(" ")
      const result = cleanCertificationText(
        certification.value ||
          measuredResult ||
          certification.description ||
          certification.status,
      )

      return title && result
        ? {
            title,
            result,
          }
        : null
    })
    .filter((summary): summary is { title: string; result: string } =>
      Boolean(summary),
    )
  const whyItMattersContent = materialInfo
    ? {
        ...messages.home.whyItMatters,
        title: materialInfo.tagline,
        cards: materialInfo.properties.slice(0, 3).map((property) => ({
          title: property.label,
          description: `${property.value}. ${property.vs}`,
        })),
        stats: [
          materialInfo.origin,
          materialInfo.models
            .map((model) => `${model.name} (${model.finish}): ${model.description}`)
            .join(" "),
          materialInfo.colors
            .map((color) => `${color.name} (${color.temp}): ${color.description}`)
            .join(" "),
        ],
      }
    : messages.home.whyItMatters
  const storyContent = materialInfo
    ? {
        ...messages.home.materialStory,
        title: materialInfo.tagline,
        steps: materialInfo.process_steps.map((step) => ({
          number: String(step.step).padStart(2, "0"),
          title: step.title,
          description: step.body,
        })),
      }
    : messages.home.materialStory
  const materialFactsContent = materialInfo
    ? {
        ...messages.home.materialFacts,
        title: materialInfo.tagline,
        sheetTitle: `${materialInfo.name} ${messages.materialPage.sheetTitleSuffix}`,
        sheetDescription: certificationSummaries
          .map((certification) => `${certification.title}: ${certification.result}`)
          .join(" / "),
        infoCards: [
          {
            label: messages.materialPage.modelsLabel,
            value: materialInfo.models.map((model) => model.name).join(", "),
          },
          {
            label: messages.materialPage.colorsLabel,
            value: materialInfo.colors.map((color) => color.name).join(", "),
          },
        ],
        note: materialInfo.origin,
      }
    : messages.home.materialFacts
  const credibilityContent = materialInfo
    ? {
        ...messages.home.credibility,
        title: materialInfo.tagline,
        benefits: certificationSummaries.map(
          (certification) => certification.result,
        ),
        features: certificationSummaries.map((certification) => ({
          title: certification.title,
          description: certification.result,
        })),
      }
    : messages.home.credibility

  return (
    <>
      <PageIntro
        eyebrow={intro.eyebrow}
        title={materialInfo?.tagline || intro.title}
        description={materialInfo?.origin || intro.description}
        primaryAction={{
          label: intro.primaryCta,
          href: `${getLocalizedHref(locale, "b2b")}?leadType=sample_request#inquiry`,
        }}
        secondaryAction={{
          label: intro.secondaryCta,
          href: getLocalizedHref(locale, "contact"),
        }}
      />
      <MaterialFamilySection locale={locale} content={messages.home.materialFamily} />
      <WhyItMattersSection content={whyItMattersContent} />
      <MaterialStorySection content={storyContent} />
      <OpenSourceLegacySection content={messages.home.openSourceLegacy} />
      <ApplicationsSection content={messages.home.applications} />
      <MaterialFactsSection
        locale={locale}
        content={materialFactsContent}
        specs={specs}
        sheetHref={`${getLocalizedHref(locale, "b2b")}?leadType=sample_request#inquiry`}
      />
      <MaterialProofPointsSection content={messages.materialProof.proofPoints} />
      <CertificationsAtAGlance
        certifications={materialInfo?.certifications ?? []}
        eyebrow={certificationMessages.eyebrow}
        title={certificationMessages.title}
        description={certificationMessages.description}
        variant="material"
        verifiedLabel={certificationMessages.verifiedLabel}
        emptyMessage={certificationMessages.emptyMessage}
        statusLabels={certificationMessages.statusLabels}
        issuerLabel={certificationMessages.issuerLabel}
        testedAtLabel={certificationMessages.testedAtLabel}
        downloadLabel={certificationMessages.downloadLabel}
      />
      <TechnicalDownloadsSection
        content={messages.materialProof.technicalDownloads}
        downloads={materialInfo?.technical_downloads ?? []}
      />
      <MaterialComparisonSection content={messages.materialProof.comparison} />
      <CredibilitySection content={credibilityContent} />
      <TrustAndCredibilitySection content={messages.trustAndCredibility} />
      <PilotProjectsSection content={messages.pilotProjects} />
      <CollaborationSection
        locale={locale}
        content={messages.home.collaboration}
        cardHrefs={[
          `${getLocalizedHref(locale, "b2b")}#inquiry`,
          `${getLocalizedHref(locale, "b2b")}?leadType=sample_request#inquiry`,
          `${getLocalizedHref(locale, "b2b")}?leadType=product_development_collaboration#inquiry`,
        ]}
      />
      <FinalCtaSection locale={locale} content={messages.home.finalCta} />
    </>
  )
}
