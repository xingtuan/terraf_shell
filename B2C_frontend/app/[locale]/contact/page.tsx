import { Suspense } from "react"

import { PageIntro } from "@/components/page-intro"
import { B2BInquiryFormSection } from "@/components/sections/b2b-inquiry-form"
import { ContactDetailsSection } from "@/components/sections/contact-details"
import { FinalCtaSection } from "@/components/sections/final-cta"
import { getBrandContactHref } from "@/lib/brand"
import { getLocalizedHref, getMessages } from "@/lib/i18n"
import { resolveLocale } from "@/lib/resolve-locale"

type ContactPageProps = {
  params: Promise<{ locale: string }>
}

export default async function ContactPage({ params }: ContactPageProps) {
  const locale = await resolveLocale(params)
  const messages = getMessages(locale)
  const intro = messages.contactPage.intro

  return (
    <>
      <PageIntro
        eyebrow={intro.eyebrow}
        title={intro.title}
        description={intro.description}
        primaryAction={{
          label: intro.primaryCta,
          href: getBrandContactHref("#contact-form"),
        }}
        secondaryAction={{
          label: intro.secondaryCta,
          href: `${getLocalizedHref(locale, "b2b")}#inquiry`,
        }}
      />
      <ContactDetailsSection content={messages.contactPage.details} />
      <Suspense fallback={null}>
        <B2BInquiryFormSection
          locale={locale}
          content={messages.b2bPage.form}
          id="contact-form"
          sourcePage="contact"
          defaultLeadType="business_contact"
        />
      </Suspense>
      <FinalCtaSection locale={locale} content={messages.home.finalCta} />
    </>
  )
}
