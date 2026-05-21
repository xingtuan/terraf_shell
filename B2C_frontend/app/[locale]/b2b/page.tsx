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
import { findHomeSection, getHomeSections } from "@/lib/api/homepage"
import { getFeaturedMaterial, getMaterialSpecs } from "@/lib/api/materials"
import { findPageSection, getPageSections } from "@/lib/api/page-sections"
import { getServerApiBaseUrl } from "@/lib/api/server-base-url"
import { getLocalizedHref, getMessages } from "@/lib/i18n"
import type { HomeSection } from "@/lib/types"
import {
  buildB2BAfterSubmitContent,
  buildB2BApplicationsContent,
  buildB2BCtaStripContent,
  buildB2BFormContent,
  buildB2BProcessContent,
  buildCollaborationContent,
  buildCredibilityContent,
  buildFinalCtaContent,
  buildMaterialFactsContent,
  buildPageIntroContent,
  buildPilotProjectsContent,
  buildTrustAndCredibilityContent,
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
  let pilotProjectsSection = null
  let b2bSections: HomeSection[] = []
  let homeSections: HomeSection[] = []

  try {
    material = await getFeaturedMaterial({ baseUrl: apiBaseUrl, locale })
  } catch {
    material = null
  }

  try {
    ;[homeSections, b2bSections] = await Promise.all([
      getHomeSections({ baseUrl: apiBaseUrl, locale, page: "home" }),
      getPageSections({ baseUrl: apiBaseUrl, locale, page: "b2b" }),
    ])
    pilotProjectsSection =
      findPageSection(b2bSections, "pilot_projects") ??
      findHomeSection(homeSections, "pilot_projects")
  } catch {
    pilotProjectsSection = null
  }

  const fallbackSpecs =
    material?.specs.length
      ? material.specs
      : await getMaterialSpecs(locale, { baseUrl: apiBaseUrl, locale })

  const defaultPrimaryHref = `${getLocalizedHref(locale, "b2b")}?leadType=inquiry#inquiry`
  const defaultSecondaryHref = getLocalizedHref(locale, "material")
  const intro = buildPageIntroContent(
    messages.b2bPage.intro,
    findPageSection(b2bSections, "intro"),
    locale,
    defaultPrimaryHref,
    defaultSecondaryHref,
  )
  const collaborationContent = buildCollaborationContent(
    messages.home.collaboration,
    findPageSection(b2bSections, "collaboration") ??
      findHomeSection(homeSections, "collaboration"),
    locale,
  )
  const materialFactsContent = buildMaterialFactsContent(
    messages.home.materialFacts,
    material,
    findPageSection(b2bSections, "material_facts"),
    locale,
  )
  const credibilityContent = buildCredibilityContent(
    messages.home.credibility,
    material,
    locale,
    findPageSection(b2bSections, "credibility"),
  )
  const processContent = buildB2BProcessContent(
    messages.b2bPage.process,
    findPageSection(b2bSections, "process"),
    locale,
  )
  const ctaStripContent = buildB2BCtaStripContent(
    messages.b2bPage.ctaStrip,
    findPageSection(b2bSections, "cta_strip"),
    locale,
  )
  const applicationsContent = buildB2BApplicationsContent(
    messages.b2bPage.applications,
    findPageSection(b2bSections, "applications"),
    locale,
  )
  const formContent = buildB2BFormContent(
    messages.b2bPage.form,
    findPageSection(b2bSections, "form"),
    locale,
  )
  const afterSubmitContent = buildB2BAfterSubmitContent(
    messages.b2bPage.afterSubmit,
    findPageSection(b2bSections, "after_submit"),
    locale,
  )
  const trustContent = buildTrustAndCredibilityContent(
    messages.trustAndCredibility,
    findPageSection(b2bSections, "trust_and_credibility") ??
      findHomeSection(homeSections, "trust_and_credibility"),
    locale,
  )
  const pilotProjectsContent = buildPilotProjectsContent(
    messages.pilotProjects,
    pilotProjectsSection,
    locale,
  )
  const finalCtaContent = buildFinalCtaContent(
    messages.home.finalCta,
    findPageSection(b2bSections, "final_cta"),
    locale,
  )

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
      <CollaborationSection
        locale={locale}
        content={collaborationContent}
        cardHrefs={collaborationContent.cardHrefs}
      />
      <B2BProcessSection content={processContent} />
      <B2BCtaStrip locale={locale} content={ctaStripContent} />
      <B2BApplicationsSection content={applicationsContent} />
      <MaterialFactsSection
        locale={locale}
        content={materialFactsContent}
        specs={material?.specs.length ? material.specs : fallbackSpecs}
        sheetHref={`${getLocalizedHref(locale, "b2b")}?leadType=sample_request#inquiry`}
      />
      <CredibilitySection content={credibilityContent} />
      <TrustAndCredibilitySection content={trustContent} />
      <PilotProjectsSection content={pilotProjectsContent} />
      <Suspense fallback={null}>
        <B2BInquiryFormSection
          locale={locale}
          content={formContent}
          common={messages.common}
          sourcePage="b2b"
          defaultLeadType="inquiry"
        />
      </Suspense>
      <B2BAfterSubmitSection content={afterSubmitContent} />
      <FinalCtaSection locale={locale} content={finalCtaContent} />
    </>
  )
}
