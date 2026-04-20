import { CommunityProfilePage } from "@/components/community/community-profile-page"
import { FinalCtaSection } from "@/components/sections/final-cta"
import { getMessages } from "@/lib/i18n"
import { resolveLocale } from "@/lib/resolve-locale"

type CommunityProfileRoutePageProps = {
  params: Promise<{ locale: string; username: string }>
}

export default async function CommunityProfileRoutePage({
  params,
}: CommunityProfileRoutePageProps) {
  const locale = await resolveLocale(params)
  const resolvedParams = await params
  const messages = getMessages(locale)

  return (
    <>
      <CommunityProfilePage
        locale={locale}
        username={resolvedParams.username}
        messages={messages.community}
      />
      <FinalCtaSection locale={locale} content={messages.home.finalCta} />
    </>
  )
}
