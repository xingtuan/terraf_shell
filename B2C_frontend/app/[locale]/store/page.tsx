import { ApplicationsSection } from "@/components/sections/applications"
import { FinalCtaSection } from "@/components/sections/final-cta"
import { PageIntro } from "@/components/page-intro"
import { ProductGridSection } from "@/components/sections/product-grid"
import { StoreFaq } from "@/components/store/store-faq"
import { StoreTrustPanel } from "@/components/store/store-trust-panel"
import { getProducts } from "@/lib/api/products"
import { findPageSection, getPageSections } from "@/lib/api/page-sections"
import { getServerApiBaseUrl } from "@/lib/api/server-base-url"
import { getLocalizedHref, getMessages } from "@/lib/i18n"
import {
  buildApplicationsContent,
  buildCredibilityContent,
  buildFinalCtaContent,
  buildPageIntroContent,
  buildStoreFaqContent,
  buildStoreGridContent,
} from "@/lib/page-content"
import { resolveLocale } from "@/lib/resolve-locale"
import { parseStoreCatalogFilters } from "@/lib/store/catalog"
import type { HomeSection, ProductCatalogResult } from "@/lib/types"

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
  const filters = parseStoreCatalogFilters(resolvedSearchParams)

  let catalogue: ProductCatalogResult | null = null
  let storeSections: HomeSection[] = []
  let sectionsLoaded = false
  let hasError = false

  try {
    storeSections = await getPageSections({ baseUrl: apiBaseUrl, locale, page: "store" })
    sectionsLoaded = true
  } catch {
    storeSections = []
  }

  try {
    catalogue = await getProducts({
      search: filters.search,
      sort: filters.sort,
      category: filters.category || undefined,
      stock_status: filters.stock_status || undefined,
      attributes:
        Object.keys(filters.attributes).length > 0 ? filters.attributes : undefined,
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

  const shouldUseCmsVisibility = sectionsLoaded && storeSections.length > 0
  const storeSection = (key: string) => findPageSection(storeSections, key)
  const shouldRender = (key: string) => !shouldUseCmsVisibility || Boolean(storeSection(key))
  const intro = buildPageIntroContent(
    messages.storePage.intro,
    shouldRender("intro") ? storeSection("intro") : null,
    locale,
    `${getLocalizedHref(locale, "store")}#catalogue`,
    getLocalizedHref(locale, "material"),
  )
  const productGridContent = buildStoreGridContent(
    messages.storePage.grid,
    shouldRender("product_grid") ? storeSection("product_grid") : null,
    locale,
  )
  const applicationsContent = buildApplicationsContent(
    messages.home.applications,
    null,
    locale,
    shouldRender("applications") ? storeSection("applications") : null,
  )
  const credibilityContent = buildCredibilityContent(
    messages.home.credibility,
    null,
    locale,
    shouldRender("credibility") ? storeSection("credibility") : null,
  )
  const faqContent = buildStoreFaqContent(
    messages.storePage.faq,
    shouldRender("store_faq") ? storeSection("store_faq") : null,
    locale,
  )
  const finalCtaContent = buildFinalCtaContent(
    messages.home.finalCta,
    shouldRender("final_cta") ? storeSection("final_cta") : null,
    locale,
  )

  return (
    <>
      {shouldRender("intro") ? (
        <PageIntro
          eyebrow={intro.eyebrow}
          title={intro.title}
          description={intro.description}
          primaryAction={{
            label: intro.primaryCta,
            href: intro.primaryHref ?? `${getLocalizedHref(locale, "store")}#catalogue`,
          }}
          secondaryAction={{
            label: intro.secondaryCta,
            href: intro.secondaryHref ?? getLocalizedHref(locale, "material"),
          }}
        />
      ) : null}
      {shouldRender("product_grid") ? (
        <ProductGridSection
          locale={locale}
          content={productGridContent}
          products={catalogue?.items ?? []}
          filters={filters}
          meta={catalogue?.meta}
          hasError={hasError}
        />
      ) : null}
      {shouldRender("credibility") || shouldRender("store_faq") ? (
        <section className="bg-background py-16 lg:py-20">
          <div className="mx-auto max-w-7xl space-y-10 px-6 lg:px-8">
            {shouldRender("credibility") ? (
              <StoreTrustPanel content={credibilityContent} />
            ) : null}
            {shouldRender("store_faq") ? <StoreFaq content={faqContent} /> : null}
          </div>
        </section>
      ) : null}
      {shouldRender("applications") ? (
        <ApplicationsSection content={applicationsContent} />
      ) : null}
      {shouldRender("final_cta") ? (
        <FinalCtaSection locale={locale} content={finalCtaContent} />
      ) : null}
    </>
  )
}
