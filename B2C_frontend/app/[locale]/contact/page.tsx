import { Suspense } from "react"

import { PageIntro } from "@/components/page-intro"
import { B2BInquiryFormSection } from "@/components/sections/b2b-inquiry-form"
import { ContactDetailsSection } from "@/components/sections/contact-details"
import { FinalCtaSection } from "@/components/sections/final-cta"
import { findHomeSection, getHomeSections } from "@/lib/api/homepage"
import { getServerApiBaseUrl } from "@/lib/api/server-base-url"
import { getBrandContactHref, getBrandContactLabel } from "@/lib/brand"
import { getLocalizedHref, getMessages } from "@/lib/i18n"
import {
  buildContactDetailsFromFooterContent,
  buildFooterContent,
} from "@/lib/page-content"
import { resolveLocale } from "@/lib/resolve-locale"

type ContactPageProps = {
  params: Promise<{ locale: string }>
}

export default async function ContactPage({ params }: ContactPageProps) {
  const locale = await resolveLocale(params)
  const messages = getMessages(locale)
  const intro = messages.contactPage.intro

  let contactDetails = messages.contactPage.details

  try {
    const apiBaseUrl = await getServerApiBaseUrl()
    const sections = await getHomeSections({ baseUrl: apiBaseUrl, locale })
    const footerSection = findHomeSection(sections, "footer")
    const footerContent = buildFooterContent(
      messages.footer,
      footerSection,
      locale,
      messages.header,
    )
    contactDetails = buildContactDetailsFromFooterContent(
      footerContent,
      messages.contactPage.details,
      getBrandContactLabel(),
    )
  } catch {
    // Use messages fallback
  }

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
      <ContactDetailsSection content={contactDetails} />
      <Suspense fallback={null}>
        <B2BInquiryFormSection
          locale={locale}
          content={messages.b2bPage.form}
          common={messages.common}
          id="contact-form"
          sourcePage="contact"
          defaultLeadType="business_contact"
        />
      </Suspense>
      <FinalCtaSection locale={locale} content={messages.home.finalCta} />
    </>
  )
}
