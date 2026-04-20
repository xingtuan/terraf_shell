import { Suspense } from "react"

import { CommunityHub } from "@/components/community/community-hub"
import { ContentBlockSection } from "@/components/sections/content-block"
import { getPageContent } from "@/lib/api/content"
import { getServerApiBaseUrl } from "@/lib/api/server-base-url"
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
  const apiBaseUrl = await getServerApiBaseUrl()
  const content = await getPageContent("community", locale, { baseUrl: apiBaseUrl })

  return (
    <>
      <ContentBlockSection
        title={content.intro?.title}
        body={content.intro?.body}
      />
      <Suspense fallback={null}>
        <CommunityHub
          locale={locale}
          messages={messages.community}
          initialQuery={resolvedSearchParams.q}
        />
      </Suspense>
    </>
  )
}
