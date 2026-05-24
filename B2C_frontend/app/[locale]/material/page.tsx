import { ApplicationsSection } from "@/components/sections/applications"
import { CollaborationSection } from "@/components/sections/collaboration"
import { CertificationsAtAGlance } from "@/components/sections/certifications-at-a-glance"
import { CredibilitySection } from "@/components/sections/credibility"
import { FinalCtaSection } from "@/components/sections/final-cta"
import {
  MaterialComparisonSection,
  MaterialProofPointsSection,
  TechnicalDownloadsSection,
} from "@/components/sections/material-proof-sections"
import { MaterialFactsSection } from "@/components/sections/material-facts"
import { MaterialFamilySection } from "@/components/sections/material-family"
import { MaterialStorySection } from "@/components/sections/material-story"
import { OpenSourceLegacySection } from "@/components/sections/open-source-legacy"
import {
  PilotProjectsSection,
  TrustAndCredibilitySection,
} from "@/components/sections/trust-and-b2b-sections"
import { WhyItMattersSection } from "@/components/sections/why-it-matters"
import { PageIntro } from "@/components/page-intro"
import { findPageSection, getPageSections } from "@/lib/api/page-sections"
import {
  getMaterialInfo,
  getMaterialSpecs,
  materialInfoToDetail,
  materialInfoToSpecs,
} from "@/lib/api/materials"
import { getServerApiBaseUrl } from "@/lib/api/server-base-url"
import { hasPublishedCmsSection } from "@/lib/cms-section-visibility"
import { getLocalizedHref, getMessages } from "@/lib/i18n"
import {
  buildApplicationsContent,
  buildCertificationsContent,
  buildCollaborationContent,
  buildCredibilityContent,
  buildFinalCtaContent,
  buildMaterialComparisonContent,
  buildMaterialFactsContent,
  buildMaterialFamilyContent,
  buildMaterialProofPointsContent,
  buildMaterialStoryContent,
  buildPageIntroContent,
  buildPilotProjectsContent,
  buildTechnicalDownloads,
  buildTechnicalDownloadsContent,
  buildTrustAndCredibilityContent,
  buildWhyItMattersContent,
  buildOpenSourceLegacyContent,
  resolveCmsHref,
  resolveLocalizedApiString,
  resolveLocalizedApiValue,
} from "@/lib/page-content"
import { resolveLocale } from "@/lib/resolve-locale"
import type { HomeSection, MaterialInfo } from "@/lib/types"

type MaterialPageProps = {
  params: Promise<{ locale: string }>
}

