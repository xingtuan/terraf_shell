import { PageIntro } from "@/components/page-intro"
import { B2BInquiryFormSection } from "@/components/sections/b2b-inquiry-form"
import { ContactDetailsSection } from "@/components/sections/contact-details"
import { FinalCtaSection } from "@/components/sections/final-cta"
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
          href: "mailto:hello@shellfin.kr",
        }}
        secondaryAction={{
          label: intro.secondaryCta,
          href: `${getLocalizedHref(locale, "b2b")}#inquiry`,
        }}
      />
      <ContactDetailsSection content={messages.contactPage.details} />
      <B2BInquiryFormSection
        locale={locale}
        content={messages.b2bPage.form}
        id="contact-form"
        sourcePage="contact"
      />
      <FinalCtaSection locale={locale} content={messages.home.finalCta} />
    </>
  )
}
