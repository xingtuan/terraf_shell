import { ApplicationsSection } from "@/components/sections/applications"
import { CollaborationSection } from "@/components/sections/collaboration"
import { CredibilitySection } from "@/components/sections/credibility"
import { FinalCtaSection } from "@/components/sections/final-cta"
import { HeroSection } from "@/components/sections/hero"
import { MaterialFactsSection } from "@/components/sections/material-facts"
import { MaterialStorySection } from "@/components/sections/material-story"
import { WhyItMattersSection } from "@/components/sections/why-it-matters"
import { getMaterialSpecs } from "@/lib/api/materials"
import { getMessages } from "@/lib/i18n"
import { resolveLocale } from "@/lib/resolve-locale"

type HomePageProps = {
  params: Promise<{ locale: string }>
}

export default async function LocaleHomePage({ params }: HomePageProps) {
  const locale = await resolveLocale(params)
  const messages = getMessages(locale)
  const specs = await getMaterialSpecs(locale)

  return (
    <>
      <HeroSection locale={locale} content={messages.home.hero} />
      <WhyItMattersSection content={messages.home.whyItMatters} />
      <MaterialStorySection content={messages.home.materialStory} />
      <ApplicationsSection content={messages.home.applications} />
      <MaterialFactsSection
        locale={locale}
        content={messages.home.materialFacts}
        specs={specs}
      />
      <CollaborationSection locale={locale} content={messages.home.collaboration} />
      <CredibilitySection content={messages.home.credibility} />
      <FinalCtaSection locale={locale} content={messages.home.finalCta} />
    </>
  )
}
