import { AccountOrdersPage } from "@/components/account/account-orders-page"
import { resolveLocale } from "@/lib/resolve-locale"

type AccountOrdersRoutePageProps = {
  params: Promise<{ locale: string }>
}

export default async function AccountOrdersRoutePage({
  params,
}: AccountOrdersRoutePageProps) {
  const locale = await resolveLocale(params)

  return <AccountOrdersPage locale={locale} />
}
