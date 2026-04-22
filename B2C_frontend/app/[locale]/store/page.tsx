import { ApplicationsSection } from "@/components/sections/applications"
import { FinalCtaSection } from "@/components/sections/final-cta"
import { ProductGridSection } from "@/components/sections/product-grid"
import { PageIntro } from "@/components/page-intro"
import { getProducts } from "@/lib/api/products"
import { getServerApiBaseUrl } from "@/lib/api/server-base-url"
import { getLocalizedHref, getMessages } from "@/lib/i18n"
import { resolveLocale } from "@/lib/resolve-locale"
import { parseStoreCatalogFilters } from "@/lib/store/catalog"
import type { ProductCatalogResult } from "@/lib/types"

type StorePageProps = {
  params: Promise<{ locale: string }>
  searchParams: Promise<Record<string, string | string[] | undefined>>
}

export default async function StorePage({
  params,
  searchParams,
}: StorePageProps) {
  const locale = await resolveLocale(params)
  const resolvedSearchParams = await searchParams
  const apiBaseUrl = await getServerApiBaseUrl()
  const messages = getMessages(locale)
  const intro = messages.storePage.intro
  const filters = parseStoreCatalogFilters(resolvedSearchParams)

  let catalogue: ProductCatalogResult | null = null
  let hasError = false

  try {
    catalogue = await getProducts({
      search: filters.search,
      sort: filters.sort,
      category: filters.category || undefined,
      model: filters.model || undefined,
      finish: filters.finish || undefined,
      color: filters.color || undefined,
      stock_status: filters.stock_status || undefined,
      use_case: filters.use_case || undefined,
      price_min: filters.price_min || undefined,
      price_max: filters.price_max || undefined,
      page: filters.page,
      per_page: 8,
      locale,
      baseUrl: apiBaseUrl,
    })
  } catch {
    hasError = true
  }

  return (
    <>
      <PageIntro
        eyebrow={intro.eyebrow}
        title={intro.title}
        description={intro.description}
        primaryAction={{
          label: intro.primaryCta,
          href: `${getLocalizedHref(locale, "store")}#catalogue`,
        }}
        secondaryAction={{
          label: intro.secondaryCta,
          href: getLocalizedHref(locale, "material"),
        }}
      />
      <ProductGridSection
        locale={locale}
        content={messages.storePage.grid}
        faqContent={messages.storePage.faq}
        trustContent={messages.home.credibility}
        products={catalogue?.items ?? []}
        filters={filters}
        meta={catalogue?.meta}
        hasError={hasError}
      />
      <ApplicationsSection content={messages.home.applications} />
      <FinalCtaSection locale={locale} content={messages.home.finalCta} />
    </>
  )
}
