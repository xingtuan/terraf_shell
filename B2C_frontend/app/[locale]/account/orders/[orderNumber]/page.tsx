import { AccountOrderDetailPage } from "@/components/account/account-order-detail-page"
import { resolveLocale } from "@/lib/resolve-locale"

type AccountOrderDetailRoutePageProps = {
  params: Promise<{ locale: string; orderNumber: string }>
  searchParams?: Promise<{ submitted?: string }>
}

export default async function AccountOrderDetailRoutePage({
  params,
  searchParams,
}: AccountOrderDetailRoutePageProps) {
  const locale = await resolveLocale(params)
  const resolvedParams = await params
  const resolvedSearchParams = await searchParams

  return (
    <AccountOrderDetailPage
      locale={locale}
      orderNumber={resolvedParams.orderNumber}
      isSubmitted={resolvedSearchParams?.submitted === "1"}
    />
  )
}
