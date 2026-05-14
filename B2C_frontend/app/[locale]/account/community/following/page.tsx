import { AccountCommunityListPage } from "@/components/account/account-community-list-page"
import { resolveLocale } from "@/lib/resolve-locale"

type AccountCommunityFollowingRoutePageProps = {
  params: Promise<{ locale: string }>
}

export default async function AccountCommunityFollowingRoutePage({
  params,
}: AccountCommunityFollowingRoutePageProps) {
  const locale = await resolveLocale(params)

  return <AccountCommunityListPage locale={locale} kind="following" />
}
