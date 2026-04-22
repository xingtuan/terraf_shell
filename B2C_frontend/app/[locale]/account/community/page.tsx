import { AccountCommunityPage } from "@/components/account/account-community-page"
import { resolveLocale } from "@/lib/resolve-locale"

type AccountCommunityRoutePageProps = {
  params: Promise<{ locale: string }>
}

export default async function AccountCommunityRoutePage({
  params,
}: AccountCommunityRoutePageProps) {
  const locale = await resolveLocale(params)

  return <AccountCommunityPage locale={locale} />
}
