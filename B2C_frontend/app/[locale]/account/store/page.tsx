import { AccountStorePage } from "@/components/account/account-store-page"
import { resolveLocale } from "@/lib/resolve-locale"

type AccountStoreRoutePageProps = {
  params: Promise<{ locale: string }>
}

export default async function AccountStoreRoutePage({
  params,
}: AccountStoreRoutePageProps) {
  const locale = await resolveLocale(params)

  return <AccountStorePage locale={locale} />
}
