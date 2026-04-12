import { CollaborationSection } from "@/components/sections/collaboration"
import { CredibilitySection } from "@/components/sections/credibility"
import { FinalCtaSection } from "@/components/sections/final-cta"
import { MaterialFactsSection } from "@/components/sections/material-facts"
import { MaterialStorySection } from "@/components/sections/material-story"
import { WhyItMattersSection } from "@/components/sections/why-it-matters"
import { PageIntro } from "@/components/page-intro"
import { getMaterialSpecs } from "@/lib/api/materials"
import { getLocalizedHref, getMessages } from "@/lib/i18n"
import { resolveLocale } from "@/lib/resolve-locale"

type MaterialPageProps = {
  params: Promise<{ locale: string }>
}

export default async function MaterialPage({ params }: MaterialPageProps) {
  const locale = await resolveLocale(params)
  const messages = getMessages(locale)
  const specs = await getMaterialSpecs(locale)
  const intro = messages.materialPage.intro

  return (
    <>
      <PageIntro
        eyebrow={intro.eyebrow}
        title={intro.title}
        description={intro.description}
        primaryAction={{
          label: intro.primaryCta,
          href: `${getLocalizedHref(locale, "b2b")}#inquiry`,
        }}
        secondaryAction={{
          label: intro.secondaryCta,
          href: getLocalizedHref(locale, "contact"),
        }}
      />
      <WhyItMattersSection content={messages.home.whyItMatters} />
      <MaterialStorySection content={messages.home.materialStory} />
      <MaterialFactsSection
        locale={locale}
        content={messages.home.materialFacts}
        specs={specs}
      />
      <CredibilitySection content={messages.home.credibility} />
      <CollaborationSection locale={locale} content={messages.home.collaboration} />
      <FinalCtaSection locale={locale} content={messages.home.finalCta} />
    </>
  )
}
