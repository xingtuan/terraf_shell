import { AccountCommunityListPage } from "@/components/account/account-community-list-page"
import { resolveLocale } from "@/lib/resolve-locale"

type AccountCommunityCommentsRoutePageProps = {
  params: Promise<{ locale: string }>
}

export default async function AccountCommunityCommentsRoutePage({
  params,
}: AccountCommunityCommentsRoutePageProps) {
  const locale = await resolveLocale(params)

  return <AccountCommunityListPage locale={locale} kind="comments" />
}
