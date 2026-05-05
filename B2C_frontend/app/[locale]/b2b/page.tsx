import { Suspense } from "react"

import { PageIntro } from "@/components/page-intro"
import { B2BInquiryFormSection } from "@/components/sections/b2b-inquiry-form"
import { CollaborationSection } from "@/components/sections/collaboration"
import { CredibilitySection } from "@/components/sections/credibility"
import { FinalCtaSection } from "@/components/sections/final-cta"
import { MaterialFactsSection } from "@/components/sections/material-facts"
import {
  B2BAfterSubmitSection,
  B2BApplicationsSection,
  B2BCtaStrip,
  B2BProcessSection,
  PilotProjectsSection,
  TrustAndCredibilitySection,
} from "@/components/sections/trust-and-b2b-sections"
import { getFeaturedMaterial, getMaterialSpecs } from "@/lib/api/materials"
import { getServerApiBaseUrl } from "@/lib/api/server-base-url"
import { getLocalizedHref, getMessages } from "@/lib/i18n"
import {
  buildCredibilityContent,
  buildMaterialFactsContent,
} from "@/lib/page-content"
import { resolveLocale } from "@/lib/resolve-locale"

type B2BPageProps = {
  params: Promise<{ locale: string }>
}

export default async function B2BPage({ params }: B2BPageProps) {
  const locale = await resolveLocale(params)
  const apiBaseUrl = await getServerApiBaseUrl()
  const messages = getMessages(locale)

  let material = null

  try {
    material = await getFeaturedMaterial({ baseUrl: apiBaseUrl })
  } catch {
    material = null
  }

  const fallbackSpecs =
    material?.specs.length
      ? material.specs
      : await getMaterialSpecs(locale, { baseUrl: apiBaseUrl })

  const intro = messages.b2bPage.intro
  const materialFactsContent = buildMaterialFactsContent(
    messages.home.materialFacts,
    material,
    null,
  )
  const credibilityContent = buildCredibilityContent(
    messages.home.credibility,
    material,
  )

  return (
    <>
      <PageIntro
        eyebrow={intro.eyebrow}
        title={intro.title}
        description={intro.description}
        primaryAction={{
          label: intro.primaryCta,
          href: `${getLocalizedHref(locale, "b2b")}?leadType=inquiry#inquiry`,
        }}
        secondaryAction={{
          label: intro.secondaryCta,
          href: getLocalizedHref(locale, "material"),
        }}
      />
      <CollaborationSection
        locale={locale}
        content={messages.home.collaboration}
        cardHrefs={[
          `${getLocalizedHref(locale, "b2b")}?leadType=inquiry#inquiry`,
          `${getLocalizedHref(locale, "b2b")}?leadType=sample_request#inquiry`,
          `${getLocalizedHref(locale, "b2b")}?leadType=product_development_collaboration#inquiry`,
        ]}
      />
      <B2BProcessSection content={messages.b2bPage.process} />
      <B2BCtaStrip locale={locale} content={messages.b2bPage.ctaStrip} />
      <B2BApplicationsSection content={messages.b2bPage.applications} />
      <MaterialFactsSection
        locale={locale}
        content={materialFactsContent}
        specs={material?.specs.length ? material.specs : fallbackSpecs}
        sheetHref={`${getLocalizedHref(locale, "b2b")}?leadType=sample_request#inquiry`}
      />
      <CredibilitySection content={credibilityContent} />
      <TrustAndCredibilitySection content={messages.trustAndCredibility} />
      <PilotProjectsSection content={messages.pilotProjects} />
      <Suspense fallback={null}>
        <B2BInquiryFormSection
          locale={locale}
          content={messages.b2bPage.form}
          common={messages.common}
          sourcePage="b2b"
          defaultLeadType="inquiry"
        />
      </Suspense>
      <B2BAfterSubmitSection content={messages.b2bPage.afterSubmit} />
      <FinalCtaSection locale={locale} content={messages.home.finalCta} />
    </>
  )
}
