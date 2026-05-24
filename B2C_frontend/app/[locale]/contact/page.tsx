import { Fragment, Suspense, type ReactNode } from "react"

import { PageIntro } from "@/components/page-intro"
import { B2BInquiryFormSection } from "@/components/sections/b2b-inquiry-form"
import { ContactDetailsSection } from "@/components/sections/contact-details"
import { FinalCtaSection } from "@/components/sections/final-cta"
import { findHomeSection, getHomeSections } from "@/lib/api/homepage"
import { getPageSections } from "@/lib/api/page-sections"
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
import type { HomeSection } from "@/lib/types"

type ContactPageProps = {
  params: Promise<{ locale: string }>
}

function orderedSections(sections: HomeSection[]) {
  return [...sections].sort((a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0))
}

export default async function ContactPage({ params }: ContactPageProps) {
  const locale = await resolveLocale(params)
  const messages = getMessages(locale)
  const defaultPrimaryHref = getBrandContactHref("#inquiry")
  const defaultSecondaryHref = `${getLocalizedHref(locale, "b2b")}#inquiry`
  const apiBaseUrl = await getServerApiBaseUrl()

  let contactSections: HomeSection[] = []
  let footerSection: HomeSection | null = null

  try {
    const [homeSections, fetchedContactSections] = await Promise.all([
      getHomeSections({ baseUrl: apiBaseUrl, locale, page: "home" }),
      getPageSections({ baseUrl: apiBaseUrl, locale, page: "contact" }),
    ])

    contactSections = fetchedContactSections
    footerSection = findHomeSection(homeSections, "footer")
  } catch {
    contactSections = []
  }

  const footerContent = buildFooterContent(
    messages.footer,
    footerSection,
    locale,
    messages.header,
  )

  const formRenderer = (section: HomeSection) => {
    const content = buildB2BFormContent(messages.b2bPage.form, section, locale)

    return (
      <Suspense fallback={null}>
        <B2BInquiryFormSection
          locale={locale}
          content={content}
          common={messages.common}
          id={content.formAnchorId ?? "inquiry"}
          sourcePage="contact"
          defaultLeadType="business_contact"
        />
      </Suspense>
    )
  }

  const contactRenderers: Record<string, (section: HomeSection) => ReactNode> = {
    intro: (section) => {
      const intro = buildPageIntroContent(
        messages.contactPage.intro,
        section,
        locale,
        defaultPrimaryHref,
        defaultSecondaryHref,
      )

      return (
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
      )
    },
    details: (section) => (
      <ContactDetailsSection
        content={buildContactDetailsContent(
          messages.contactPage.details,
          section,
          footerContent,
          getBrandContactLabel(),
          locale,
        )}
      />
    ),
    form: formRenderer,
    inquiry_form: formRenderer,
    final_cta: (section) => (
      <FinalCtaSection
        locale={locale}
        content={buildFinalCtaContent(messages.home.finalCta, section, locale)}
      />
    ),
  }

  return (
    <>
      {orderedSections(contactSections).map((section) => {
        const renderSection = contactRenderers[section.key]

        return renderSection ? (
          <Fragment key={`${section.page_key}-${section.key}`}>
            {renderSection(section)}
          </Fragment>
        ) : null
      })}
    </>
  )
}
