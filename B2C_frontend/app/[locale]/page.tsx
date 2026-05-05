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
import { WhyItMattersSection } from "@/components/sections/why-it-matters"
import { getHomepageContent, getHomeSections, findHomeSection } from "@/lib/api/homepage"
import { getFeaturedMaterial, getMaterial, getMaterialSpecs } from "@/lib/api/materials"
import { getServerApiBaseUrl } from "@/lib/api/server-base-url"
import { getLocalizedHref, getMessages, type Locale } from "@/lib/i18n"
import {
  buildApplicationsContent,
  buildCredibilityContent,
  buildHeroContent,
  buildMaterialFactsContent,
  buildMaterialStoryContent,
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
    getHomeSections({ baseUrl: apiBaseUrl, locale }),
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
  const scienceSection = findHomeSection(homeSections, "science_block")
  const articlesSection = findHomeSection(homeSections, "latest_updates")
  const primaryMaterial = (await loadPrimaryMaterial(
    scienceSection,
    homepage,
    apiBaseUrl,
    locale,
  )) as
    | MaterialDetail
    | null
  const fallbackSpecs =
    primaryMaterial?.specs.length
      ? primaryMaterial.specs
      : await getMaterialSpecs(locale, { baseUrl: apiBaseUrl, locale })

  const heroContent = buildHeroContent(
    messages.home.hero,
    primaryMaterial,
    heroSection,
    locale,
  )
  const storyContent = buildMaterialStoryContent(
    messages.home.materialStory,
    primaryMaterial,
  )
  const applicationsContent = buildApplicationsContent(
    messages.home.applications,
    primaryMaterial,
  )
  const materialFactsContent = buildMaterialFactsContent(
    messages.home.materialFacts,
    primaryMaterial,
    scienceSection,
    locale,
  )
  const credibilityContent = buildCredibilityContent(
    messages.home.credibility,
    primaryMaterial,
  )

  return (
    <>
      <HeroSection
        locale={locale}
        content={heroContent}
        primaryHref={resolveCmsHref(
          locale,
          heroSection?.cta_url,
          getLocalizedHref(locale, "material"),
        )}
      />
      <AudiencePathsSection locale={locale} content={messages.home.audiencePaths} />
      <BusinessPillarsSection content={messages.home.businessPillars} />
      <WhyItMattersSection content={messages.home.whyItMatters} />
      <MaterialStorySection content={storyContent} />
      <OpenSourceLegacySection content={messages.home.openSourceLegacy} />
      <ApplicationsSection content={applicationsContent} />
      <MaterialFactsSection
        locale={locale}
        content={materialFactsContent}
        specs={primaryMaterial?.specs.length ? primaryMaterial.specs : fallbackSpecs}
        sheetHref={resolveCmsHref(
          locale,
          scienceSection?.cta_url,
          `${getLocalizedHref(locale, "b2b")}?leadType=sample_request#inquiry`,
        )}
      />
      <CollaborationSection
        locale={locale}
        content={messages.home.collaboration}
        cardHrefs={[
          `${getLocalizedHref(locale, "b2b")}#inquiry`,
          `${getLocalizedHref(locale, "b2b")}?leadType=sample_request#inquiry`,
          `${getLocalizedHref(locale, "b2b")}?leadType=product_development_collaboration#inquiry`,
        ]}
      />
      <CredibilitySection content={credibilityContent} />
      {(homepage.articles.length > 0 || articlesSection) ? (
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
          description={
            resolveLocalizedApiString(
              articlesSection,
              "content",
              locale,
              messages.articleFeed.defaultDescription,
            )
          }
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
              articlesSection?.cta_url,
              getLocalizedHref(locale, "articles"),
            ),
          }}
        />
      ) : null}
      <FinalCtaSection locale={locale} content={messages.home.finalCta} />
    </>
  )
}
