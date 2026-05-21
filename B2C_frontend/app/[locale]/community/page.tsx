import { Suspense } from "react"

import { CommunityHub } from "@/components/community/community-hub"
import { PageIntro } from "@/components/page-intro"
import { FinalCtaSection } from "@/components/sections/final-cta"
import { CommunityIdeasSection } from "@/components/sections/community-ideas"
import { getCommunityIdeas } from "@/lib/api/community"
import { findPageSection, getPageSections } from "@/lib/api/page-sections"
import { getServerApiBaseUrl } from "@/lib/api/server-base-url"
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
  let sectionsLoaded = false

  try {
    communitySections = await getPageSections({
      baseUrl: apiBaseUrl,
      locale,
      page: "community",
    })
    sectionsLoaded = true
  } catch {
    communitySections = []
  }

  const shouldUseCmsVisibility = sectionsLoaded && communitySections.length > 0
  const communitySection = (key: string) => findPageSection(communitySections, key)
  const shouldRender = (key: string) =>
    !shouldUseCmsVisibility || Boolean(communitySection(key))
  const intro = buildPageIntroContent(
    messages.communityPage.intro,
    shouldRender("intro") ? communitySection("intro") : null,
    locale,
    getLocalizedHref(locale, "community/new"),
    getLocalizedHref(locale, "community"),
  )
  const openConceptsContent = buildCommunityIdeasContent(
    messages.communityPage.ideas,
    shouldRender("open_concepts") ? communitySection("open_concepts") : null,
    locale,
  )
  const finalCtaContent = buildFinalCtaContent(
    messages.home.finalCta,
    shouldRender("final_cta") ? communitySection("final_cta") : null,
    locale,
  )

  return (
    <>
      {shouldRender("intro") ? (
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
      {shouldRender("open_concepts") ? (
        <CommunityIdeasSection
          locale={locale}
          content={openConceptsContent}
          ideas={ideas}
        />
      ) : null}
      {shouldRender("final_cta") ? (
        <FinalCtaSection locale={locale} content={finalCtaContent} />
      ) : null}
    </>
  )
}
