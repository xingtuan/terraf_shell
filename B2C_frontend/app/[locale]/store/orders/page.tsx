import { StoreOrderLookupPage } from "@/components/store/store-order-lookup-page"
import { resolveLocale } from "@/lib/resolve-locale"

type StoreOrdersPageProps = {
  params: Promise<{ locale: string }>
  searchParams?: Promise<Record<string, string | string[] | undefined>>
}

function firstSearchValue(value: string | string[] | undefined) {
  return Array.isArray(value) ? value[0] : value
}

export default async function StoreOrdersPage({
  params,
  searchParams,
}: StoreOrdersPageProps) {
  const locale = await resolveLocale(params)
  const resolvedSearchParams = await searchParams
  const manualParam = firstSearchValue(resolvedSearchParams?.manual)
  const allowAuthenticatedManualLookup =
    manualParam === "1" || manualParam === "true"

  return (
    <StoreOrderLookupPage
      locale={locale}
      initialOrderNumber={firstSearchValue(resolvedSearchParams?.order)}
      initialToken={firstSearchValue(resolvedSearchParams?.token)}
      allowAuthenticatedManualLookup={allowAuthenticatedManualLookup}
    />
  )
}
