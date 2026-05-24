import { ApplicationsSection } from "@/components/sections/applications"
import { FinalCtaSection } from "@/components/sections/final-cta"
import { PageIntro } from "@/components/page-intro"
import { ProductGridSection } from "@/components/sections/product-grid"
import { StoreFaq } from "@/components/store/store-faq"
import { StoreTrustPanel } from "@/components/store/store-trust-panel"
import { getProducts } from "@/lib/api/products"
import { findPageSection, getPageSections } from "@/lib/api/page-sections"
import { getServerApiBaseUrl } from "@/lib/api/server-base-url"
import { hasPublishedCmsSection } from "@/lib/cms-section-visibility"
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
  let hasError = false

  try {
    storeSections = await getPageSections({ baseUrl: apiBaseUrl, locale, page: "store" })
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

  const storeSection = (key: string) => findPageSection(storeSections, key)
  const introSection = storeSection("intro")
  const productGridSection = storeSection("product_grid")
  const applicationsSection = storeSection("applications")
  const credibilitySection = storeSection("credibility")
  const faqSection = storeSection("store_faq")
  const finalCtaSection = storeSection("final_cta")
  const intro = hasPublishedCmsSection(introSection)
    ? buildPageIntroContent(
        messages.storePage.intro,
        introSection,
        locale,
        `${getLocalizedHref(locale, "store")}#catalogue`,
        getLocalizedHref(locale, "material"),
      )
    : null
  const productGridContent = hasPublishedCmsSection(productGridSection)
    ? buildStoreGridContent(messages.storePage.grid, productGridSection, locale)
    : null
  const applicationsContent = hasPublishedCmsSection(applicationsSection)
    ? buildApplicationsContent(
        messages.home.applications,
        null,
        locale,
        applicationsSection,
      )
    : null
  const credibilityContent = hasPublishedCmsSection(credibilitySection)
    ? buildCredibilityContent(
        messages.home.credibility,
        null,
        locale,
        credibilitySection,
      )
    : null
  const faqContent = hasPublishedCmsSection(faqSection)
    ? buildStoreFaqContent(messages.storePage.faq, faqSection, locale)
    : null
  const finalCtaContent = hasPublishedCmsSection(finalCtaSection)
    ? buildFinalCtaContent(messages.home.finalCta, finalCtaSection, locale)
    : null

  return (
    <>
      {intro ? (
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
      {productGridContent ? (
        <ProductGridSection
          locale={locale}
          content={productGridContent}
          products={catalogue?.items ?? []}
          filters={filters}
          meta={catalogue?.meta}
          hasError={hasError}
        />
      ) : null}
      {credibilityContent || faqContent ? (
        <section className="bg-background py-16 lg:py-20">
          <div className="mx-auto max-w-7xl space-y-10 px-6 lg:px-8">
            {credibilityContent ? (
              <StoreTrustPanel content={credibilityContent} />
            ) : null}
            {faqContent ? <StoreFaq content={faqContent} /> : null}
          </div>
        </section>
      ) : null}
      {applicationsContent ? (
        <ApplicationsSection content={applicationsContent} />
      ) : null}
      {finalCtaContent ? (
        <FinalCtaSection locale={locale} content={finalCtaContent} />
      ) : null}
    </>
  )
}
