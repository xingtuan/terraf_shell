import { ArticleFeedSection } from "@/components/sections/article-feed"
import { ApplicationsSection } from "@/components/sections/applications"
import { AudiencePathsSection } from "@/components/sections/audience-paths"
import { BusinessPillarsSection } from "@/components/sections/business-pillars"
import { CollaborationSection } from "@/components/sections/collaboration"
import { CredibilitySection } from "@/components/sections/credibility"
import { FinalCtaSection } from "@/components/sections/final-cta"
import { HeroSection } from "@/components/sections/hero"
import { MaterialFactsSection } from "@/components/sections/material-facts"
import { MaterialStorySection } from "@/components/sections/material-story"
import { OpenSourceLegacySection } from "@/components/sections/open-source-legacy"
import {
  PilotProjectsSection,
  TrustAndCredibilitySection,
} from "@/components/sections/trust-and-b2b-sections"
import { WhyItMattersSection } from "@/components/sections/why-it-matters"
import { getHomepageContent, getHomeSections, findHomeSection } from "@/lib/api/homepage"
import { getFeaturedMaterial, getMaterial, getMaterialSpecs } from "@/lib/api/materials"
import { getServerApiBaseUrl } from "@/lib/api/server-base-url"
import { hasPublishedCmsSection } from "@/lib/cms-section-visibility"
import { getLocalizedHref, getMessages, type Locale } from "@/lib/i18n"
import {
  buildAudiencePathsContent,
  buildBusinessPillarsContent,
  buildCollaborationContent,
  buildApplicationsContent,
  buildCredibilityContent,
  buildFinalCtaContent,
  buildHeroContent,
  buildMaterialFactsContent,
  buildOpenSourceLegacyContent,
  buildPilotProjectsContent,
  buildMaterialStoryContent,
  buildTrustAndCredibilityContent,
  buildWhyItMattersContent,
  resolveLocalizedApiString,
  resolveCmsHref,
} from "@/lib/page-content"
import { resolveLocale } from "@/lib/resolve-locale"
import type { HomepageContent, HomeSection, MaterialDetail } from "@/lib/types"

type HomePageProps = {
  params: Promise<{ locale: string }>
}

const emptyHomepageContent: HomepageContent = {
  home_sections: [],
  materials: [],
  articles: [],
}

async function loadHomepageData(apiBaseUrl: string, locale: Locale) {
  const [homepageResult, sectionsResult] = await Promise.allSettled([
    getHomepageContent({ baseUrl: apiBaseUrl, locale }),
    getHomeSections({ baseUrl: apiBaseUrl, locale, page: "home" }),
  ])

  return {
    homepage:
      homepageResult.status === "fulfilled"
        ? homepageResult.value
        : emptyHomepageContent,
    homeSections:
      sectionsResult.status === "fulfilled" ? sectionsResult.value : ([] as HomeSection[]),
  }
}

async function loadPrimaryMaterial(
  section: HomeSection | null,
  homepage: HomepageContent,
  apiBaseUrl: string,
  locale: Locale,
) {
  const requestedSlug = section?.payload?.material_slug

  if (typeof requestedSlug === "string" && requestedSlug.trim()) {
    try {
      return await getMaterial(requestedSlug, { baseUrl: apiBaseUrl, locale })
    } catch {
      return null
    }
  }

  const fallbackSlug = homepage.materials[0]?.slug

  if (fallbackSlug) {
    try {
      return await getMaterial(fallbackSlug, { baseUrl: apiBaseUrl, locale })
    } catch {
      // Continue to featured-material fallback.
    }
  }

  try {
    return await getFeaturedMaterial({ baseUrl: apiBaseUrl, locale })
  } catch {
    return null
  }
}

