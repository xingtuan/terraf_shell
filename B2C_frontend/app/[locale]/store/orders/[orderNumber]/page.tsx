import { StoreOrderLookupPage } from "@/components/store/store-order-lookup-page"
import { resolveLocale } from "@/lib/resolve-locale"

type StoreOrderPageProps = {
  params: Promise<{ locale: string; orderNumber: string }>
  searchParams?: Promise<Record<string, string | string[] | undefined>>
}

function firstSearchValue(value: string | string[] | undefined) {
  return Array.isArray(value) ? value[0] : value
}

export default async function StoreOrderPage({
  params,
  searchParams,
}: StoreOrderPageProps) {
  const locale = await resolveLocale(params)
  const resolvedParams = await params
  const resolvedSearchParams = await searchParams

  return (
    <StoreOrderLookupPage
      locale={locale}
      initialOrderNumber={resolvedParams.orderNumber}
      initialToken={firstSearchValue(resolvedSearchParams?.token)}
    />
  )
}
