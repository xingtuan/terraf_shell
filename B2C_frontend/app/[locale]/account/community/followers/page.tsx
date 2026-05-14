import { AccountCommunityListPage } from "@/components/account/account-community-list-page"
import { resolveLocale } from "@/lib/resolve-locale"

type AccountCommunityFollowersRoutePageProps = {
  params: Promise<{ locale: string }>
}

export default async function AccountCommunityFollowersRoutePage({
  params,
}: AccountCommunityFollowersRoutePageProps) {
  const locale = await resolveLocale(params)

  return <AccountCommunityListPage locale={locale} kind="followers" />
}
