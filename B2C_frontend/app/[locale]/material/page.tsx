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
  materialInfoToDetail,
  materialInfoToSpecs,
} from "@/lib/api/materials"
import { getServerApiBaseUrl } from "@/lib/api/server-base-url"
import { getLocalizedHref, getMessages } from "@/lib/i18n"
import {
  buildApplicationsContent,
  resolveLocalizedApiValue,
} from "@/lib/page-content"
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
  const materialDetail = materialInfo ? materialInfoToDetail(materialInfo) : null
  const specs = materialInfo
    ? materialInfoToSpecs(materialInfo)
    : await getMaterialSpecs(locale, materialRequestOptions)
  const certificationSummaries = (materialInfo?.certifications ?? [])
    .map((certification) => {
      const title = cleanCertificationText(
        resolveLocalizedApiValue(
          certification.label ?? certification.name ?? certification.key,
          null,
          locale,
        ),
      )
      const measuredResult = [certification.result, certification.unit]
        .filter(Boolean)
        .join(" ")
      const result = cleanCertificationText(
        resolveLocalizedApiValue(
          certification.value ||
            measuredResult ||
            certification.description ||
            certification.status,
          null,
          locale,
        ),
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
        title: resolveLocalizedApiValue(
          materialInfo.tagline,
          messages.home.whyItMatters.title,
          locale,
        ),
        cards: materialInfo.properties.slice(0, 3).map((property, index) => ({
          title: resolveLocalizedApiValue(
            property.label,
            messages.home.whyItMatters.cards[index]?.title,
            locale,
          ),
          description: [
            resolveLocalizedApiValue(property.value, null, locale),
            resolveLocalizedApiValue(property.vs, null, locale),
          ]
            .filter(Boolean)
            .join(". "),
        })),
        stats: [
          resolveLocalizedApiValue(
            materialInfo.origin,
            messages.home.whyItMatters.stats[0],
            locale,
          ),
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
        title: resolveLocalizedApiValue(
          materialInfo.process_steps.map((step) => step.title).join(" -> "),
          messages.home.materialStory.title,
          locale,
        ),
        steps: materialInfo.process_steps.map((step) => ({
          number: String(step.step).padStart(2, "0"),
          title: resolveLocalizedApiValue(
            step.title,
            messages.home.materialStory.steps[step.step - 1]?.title,
            locale,
          ),
          description: resolveLocalizedApiValue(
            step.body,
            messages.home.materialStory.steps[step.step - 1]?.description,
            locale,
          ),
        })),
      }
    : messages.home.materialStory
  const applicationsContent = buildApplicationsContent(
    messages.home.applications,
    materialDetail,
    locale,
  )
  const materialFactsContent = materialInfo
    ? {
        ...messages.home.materialFacts,
        title: resolveLocalizedApiValue(
          materialInfo.tagline,
          messages.home.materialFacts.title,
          locale,
        ),
        sheetTitle: `${resolveLocalizedApiValue(
          materialInfo.name,
          messages.home.materialFacts.sheetTitle,
          locale,
        )} ${messages.materialPage.sheetTitleSuffix}`,
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
        note: resolveLocalizedApiValue(
          materialInfo.origin,
          messages.home.materialFacts.note,
          locale,
        ),
      }
    : messages.home.materialFacts
  const credibilityContent = materialInfo
    ? {
        ...messages.home.credibility,
        title: resolveLocalizedApiValue(
          materialInfo.tagline,
          messages.home.credibility.title,
          locale,
        ),
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
        title={resolveLocalizedApiValue(materialInfo?.tagline, intro.title, locale)}
        description={resolveLocalizedApiValue(
          materialInfo?.origin,
          intro.description,
          locale,
        )}
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
      <ApplicationsSection content={applicationsContent} />
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
