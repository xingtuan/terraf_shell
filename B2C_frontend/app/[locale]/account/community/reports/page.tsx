import { AccountCommunityListPage } from "@/components/account/account-community-list-page"
import { resolveLocale } from "@/lib/resolve-locale"

type AccountCommunityReportsRoutePageProps = {
  params: Promise<{ locale: string }>
}

export default async function AccountCommunityReportsRoutePage({
  params,
}: AccountCommunityReportsRoutePageProps) {
  const locale = await resolveLocale(params)

  return <AccountCommunityListPage locale={locale} kind="reports" />
}
