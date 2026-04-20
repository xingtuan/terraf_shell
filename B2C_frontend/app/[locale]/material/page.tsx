import { ApplicationsSection } from "@/components/sections/applications"
import { CollaborationSection } from "@/components/sections/collaboration"
import { CredibilitySection } from "@/components/sections/credibility"
import { FinalCtaSection } from "@/components/sections/final-cta"
import { MaterialFactsSection } from "@/components/sections/material-facts"
import { MaterialStorySection } from "@/components/sections/material-story"
import { WhyItMattersSection } from "@/components/sections/why-it-matters"
import { PageIntro } from "@/components/page-intro"
import {
  getMaterialInfo,
  materialInfoToSpecs,
} from "@/lib/api/materials"
import { getServerApiBaseUrl } from "@/lib/api/server-base-url"
import { getLocalizedHref, getMessages } from "@/lib/i18n"
import { resolveLocale } from "@/lib/resolve-locale"

type MaterialPageProps = {
  params: Promise<{ locale: string }>
}

export default async function MaterialPage({ params }: MaterialPageProps) {
  const locale = await resolveLocale(params)
  const apiBaseUrl = await getServerApiBaseUrl()
  const messages = getMessages(locale)

  let materialInfo = null

  try {
    const response = await getMaterialInfo({ baseUrl: apiBaseUrl })
    materialInfo = response.data
  } catch {
    materialInfo = null
  }

  const intro = messages.materialPage.intro
  const specs = materialInfo ? materialInfoToSpecs(materialInfo) : []
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
        sheetTitle: `${materialInfo.name} Material Sheet`,
        sheetDescription: materialInfo.certifications
          .map((certification) => `${certification.label}: ${certification.value}`)
          .join(" / "),
        infoCards: [
          {
            label: "Models",
            value: materialInfo.models.map((model) => model.name).join(", "),
          },
          {
            label: "Colors",
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
        benefits: materialInfo.certifications.map((certification) => certification.value),
        features: materialInfo.certifications.map((certification) => ({
          title: certification.label,
          description: certification.value,
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
      <WhyItMattersSection content={whyItMattersContent} />
      <MaterialStorySection content={storyContent} />
      <ApplicationsSection content={messages.home.applications} />
      <MaterialFactsSection
        locale={locale}
        content={materialFactsContent}
        specs={specs}
        sheetHref={`${getLocalizedHref(locale, "b2b")}?leadType=sample_request#inquiry`}
      />
      <CredibilitySection content={credibilityContent} />
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
