import { Suspense } from "react"

import { PageIntro } from "@/components/page-intro"
import { B2BInquiryFormSection } from "@/components/sections/b2b-inquiry-form"
import { ContactDetailsSection } from "@/components/sections/contact-details"
import { FinalCtaSection } from "@/components/sections/final-cta"
import { findHomeSection, getHomeSections } from "@/lib/api/homepage"
import { findPageSection, getPageSections } from "@/lib/api/page-sections"
import { getServerApiBaseUrl } from "@/lib/api/server-base-url"
import { getBrandContactHref, getBrandContactLabel } from "@/lib/brand"
import { hasPublishedCmsSection } from "@/lib/cms-section-visibility"
import { getLocalizedHref, getMessages } from "@/lib/i18n"
import {
  buildB2BFormContent,
  buildContactDetailsContent,
  buildFinalCtaContent,
  buildFooterContent,
  buildPageIntroContent,
  type B2BFormContent,
  type ContactDetailsContent,
  type PageIntroContent,
} from "@/lib/page-content"
import { resolveLocale } from "@/lib/resolve-locale"

type ContactPageProps = {
  params: Promise<{ locale: string }>
}

export default async function ContactPage({ params }: ContactPageProps) {
  const locale = await resolveLocale(params)
  const messages = getMessages(locale)
  const defaultPrimaryHref = getBrandContactHref("#inquiry")
  const defaultSecondaryHref = `${getLocalizedHref(locale, "b2b")}#inquiry`
  const apiBaseUrl = await getServerApiBaseUrl()
  let intro: PageIntroContent<typeof messages.contactPage.intro> | null = null
  let contactDetails: ContactDetailsContent | null = null
  let formContent: B2BFormContent | null = null
  let finalCtaContent: ReturnType<typeof buildFinalCtaContent> | null = null

  try {
    const [homeSections, fetchedContactSections] = await Promise.all([
      getHomeSections({ baseUrl: apiBaseUrl, locale, page: "home" }),
      getPageSections({ baseUrl: apiBaseUrl, locale, page: "contact" }),
    ])
    const contactSection = (key: string) => findPageSection(fetchedContactSections, key)
    const introSection = contactSection("intro")
    const detailsSection = contactSection("details")
    const inquiryFormSection =
      contactSection("inquiry_form") ?? contactSection("form")
    const finalCtaSection = contactSection("final_cta")
    const footerSection = findHomeSection(homeSections, "footer")

    if (hasPublishedCmsSection(introSection)) {
      intro = buildPageIntroContent(
        messages.contactPage.intro,
        introSection,
        locale,
        defaultPrimaryHref,
        defaultSecondaryHref,
      )
    }

    if (hasPublishedCmsSection(detailsSection)) {
      const footerContent = buildFooterContent(
        messages.footer,
        footerSection,
        locale,
        messages.header,
      )
      contactDetails = buildContactDetailsContent(
        messages.contactPage.details,
        detailsSection,
        footerContent,
        getBrandContactLabel(),
        locale,
      )
    }

    if (hasPublishedCmsSection(inquiryFormSection)) {
      formContent = buildB2BFormContent(
        messages.b2bPage.form,
        inquiryFormSection,
        locale,
      )
    }

    if (hasPublishedCmsSection(finalCtaSection)) {
      finalCtaContent = buildFinalCtaContent(
        messages.home.finalCta,
        finalCtaSection,
        locale,
      )
    }
  } catch {
    // Missing CMS sections should hide CMS-driven blocks.
  }

  return (
    <>
      {intro ? (
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
      {contactDetails ? <ContactDetailsSection content={contactDetails} /> : null}
      {formContent ? (
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
      {finalCtaContent ? (
        <FinalCtaSection locale={locale} content={finalCtaContent} />
      ) : null}
    </>
  )
}
