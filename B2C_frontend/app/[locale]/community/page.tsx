import { Suspense } from "react"

import { CommunityHub } from "@/components/community/community-hub"
import { PageIntro } from "@/components/page-intro"
import { FinalCtaSection } from "@/components/sections/final-cta"
import { CommunityIdeasSection } from "@/components/sections/community-ideas"
import { getCommunityIdeas } from "@/lib/api/community"
import { findPageSection, getPageSections } from "@/lib/api/page-sections"
import { getServerApiBaseUrl } from "@/lib/api/server-base-url"
import { hasPublishedCmsSection } from "@/lib/cms-section-visibility"
import { getLocalizedHref, getMessages } from "@/lib/i18n"
import {
  buildCommunityIdeasContent,
  buildFinalCtaContent,
  buildPageIntroContent,
} from "@/lib/page-content"
import { resolveLocale } from "@/lib/resolve-locale"
import type { HomeSection } from "@/lib/types"

type CommunityPageProps = {
  params: Promise<{ locale: string }>
  searchParams: Promise<{ q?: string }>
}

export default async function CommunityPage({
  params,
  searchParams,
}: CommunityPageProps) {
  const locale = await resolveLocale(params)
  const resolvedSearchParams = await searchParams
  const apiBaseUrl = await getServerApiBaseUrl()
  const messages = getMessages(locale)
  const ideas = await getCommunityIdeas(locale)
  let communitySections: HomeSection[] = []

  try {
    communitySections = await getPageSections({
      baseUrl: apiBaseUrl,
      locale,
      page: "community",
    })
  } catch {
    communitySections = []
  }

  const communitySection = (key: string) => findPageSection(communitySections, key)
  const introSection = communitySection("intro")
  const openConceptsSection = communitySection("open_concepts")
  const finalCtaSection = communitySection("final_cta")
  const intro = hasPublishedCmsSection(introSection)
    ? buildPageIntroContent(
        messages.communityPage.intro,
        introSection,
        locale,
        getLocalizedHref(locale, "community/new"),
        getLocalizedHref(locale, "community"),
      )
    : null
  const openConceptsContent = hasPublishedCmsSection(openConceptsSection)
    ? buildCommunityIdeasContent(
        messages.communityPage.ideas,
        openConceptsSection,
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
            href: intro.primaryHref ?? getLocalizedHref(locale, "community/new"),
          }}
          secondaryAction={{
            label: intro.secondaryCta,
            href: intro.secondaryHref ?? getLocalizedHref(locale, "community"),
          }}
        />
      ) : null}
      <Suspense fallback={null}>
        <CommunityHub
          locale={locale}
          messages={messages.community}
          initialQuery={resolvedSearchParams.q}
        />
      </Suspense>
      {openConceptsContent ? (
        <CommunityIdeasSection
          locale={locale}
          content={openConceptsContent}
          ideas={openConceptsContent.ideas ?? ideas}
        />
      ) : null}
      {finalCtaContent ? (
        <FinalCtaSection locale={locale} content={finalCtaContent} />
      ) : null}
    </>
  )
}
