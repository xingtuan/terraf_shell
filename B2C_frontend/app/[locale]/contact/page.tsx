import { Suspense } from "react"

import { PageIntro } from "@/components/page-intro"
import { B2BInquiryFormSection } from "@/components/sections/b2b-inquiry-form"
import { ContactDetailsSection } from "@/components/sections/contact-details"
import { FinalCtaSection } from "@/components/sections/final-cta"
import { findHomeSection, getHomeSections } from "@/lib/api/homepage"
import { findPageSection, getPageSections } from "@/lib/api/page-sections"
import { getServerApiBaseUrl } from "@/lib/api/server-base-url"
import { getBrandContactHref, getBrandContactLabel } from "@/lib/brand"
import { getLocalizedHref, getMessages } from "@/lib/i18n"
import {
  buildB2BFormContent,
  buildContactDetailsContent,
  buildFinalCtaContent,
  buildFooterContent,
  buildPageIntroContent,
} from "@/lib/page-content"
import { resolveLocale } from "@/lib/resolve-locale"

type ContactPageProps = {
  params: Promise<{ locale: string }>
}

export default async function ContactPage({ params }: ContactPageProps) {
  const locale = await resolveLocale(params)
  const messages = getMessages(locale)
  const defaultPrimaryHref = getBrandContactHref("#contact-form")
  const defaultSecondaryHref = `${getLocalizedHref(locale, "b2b")}#inquiry`
  let intro = buildPageIntroContent(
    messages.contactPage.intro,
    null,
    locale,
    defaultPrimaryHref,
    defaultSecondaryHref,
  )
  let contactDetails = messages.contactPage.details
  let formContent = messages.b2bPage.form
  let finalCtaContent = buildFinalCtaContent(messages.home.finalCta, null, locale)

  try {
    const apiBaseUrl = await getServerApiBaseUrl()
    const [homeSections, contactSections] = await Promise.all([
      getHomeSections({ baseUrl: apiBaseUrl, locale, page: "home" }),
      getPageSections({ baseUrl: apiBaseUrl, locale, page: "contact" }),
    ])
    const footerSection = findHomeSection(homeSections, "footer")
    const footerContent = buildFooterContent(
      messages.footer,
      footerSection,
      locale,
      messages.header,
    )
    intro = buildPageIntroContent(
      messages.contactPage.intro,
      findPageSection(contactSections, "intro"),
      locale,
      defaultPrimaryHref,
      defaultSecondaryHref,
    )
    contactDetails = buildContactDetailsContent(
      messages.contactPage.details,
      findPageSection(contactSections, "details"),
      footerContent,
      getBrandContactLabel(),
      locale,
    )
    formContent = buildB2BFormContent(
      messages.b2bPage.form,
      findPageSection(contactSections, "form"),
      locale,
    )
    finalCtaContent = buildFinalCtaContent(
      messages.home.finalCta,
      findPageSection(contactSections, "final_cta"),
      locale,
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
          href: intro.primaryHref ?? defaultPrimaryHref,
        }}
        secondaryAction={{
          label: intro.secondaryCta,
          href: intro.secondaryHref ?? defaultSecondaryHref,
        }}
      />
      <ContactDetailsSection content={contactDetails} />
      <Suspense fallback={null}>
        <B2BInquiryFormSection
          locale={locale}
          content={formContent}
          common={messages.common}
          id="contact-form"
          sourcePage="contact"
          defaultLeadType="business_contact"
        />
      </Suspense>
      <FinalCtaSection locale={locale} content={finalCtaContent} />
    </>
  )
}