export default async function MaterialPage({ params }: MaterialPageProps) {
  const locale = await resolveLocale(params)
  const apiBaseUrl = await getServerApiBaseUrl()
  const messages = getMessages(locale)
  const certificationMessages = messages.certificationsAtAGlance
  const materialRequestOptions = {
    baseUrl: apiBaseUrl,
    locale,
  } as const

  let materialInfo: MaterialInfo | null = null
  let pageSections: HomeSection[] = []

  try {
    const response = await getMaterialInfo(materialRequestOptions)
    materialInfo = response.data
  } catch {
    materialInfo = null
  }

  try {
    pageSections = await getPageSections({ ...materialRequestOptions, page: "material" })
  } catch {
    pageSections = []
  }

  const introSection = findPageSection(pageSections, "intro")
  const materialFamilySection = findPageSection(pageSections, "material_family")
  const whyItMattersSection = findPageSection(pageSections, "why_it_matters")
  const materialStorySection = findPageSection(pageSections, "material_story")
  const openSourceLegacySection = findPageSection(pageSections, "open_source_legacy")
  const applicationsSection = findPageSection(pageSections, "applications")
  const materialFactsSection = findPageSection(pageSections, "material_facts")
  const proofPointsSection = findPageSection(pageSections, "proof_points")
  const certificationsSection = findPageSection(pageSections, "certifications")
  const technicalDownloadsSection = findPageSection(pageSections, "technical_downloads")
  const comparisonSection = findPageSection(pageSections, "comparison")
  const credibilitySection = findPageSection(pageSections, "credibility")
  const trustSection = findPageSection(pageSections, "trust_and_credibility")
  const pilotProjectsSection = findPageSection(pageSections, "pilot_projects")
  const collaborationSection = findPageSection(pageSections, "collaboration")
  const finalCtaSection = findPageSection(pageSections, "final_cta")
  const introFallback = messages.materialPage.intro
  const materialDetail = materialInfo ? materialInfoToDetail(materialInfo) : null
  const specs = hasPublishedCmsSection(materialFactsSection)
    ? materialInfo
      ? materialInfoToSpecs(materialInfo)
      : await getMaterialSpecs(locale, materialRequestOptions)
    : []
  const intro = hasPublishedCmsSection(introSection)
    ? buildPageIntroContent(
        introFallback,
        introSection,
        locale,
        `${getLocalizedHref(locale, "b2b")}?leadType=sample_request#inquiry`,
        getLocalizedHref(locale, "contact"),
      )
    : null
  const materialFamilyContent = hasPublishedCmsSection(materialFamilySection)
    ? buildMaterialFamilyContent(
        messages.home.materialFamily,
        materialFamilySection,
        locale,
      )
    : null
  const whyItMattersContent = hasPublishedCmsSection(whyItMattersSection)
    ? buildWhyItMattersContent(
        messages.home.whyItMatters,
        whyItMattersSection,
        locale,
        materialDetail,
      )
    : null
  const storyContent = hasPublishedCmsSection(materialStorySection)
    ? {
        ...buildMaterialStoryContent(
          messages.home.materialStory,
          materialDetail,
          locale,
          materialDetail?.story_sections.length ? null : materialStorySection,
        ),
        eyebrow: resolveLocalizedApiString(
          materialStorySection,
          "subtitle",
          locale,
          messages.home.materialStory.eyebrow,
        ),
        title: resolveLocalizedApiString(
          materialStorySection,
          "title",
          locale,
          materialInfo
            ? resolveLocalizedApiValue(
                materialInfo.process_steps.map((step) => step.title).join(" -> "),
                messages.home.materialStory.title,
                locale,
              )
            : messages.home.materialStory.title,
        ),
      }
    : null
  const openSourceLegacyContent = hasPublishedCmsSection(openSourceLegacySection)
    ? buildOpenSourceLegacyContent(
        messages.home.openSourceLegacy,
        openSourceLegacySection,
        locale,
      )
    : null
  const applicationsContent = hasPublishedCmsSection(applicationsSection)
    ? {
        ...buildApplicationsContent(
          messages.home.applications,
          materialDetail,
          locale,
          materialDetail?.applications.length ? null : applicationsSection,
        ),
        eyebrow: resolveLocalizedApiString(
          applicationsSection,
          "subtitle",
          locale,
          messages.home.applications.eyebrow,
        ),
        title: resolveLocalizedApiString(
          applicationsSection,
          "title",
          locale,
          messages.home.applications.title,
        ),
      }
    : null
  const materialFactsContent = hasPublishedCmsSection(materialFactsSection)
    ? buildMaterialFactsContent(
        messages.home.materialFacts,
        materialDetail,
        materialFactsSection,
        locale,
      )
    : null
  const proofPointsContent = hasPublishedCmsSection(proofPointsSection)
    ? buildMaterialProofPointsContent(
        messages.materialProof.proofPoints,
        proofPointsSection,
        locale,
      )
    : null
  const certificationsContent = hasPublishedCmsSection(certificationsSection)
    ? buildCertificationsContent(certificationMessages, certificationsSection, locale)
    : null
  const technicalDownloadsContent = hasPublishedCmsSection(technicalDownloadsSection)
    ? buildTechnicalDownloadsContent(
        messages.materialProof.technicalDownloads,
        technicalDownloadsSection,
        locale,
      )
    : null
  const downloads = hasPublishedCmsSection(technicalDownloadsSection)
    ? materialInfo?.technical_downloads?.length
      ? materialInfo.technical_downloads
      : buildTechnicalDownloads(technicalDownloadsSection, locale)
    : []
  const comparisonContent = hasPublishedCmsSection(comparisonSection)
    ? buildMaterialComparisonContent(
        messages.materialProof.comparison,
        comparisonSection,
        locale,
      )
    : null
  const credibilityContent = hasPublishedCmsSection(credibilitySection)
    ? buildCredibilityContent(
        messages.home.credibility,
        materialDetail,
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
  const collaborationContent = hasPublishedCmsSection(collaborationSection)
    ? buildCollaborationContent(
        messages.home.collaboration,
        collaborationSection,
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
          eyebrow={resolveLocalizedApiString(
            introSection,
            "subtitle",
            locale,
            intro.eyebrow,
          )}
          title={resolveLocalizedApiString(
            introSection,
            "title",
            locale,
            resolveLocalizedApiValue(materialInfo?.tagline, intro.title, locale),
          )}
          description={resolveLocalizedApiString(
            introSection,
            "content",
            locale,
            resolveLocalizedApiValue(materialInfo?.origin, intro.description, locale),
          )}
          primaryAction={{
            label: resolveLocalizedApiString(
              introSection,
              "cta_label",
              locale,
              intro.primaryCta,
            ),
            href: resolveCmsHref(
              locale,
              introSection?.cta_url,
              `${getLocalizedHref(locale, "b2b")}?leadType=sample_request#inquiry`,
            ),
          }}
          secondaryAction={{
            label: resolveLocalizedApiString(
              introSection?.payload,
              "secondary_cta_label",
              locale,
              intro.secondaryCta,
            ),
            href: resolveCmsHref(
              locale,
              typeof introSection?.payload?.secondary_cta_url === "string"
                ? introSection.payload.secondary_cta_url
                : null,
              getLocalizedHref(locale, "contact"),
            ),
          }}
        />
      ) : null}
      {materialFamilyContent ? (
        <MaterialFamilySection locale={locale} content={materialFamilyContent} />
      ) : null}
      {whyItMattersContent ? (
        <WhyItMattersSection content={whyItMattersContent} />
      ) : null}
      {storyContent ? <MaterialStorySection content={storyContent} /> : null}
      {openSourceLegacyContent ? (
        <OpenSourceLegacySection content={openSourceLegacyContent} />
      ) : null}
      {applicationsContent ? (
        <ApplicationsSection content={applicationsContent} />
      ) : null}
      {materialFactsContent ? (
        <MaterialFactsSection
          locale={locale}
          content={materialFactsContent}
          specs={specs}
          sheetHref={resolveCmsHref(
            locale,
            materialFactsSection?.cta_url,
            `${getLocalizedHref(locale, "b2b")}?leadType=sample_request#inquiry`,
          )}
        />
      ) : null}
      {proofPointsContent ? (
        <MaterialProofPointsSection content={proofPointsContent} />
      ) : null}
      {certificationsContent ? (
        <CertificationsAtAGlance
          certifications={
            certificationsContent.certifications ?? materialInfo?.certifications ?? []
          }
          eyebrow={certificationsContent.eyebrow}
          title={certificationsContent.title}
          description={certificationsContent.description}
          variant="material"
          verifiedLabel={certificationsContent.verifiedLabel}
          emptyMessage={certificationsContent.emptyMessage}
          statusLabels={certificationsContent.statusLabels}
          issuerLabel={certificationsContent.issuerLabel}
          testedAtLabel={certificationsContent.testedAtLabel}
          downloadLabel={certificationsContent.downloadLabel}
        />
      ) : null}
      {technicalDownloadsContent ? (
        <TechnicalDownloadsSection
          content={technicalDownloadsContent}
          downloads={downloads}
        />
      ) : null}
      {comparisonContent ? (
        <MaterialComparisonSection content={comparisonContent} />
      ) : null}
      {credibilityContent ? (
        <CredibilitySection content={credibilityContent} />
      ) : null}
      {trustContent ? <TrustAndCredibilitySection content={trustContent} /> : null}
      {pilotProjectsContent ? (
        <PilotProjectsSection content={pilotProjectsContent} />
      ) : null}
      {collaborationContent ? (
        <CollaborationSection
          locale={locale}
          content={collaborationContent}
          cardHrefs={collaborationContent.cardHrefs}
        />
      ) : null}
      {finalCtaContent ? (
        <FinalCtaSection locale={locale} content={finalCtaContent} />
      ) : null}
    </>
  )
}
