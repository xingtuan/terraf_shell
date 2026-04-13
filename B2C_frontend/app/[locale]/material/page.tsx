import { ApplicationsSection } from "@/components/sections/applications"
import { CollaborationSection } from "@/components/sections/collaboration"
import { CredibilitySection } from "@/components/sections/credibility"
import { FinalCtaSection } from "@/components/sections/final-cta"
import { MaterialFactsSection } from "@/components/sections/material-facts"
import { MaterialStorySection } from "@/components/sections/material-story"
import { WhyItMattersSection } from "@/components/sections/why-it-matters"
import { PageIntro } from "@/components/page-intro"
import { getFeaturedMaterial, getMaterialSpecs } from "@/lib/api/materials"
import { getLocalizedHref, getMessages } from "@/lib/i18n"
import {
  buildApplicationsContent,
  buildCredibilityContent,
  buildMaterialFactsContent,
  buildMaterialStoryContent,
} from "@/lib/page-content"
import { resolveLocale } from "@/lib/resolve-locale"

type MaterialPageProps = {
  params: Promise<{ locale: string }>
}

export default async function MaterialPage({ params }: MaterialPageProps) {
  const locale = await resolveLocale(params)
  const messages = getMessages(locale)
  const fallbackSpecs = await getMaterialSpecs(locale)

  let material = null

  try {
    material = await getFeaturedMaterial()
  } catch {
    material = null
  }

  const intro = messages.materialPage.intro
  const storyContent = buildMaterialStoryContent(
    messages.home.materialStory,
    material,
  )
  const applicationsContent = buildApplicationsContent(
    messages.home.applications,
    material,
  )
  const materialFactsContent = buildMaterialFactsContent(
    messages.home.materialFacts,
    material,
    null,
  )
  const credibilityContent = buildCredibilityContent(
    messages.home.credibility,
    material,
  )

  return (
    <>
      <PageIntro
        eyebrow={intro.eyebrow}
        title={material?.headline || intro.title}
        description={material?.summary || intro.description}
        primaryAction={{
          label: intro.primaryCta,
          href: `${getLocalizedHref(locale, "b2b")}?leadType=sample_request#inquiry`,
        }}
        secondaryAction={{
          label: intro.secondaryCta,
          href: getLocalizedHref(locale, "contact"),
        }}
      />
      <WhyItMattersSection content={messages.home.whyItMatters} />
      <MaterialStorySection content={storyContent} />
      <ApplicationsSection content={applicationsContent} />
      <MaterialFactsSection
        locale={locale}
        content={materialFactsContent}
        specs={material?.specs.length ? material.specs : fallbackSpecs}
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
