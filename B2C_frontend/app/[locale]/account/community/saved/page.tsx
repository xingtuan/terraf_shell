import { AccountCommunityListPage } from "@/components/account/account-community-list-page"
import { resolveLocale } from "@/lib/resolve-locale"

type AccountCommunitySavedRoutePageProps = {
  params: Promise<{ locale: string }>
}

export default async function AccountCommunitySavedRoutePage({
  params,
}: AccountCommunitySavedRoutePageProps) {
  const locale = await resolveLocale(params)

  return <AccountCommunityListPage locale={locale} kind="saved" />
}
