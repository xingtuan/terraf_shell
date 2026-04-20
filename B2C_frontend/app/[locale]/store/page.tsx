import { ApplicationsSection } from "@/components/sections/applications"
import { FinalCtaSection } from "@/components/sections/final-cta"
import { ProductGridSection } from "@/components/sections/product-grid"
import { PageIntro } from "@/components/page-intro"
import { getProducts } from "@/lib/api/products"
import { getServerApiBaseUrl } from "@/lib/api/server-base-url"
import { getLocalizedHref, getMessages } from "@/lib/i18n"
import { resolveLocale } from "@/lib/resolve-locale"
import type { Product } from "@/lib/types"

type StorePageProps = {
  params: Promise<{ locale: string }>
  searchParams: Promise<{ category?: string | string[] }>
}

const allowedCategories = new Set([
  "tableware",
  "planters",
  "wellness_interior",
  "architectural",
])

export default async function StorePage({
  params,
  searchParams,
}: StorePageProps) {
  const locale = await resolveLocale(params)
  const resolvedSearchParams = await searchParams
  const apiBaseUrl = await getServerApiBaseUrl()
  const messages = getMessages(locale)
  const intro = messages.storePage.intro

  const rawCategory = Array.isArray(resolvedSearchParams.category)
    ? resolvedSearchParams.category[0]
    : resolvedSearchParams.category
  const activeCategory =
    rawCategory && allowedCategories.has(rawCategory) ? rawCategory : undefined

  let products: Product[] = []
  let hasError = false

  try {
    const response = await getProducts({
      category: activeCategory,
      per_page: 12,
      baseUrl: apiBaseUrl,
    })
    products = response.data
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
          href: `${getLocalizedHref(locale, "store")}#products`,
        }}
        secondaryAction={{
          label: intro.secondaryCta,
          href: getLocalizedHref(locale, "material"),
        }}
      />
      <ProductGridSection
        locale={locale}
        header={messages.header}
        content={messages.storePage.grid}
        products={products}
        activeCategory={activeCategory}
        hasError={hasError}
      />
      <ApplicationsSection content={messages.home.applications} />
      <FinalCtaSection locale={locale} content={messages.home.finalCta} />
    </>
  )
}
