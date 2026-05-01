import { getLocalizedHref, type Locale } from "@/lib/i18n"
import type { Product } from "@/lib/types"

function buildLeadHref(
  locale: Locale,
  leadType: string,
  product: Pick<Product, "slug" | "name" | "category">,
) {
  const url = new URL(getLocalizedHref(locale, "b2b"), "https://oxp.local")

  url.searchParams.set("leadType", leadType)
  url.searchParams.set("product", product.slug)
  url.searchParams.set("productName", product.name)

  if (product.category) {
    url.searchParams.set("category", product.category)
  }

  return `${url.pathname}${url.search}#inquiry`
}

export function getProductDetailHref(locale: Locale, slug: string) {
  return getLocalizedHref(locale, `store/${slug}`)
}

export function getProductInquiryHref(locale: Locale, product: Product) {
  return buildLeadHref(locale, "business_contact", product)
}

export function getProductSampleRequestHref(locale: Locale, product: Product) {
  return buildLeadHref(locale, "sample_request", product)
}

export function getProductDevelopmentHref(locale: Locale, product: Product) {
  return buildLeadHref(locale, "product_development_collaboration", product)
}
