import { CommunityProfilePage } from "@/components/community/community-profile-page"
import { FinalCtaSection } from "@/components/sections/final-cta"
import { getServerApiBaseUrl } from "@/lib/api/server-base-url"
import { getUserProfile } from "@/lib/api/users"
import { getMessages } from "@/lib/i18n"
import { resolveLocale } from "@/lib/resolve-locale"
import type { UserProfile } from "@/lib/types"

type CommunityProfileRoutePageProps = {
  params: Promise<{ locale: string; username: string }>
}

export default async function CommunityProfileRoutePage({
  params,
}: CommunityProfileRoutePageProps) {
  const locale = await resolveLocale(params)
  const resolvedParams = await params
  const messages = getMessages(locale)
  const apiBaseUrl = await getServerApiBaseUrl()
  let initialProfile: UserProfile | null = null

  try {
    initialProfile = await getUserProfile(resolvedParams.username, null, {
      baseUrl: apiBaseUrl,
    })
  } catch {
    initialProfile = null
  }

  return (
    <>
      <CommunityProfilePage
        locale={locale}
        username={resolvedParams.username}
        messages={messages.community}
        initialProfile={initialProfile}
      />
      <FinalCtaSection locale={locale} content={messages.home.finalCta} />
    </>
  )
}
