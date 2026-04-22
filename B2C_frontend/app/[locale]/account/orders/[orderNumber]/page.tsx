import { AccountOrderDetailPage } from "@/components/account/account-order-detail-page"
import { resolveLocale } from "@/lib/resolve-locale"

type AccountOrderDetailRoutePageProps = {
  params: Promise<{ locale: string; orderNumber: string }>
}

export default async function AccountOrderDetailRoutePage({
  params,
}: AccountOrderDetailRoutePageProps) {
  const locale = await resolveLocale(params)
  const resolvedParams = await params

  return (
    <AccountOrderDetailPage
      locale={locale}
      orderNumber={resolvedParams.orderNumber}
    />
  )
}
