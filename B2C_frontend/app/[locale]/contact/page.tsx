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
  type B2BFormContent,
  type ContactDetailsContent,
} from "@/lib/page-content"
import { resolveLocale } from "@/lib/resolve-locale"
import type { HomeSection } from "@/lib/types"

type ContactPageProps = {
  params: Promise<{ locale: string }>
}

export default async function ContactPage({ params }: ContactPageProps) {
  const locale = await resolveLocale(params)
  const messages = getMessages(locale)
  const defaultPrimaryHref = getBrandContactHref("#inquiry")
  const defaultSecondaryHref = `${getLocalizedHref(locale, "b2b")}#inquiry`
  const apiBaseUrl = await getServerApiBaseUrl()
  let contactSections: HomeSection[] = []
  let sectionsLoaded = false
  let intro = buildPageIntroContent(
    messages.contactPage.intro,
    null,
    locale,
    defaultPrimaryHref,
    defaultSecondaryHref,
  )
  let contactDetails: ContactDetailsContent = messages.contactPage.details
  let formContent: B2BFormContent = messages.b2bPage.form
  let finalCtaContent = buildFinalCtaContent(messages.home.finalCta, null, locale)

  try {
    const [homeSections, fetchedContactSections] = await Promise.all([
      getHomeSections({ baseUrl: apiBaseUrl, locale, page: "home" }),
      getPageSections({ baseUrl: apiBaseUrl, locale, page: "contact" }),
    ])
    contactSections = fetchedContactSections
    sectionsLoaded = true
    const shouldUseCmsVisibility = contactSections.length > 0
    const contactSection = (key: string) => findPageSection(contactSections, key)
    const shouldRenderSection = (key: string) =>
      !shouldUseCmsVisibility || Boolean(contactSection(key))
    const footerSection = findHomeSection(homeSections, "footer")
    const footerContent = buildFooterContent(
      messages.footer,
      footerSection,
      locale,
      messages.header,
    )
    intro = buildPageIntroContent(
      messages.contactPage.intro,
      shouldRenderSection("intro") ? contactSection("intro") : null,
      locale,
      defaultPrimaryHref,
      defaultSecondaryHref,
    )
    contactDetails = buildContactDetailsContent(
      messages.contactPage.details,
      shouldRenderSection("details") ? contactSection("details") : null,
      footerContent,
      getBrandContactLabel(),
      locale,
    )
    const inquiryFormSection =
      contactSection("inquiry_form") ?? contactSection("form")
    formContent = buildB2BFormContent(
      messages.b2bPage.form,
      shouldRenderSection("inquiry_form") ? inquiryFormSection : null,
      locale,
    )
    finalCtaContent = buildFinalCtaContent(
      messages.home.finalCta,
      shouldRenderSection("final_cta") ? contactSection("final_cta") : null,
      locale,
    )
  } catch {
    // Use messages fallback
  }

  const shouldUseCmsVisibility = sectionsLoaded && contactSections.length > 0
  const contactSection = (key: string) => findPageSection(contactSections, key)
  const shouldRender = (key: string) =>
    !shouldUseCmsVisibility || Boolean(contactSection(key))

  return (
    <>
      {shouldRender("intro") ? (
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
      ) : null}
      {shouldRender("details") ? <ContactDetailsSection content={contactDetails} /> : null}
      {shouldRender("inquiry_form") ? (
        <Suspense fallback={null}>
          <B2BInquiryFormSection
            locale={locale}
            content={formContent}
            common={messages.common}
            id={formContent.formAnchorId ?? "inquiry"}
            sourcePage="contact"
            defaultLeadType="business_contact"
          />
        </Suspense>
      ) : null}
      {shouldRender("final_cta") ? (
        <FinalCtaSection locale={locale} content={finalCtaContent} />
      ) : null}
    </>
  )
}
