import { ApplicationsSection } from "@/components/sections/applications"
import { FinalCtaSection } from "@/components/sections/final-cta"
import { ProductGridSection } from "@/components/sections/product-grid"
import { PageIntro } from "@/components/page-intro"
import { getProductCategories, getProducts } from "@/lib/api/products"
import { getServerApiBaseUrl } from "@/lib/api/server-base-url"
import { getLocalizedHref, getMessages } from "@/lib/i18n"
import { resolveLocale } from "@/lib/resolve-locale"
import type { Product, ProductCategory } from "@/lib/types"

type StorePageProps = {
  params: Promise<{ locale: string }>
}

export default async function StorePage({ params }: StorePageProps) {
  const locale = await resolveLocale(params)
  const apiBaseUrl = await getServerApiBaseUrl()
  const messages = getMessages(locale)
  const intro = messages.storePage.intro

  let products: Product[] = []
  let categories: ProductCategory[] = []
  let hasError = false

  try {
    ;[products, categories] = await Promise.all([
      getProducts(locale, {}, { baseUrl: apiBaseUrl }),
      getProductCategories(locale, { baseUrl: apiBaseUrl }),
    ])
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
        categories={categories}
        hasError={hasError}
      />
      <ApplicationsSection content={messages.home.applications} />
      <FinalCtaSection locale={locale} content={messages.home.finalCta} />
    </>
  )
}
