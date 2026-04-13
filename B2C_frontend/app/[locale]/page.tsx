import { ArticleFeedSection } from "@/components/sections/article-feed"
import { ApplicationsSection } from "@/components/sections/applications"
import { CollaborationSection } from "@/components/sections/collaboration"
import { CredibilitySection } from "@/components/sections/credibility"
import { FinalCtaSection } from "@/components/sections/final-cta"
import { HeroSection } from "@/components/sections/hero"
import { MaterialFactsSection } from "@/components/sections/material-facts"
import { MaterialStorySection } from "@/components/sections/material-story"
import { WhyItMattersSection } from "@/components/sections/why-it-matters"
import { getHomepageContent, getHomeSections, findHomeSection } from "@/lib/api/homepage"
import { getFeaturedMaterial, getMaterial, getMaterialSpecs } from "@/lib/api/materials"
import { getLocalizedHref, getMessages } from "@/lib/i18n"
import {
  buildApplicationsContent,
  buildCredibilityContent,
  buildHeroContent,
  buildMaterialFactsContent,
  buildMaterialStoryContent,
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

async function loadHomepageData() {
  const [homepageResult, sectionsResult] = await Promise.allSettled([
    getHomepageContent(),
    getHomeSections(),
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
) {
  const requestedSlug = section?.payload?.material_slug

  if (typeof requestedSlug === "string" && requestedSlug.trim()) {
    try {
      return await getMaterial(requestedSlug)
    } catch {
      return null
    }
  }

  const fallbackSlug = homepage.materials[0]?.slug

  if (fallbackSlug) {
    try {
      return await getMaterial(fallbackSlug)
    } catch {
      // Continue to featured-material fallback.
    }
  }

  try {
    return await getFeaturedMaterial()
  } catch {
    return null
  }
}

export default async function LocaleHomePage({ params }: HomePageProps) {
  const locale = await resolveLocale(params)
  const messages = getMessages(locale)
  const { homepage, homeSections } = await loadHomepageData()
  const fallbackSpecs = await getMaterialSpecs(locale)

  const heroSection = findHomeSection(homeSections, "hero")
  const scienceSection = findHomeSection(homeSections, "science_block")
  const articlesSection = findHomeSection(homeSections, "latest_updates")
  const primaryMaterial = (await loadPrimaryMaterial(scienceSection, homepage)) as
    | MaterialDetail
    | null

  const heroContent = buildHeroContent(messages.home.hero, primaryMaterial, heroSection)
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
      <WhyItMattersSection content={messages.home.whyItMatters} />
      <MaterialStorySection content={storyContent} />
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
          eyebrow={articlesSection?.subtitle || "Latest Updates"}
          title={articlesSection?.title || "The newest material and studio updates."}
          description={
            articlesSection?.content ||
            "Published articles now come from the backend CMS and can be surfaced without rebuilding the frontend."
          }
          articles={homepage.articles}
          cta={{
            label: articlesSection?.cta_label || "View all updates",
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
