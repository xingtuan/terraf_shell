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
import { findPageSection, getPageSections } from "@/lib/api/page-sections"
import { getServerApiBaseUrl } from "@/lib/api/server-base-url"
import { hasPublishedCmsSection } from "@/lib/cms-section-visibility"
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
  let b2bSections: HomeSection[] = []

  try {
    material = await getFeaturedMaterial({ baseUrl: apiBaseUrl, locale })
  } catch {
    material = null
  }

  try {
    b2bSections = await getPageSections({ baseUrl: apiBaseUrl, locale, page: "b2b" })
  } catch {
    b2bSections = []
  }

  const defaultPrimaryHref = `${getLocalizedHref(locale, "b2b")}?leadType=inquiry#inquiry`
  const defaultSecondaryHref = getLocalizedHref(locale, "material")
  const introSection = findPageSection(b2bSections, "intro")
  const collaborationSection = findPageSection(b2bSections, "collaboration")
  const processSection = findPageSection(b2bSections, "process")
  const ctaStripSection = findPageSection(b2bSections, "cta_strip")
  const applicationsSection = findPageSection(b2bSections, "applications")
  const materialFactsSection = findPageSection(b2bSections, "material_facts")
  const credibilitySection = findPageSection(b2bSections, "credibility")
  const trustSection = findPageSection(b2bSections, "trust_and_credibility")
  const pilotProjectsSection = findPageSection(b2bSections, "pilot_projects")
  const formSection = findPageSection(b2bSections, "form")
  const afterSubmitSection = findPageSection(b2bSections, "after_submit")
  const finalCtaSection = findPageSection(b2bSections, "final_cta")
  const materialFactSpecs = hasPublishedCmsSection(materialFactsSection)
    ? material?.specs.length
      ? material.specs
      : await getMaterialSpecs(locale, { baseUrl: apiBaseUrl, locale })
    : []
  const intro = hasPublishedCmsSection(introSection)
    ? buildPageIntroContent(
        messages.b2bPage.intro,
        introSection,
        locale,
        defaultPrimaryHref,
        defaultSecondaryHref,
      )
    : null
  const collaborationContent = hasPublishedCmsSection(collaborationSection)
    ? buildCollaborationContent(
        messages.home.collaboration,
        collaborationSection,
        locale,
      )
    : null
  const processContent = hasPublishedCmsSection(processSection)
    ? buildB2BProcessContent(messages.b2bPage.process, processSection, locale)
    : null
  const ctaStripContent = hasPublishedCmsSection(ctaStripSection)
    ? buildB2BCtaStripContent(messages.b2bPage.ctaStrip, ctaStripSection, locale)
    : null
  const applicationsContent = hasPublishedCmsSection(applicationsSection)
    ? buildB2BApplicationsContent(
        messages.b2bPage.applications,
        applicationsSection,
        locale,
      )
    : null
  const materialFactsContent = hasPublishedCmsSection(materialFactsSection)
    ? buildMaterialFactsContent(
        messages.home.materialFacts,
        material,
        materialFactsSection,
        locale,
      )
    : null
  const credibilityContent = hasPublishedCmsSection(credibilitySection)
    ? buildCredibilityContent(
        messages.home.credibility,
        material,
        locale,
        credibilitySection,
      )
    : null
  const trustContent = hasPublishedCmsSection(trustSection)
    ? buildTrustAndCredibilityContent(
        messages.trustAndCredibility,
        trustSection,
        locale,
      )
    : null
  const pilotProjectsContent = hasPublishedCmsSection(pilotProjectsSection)
    ? buildPilotProjectsContent(messages.pilotProjects, pilotProjectsSection, locale)
    : null
  const formContent = hasPublishedCmsSection(formSection)
    ? buildB2BFormContent(messages.b2bPage.form, formSection, locale)
    : null
  const afterSubmitContent = hasPublishedCmsSection(afterSubmitSection)
    ? buildB2BAfterSubmitContent(
        messages.b2bPage.afterSubmit,
        afterSubmitSection,
        locale,
      )
    : null
  const finalCtaContent = hasPublishedCmsSection(finalCtaSection)
    ? buildFinalCtaContent(messages.home.finalCta, finalCtaSection, locale)
    : null

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
      {collaborationContent ? (
        <CollaborationSection
          locale={locale}
          content={collaborationContent}
          cardHrefs={collaborationContent.cardHrefs}
        />
      ) : null}
      {processContent ? <B2BProcessSection content={processContent} /> : null}
      {ctaStripContent ? (
        <B2BCtaStrip locale={locale} content={ctaStripContent} />
      ) : null}
      {applicationsContent ? (
        <B2BApplicationsSection content={applicationsContent} />
      ) : null}
      {materialFactsContent ? (
        <MaterialFactsSection
          locale={locale}
          content={materialFactsContent}
          specs={materialFactSpecs}
          sheetHref={`${getLocalizedHref(locale, "b2b")}?leadType=sample_request#inquiry`}
        />
      ) : null}
      {credibilityContent ? (
        <CredibilitySection content={credibilityContent} />
      ) : null}
      {trustContent ? <TrustAndCredibilitySection content={trustContent} /> : null}
      {pilotProjectsContent ? (
        <PilotProjectsSection content={pilotProjectsContent} />
      ) : null}
      {formContent ? (
        <Suspense fallback={null}>
          <B2BInquiryFormSection
            locale={locale}
            content={formContent}
            common={messages.common}
            sourcePage="b2b"
            defaultLeadType="inquiry"
          />
        </Suspense>
      ) : null}
      {afterSubmitContent ? (
        <B2BAfterSubmitSection content={afterSubmitContent} />
      ) : null}
      {finalCtaContent ? (
        <FinalCtaSection locale={locale} content={finalCtaContent} />
      ) : null}
    </>
  )
}
