import { Suspense } from "react"

import { CommunityHub } from "@/components/community/community-hub"
import { FinalCtaSection } from "@/components/sections/final-cta"
import { CommunityIdeasSection } from "@/components/sections/community-ideas"
import { getCommunityIdeas } from "@/lib/api/community"
import { getMessages } from "@/lib/i18n"
import { resolveLocale } from "@/lib/resolve-locale"

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
  const messages = getMessages(locale)
  const ideas = await getCommunityIdeas(locale)

  return (
    <>
      <Suspense fallback={null}>
        <CommunityHub
          locale={locale}
          messages={messages.community}
          initialQuery={resolvedSearchParams.q}
        />
      </Suspense>
      <CommunityIdeasSection
        locale={locale}
        content={messages.communityPage.ideas}
        ideas={ideas}
      />
      <FinalCtaSection locale={locale} content={messages.home.finalCta} />
    </>
  )
}