export default async function LocaleHomePage({ params }: HomePageProps) {
  const locale = await resolveLocale(params)
  const apiBaseUrl = await getServerApiBaseUrl()
  const messages = getMessages(locale)
  const { homepage, homeSections } = await loadHomepageData(apiBaseUrl, locale)

  const heroSection = findHomeSection(homeSections, "hero")
  const audiencePathsSection = findHomeSection(homeSections, "audience_paths")
  const businessPillarsSection = findHomeSection(homeSections, "business_pillars")
  const whyItMattersSection = findHomeSection(homeSections, "why_it_matters")
  const materialStorySection = findHomeSection(homeSections, "material_story")
  const openSourceLegacySection = findHomeSection(homeSections, "open_source_legacy")
  const applicationsSection = findHomeSection(homeSections, "applications")
  const scienceSection = findHomeSection(homeSections, "science_block")
  const collaborationSection = findHomeSection(homeSections, "collaboration")
  const credibilitySection = findHomeSection(homeSections, "credibility")
  const trustSection = findHomeSection(homeSections, "trust_and_credibility")
  const articlesSection = findHomeSection(homeSections, "latest_updates")
  const pilotProjectsSection = findHomeSection(homeSections, "pilot_projects")
  const finalCtaSection = findHomeSection(homeSections, "final_cta")
  const primaryMaterial = (await loadPrimaryMaterial(
    scienceSection,
    homepage,
    apiBaseUrl,
    locale,
  )) as
    | MaterialDetail
    | null
  const materialFactSpecs = hasPublishedCmsSection(scienceSection)
    ? primaryMaterial?.specs.length
      ? primaryMaterial.specs
      : await getMaterialSpecs(locale, { baseUrl: apiBaseUrl, locale })
    : []

  const heroContent = hasPublishedCmsSection(heroSection)
    ? buildHeroContent(messages.home.hero, primaryMaterial, heroSection, locale)
    : null
  const audiencePathsContent = hasPublishedCmsSection(audiencePathsSection)
    ? buildAudiencePathsContent(
        messages.home.audiencePaths,
        audiencePathsSection,
        locale,
      )
    : null
  const businessPillarsContent = hasPublishedCmsSection(businessPillarsSection)
    ? buildBusinessPillarsContent(
        messages.home.businessPillars,
        businessPillarsSection,
        locale,
      )
    : null
  const whyItMattersContent = hasPublishedCmsSection(whyItMattersSection)
    ? buildWhyItMattersContent(
        messages.home.whyItMatters,
        whyItMattersSection,
        locale,
        primaryMaterial,
      )
    : null
  const storyContent = hasPublishedCmsSection(materialStorySection)
    ? buildMaterialStoryContent(
        messages.home.materialStory,
        primaryMaterial,
        locale,
        materialStorySection,
      )
    : null
  const openSourceLegacyContent = hasPublishedCmsSection(openSourceLegacySection)
    ? buildOpenSourceLegacyContent(
        messages.home.openSourceLegacy,
        openSourceLegacySection,
        locale,
      )
    : null
  const applicationsContent = hasPublishedCmsSection(applicationsSection)
    ? buildApplicationsContent(
        messages.home.applications,
        primaryMaterial,
        locale,
        applicationsSection,
      )
    : null
  const materialFactsContent = hasPublishedCmsSection(scienceSection)
    ? buildMaterialFactsContent(
        messages.home.materialFacts,
        primaryMaterial,
        scienceSection,
        locale,
      )
    : null
  const credibilityContent = hasPublishedCmsSection(credibilitySection)
    ? buildCredibilityContent(
        messages.home.credibility,
        primaryMaterial,
        locale,
        credibilitySection,
      )
    : null
  const collaborationContent = hasPublishedCmsSection(collaborationSection)
    ? buildCollaborationContent(
        messages.home.collaboration,
        collaborationSection,
        locale,
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
  const finalCtaContent = hasPublishedCmsSection(finalCtaSection)
    ? buildFinalCtaContent(messages.home.finalCta, finalCtaSection, locale)
    : null

  return (
    <>
      {heroContent ? (
        <HeroSection
          locale={locale}
          content={heroContent}
          primaryHref={resolveCmsHref(
            locale,
            heroSection?.cta_url,
            getLocalizedHref(locale, "material"),
          )}
          secondaryHref={resolveCmsHref(
            locale,
            typeof heroSection?.payload?.secondary_cta_url === "string"
              ? heroSection.payload.secondary_cta_url
              : null,
            getLocalizedHref(locale, "b2b"),
          )}
        />
      ) : null}
      {audiencePathsContent ? (
        <AudiencePathsSection locale={locale} content={audiencePathsContent} />
      ) : null}
      {businessPillarsContent ? (
        <BusinessPillarsSection content={businessPillarsContent} />
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
          specs={materialFactSpecs}
          sheetHref={resolveCmsHref(
            locale,
            scienceSection?.cta_url,
            `${getLocalizedHref(locale, "b2b")}?leadType=sample_request#inquiry`,
          )}
        />
      ) : null}
      {collaborationContent ? (
        <CollaborationSection
          locale={locale}
          content={collaborationContent}
          cardHrefs={collaborationContent.cardHrefs}
        />
      ) : null}
      {credibilityContent ? (
        <CredibilitySection content={credibilityContent} />
      ) : null}
      {trustContent ? <TrustAndCredibilitySection content={trustContent} /> : null}
      {pilotProjectsContent ? (
        <PilotProjectsSection content={pilotProjectsContent} />
      ) : null}
      {hasPublishedCmsSection(articlesSection) ? (
        <ArticleFeedSection
          locale={locale}
          eyebrow={resolveLocalizedApiString(
            articlesSection,
            "subtitle",
            locale,
            messages.articleFeed.defaultEyebrow,
          )}
          title={resolveLocalizedApiString(
            articlesSection,
            "title",
            locale,
            messages.articleFeed.defaultTitle,
          )}
          description={resolveLocalizedApiString(
            articlesSection,
            "content",
            locale,
            messages.articleFeed.defaultDescription,
          )}
          articles={homepage.articles}
          cta={{
            label: resolveLocalizedApiString(
              articlesSection,
              "cta_label",
              locale,
              messages.articleFeed.defaultCta,
            ),
            href: resolveCmsHref(
              locale,
              articlesSection.cta_url,
              getLocalizedHref(locale, "articles"),
            ),
          }}
        />
      ) : null}
      {finalCtaContent ? (
        <FinalCtaSection locale={locale} content={finalCtaContent} />
      ) : null}
    </>
  )
}
