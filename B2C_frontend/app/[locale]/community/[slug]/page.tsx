import { CommunityPostDetail } from "@/components/community/community-post-detail"
import { FinalCtaSection } from "@/components/sections/final-cta"
import { getPost } from "@/lib/api/posts"
import { getServerApiBaseUrl } from "@/lib/api/server-base-url"
import { getMessages } from "@/lib/i18n"
import { resolveLocale } from "@/lib/resolve-locale"
import type { CommunityPost } from "@/lib/types"

type CommunityPostPageProps = {
  params: Promise<{ locale: string; slug: string }>
}

export default async function CommunityPostPage({
  params,
}: CommunityPostPageProps) {
  const locale = await resolveLocale(params)
  const resolvedParams = await params
  const messages = getMessages(locale)
  const apiBaseUrl = await getServerApiBaseUrl()
  let initialPost: CommunityPost | null = null

  try {
    initialPost = await getPost(resolvedParams.slug, {
      baseUrl: apiBaseUrl,
    })
  } catch {
    initialPost = null
  }

  return (
    <>
      <CommunityPostDetail
        locale={locale}
        slug={resolvedParams.slug}
        messages={messages.community}
        initialPost={initialPost}
      />
      <FinalCtaSection locale={locale} content={messages.home.finalCta} />
    </>
  )
}
