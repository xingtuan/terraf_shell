import { PageIntro } from "@/components/page-intro"
import { B2BInquiryFormSection } from "@/components/sections/b2b-inquiry-form"
import { CollaborationSection } from "@/components/sections/collaboration"
import { CredibilitySection } from "@/components/sections/credibility"
import { FinalCtaSection } from "@/components/sections/final-cta"
import { MaterialFactsSection } from "@/components/sections/material-facts"
import { getMaterialSpecs } from "@/lib/api/materials"
import { getLocalizedHref, getMessages } from "@/lib/i18n"
import { resolveLocale } from "@/lib/resolve-locale"

type B2BPageProps = {
  params: Promise<{ locale: string }>
}

export default async function B2BPage({ params }: B2BPageProps) {
  const locale = await resolveLocale(params)
  const messages = getMessages(locale)
  const specs = await getMaterialSpecs(locale)
  const intro = messages.b2bPage.intro

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
          href: getLocalizedHref(locale, "material"),
        }}
      />
      <CollaborationSection locale={locale} content={messages.home.collaboration} />
      <MaterialFactsSection
        locale={locale}
        content={messages.home.materialFacts}
        specs={specs}
      />
      <CredibilitySection content={messages.home.credibility} />
      <B2BInquiryFormSection
        locale={locale}
        content={messages.b2bPage.form}
        sourcePage="b2b"
      />
      <FinalCtaSection locale={locale} content={messages.home.finalCta} />
    </>
  )
}
