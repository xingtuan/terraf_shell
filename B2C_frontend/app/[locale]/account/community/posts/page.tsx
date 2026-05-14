import { AccountCommunityListPage } from "@/components/account/account-community-list-page"
import { resolveLocale } from "@/lib/resolve-locale"

type AccountCommunityPostsRoutePageProps = {
  params: Promise<{ locale: string }>
}

export default async function AccountCommunityPostsRoutePage({
  params,
}: AccountCommunityPostsRoutePageProps) {
  const locale = await resolveLocale(params)

  return <AccountCommunityListPage locale={locale} kind="posts" />
}
