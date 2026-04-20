import { requestApi } from "@/lib/api/client"
import { getIntlLocale, type Locale } from "@/lib/i18n"
import type { Product } from "@/lib/types"

type GetProductsParams = {
  category?: string
  model?: string
  color?: string
  page?: number
  per_page?: number
  baseUrl?: string
}

type ApiRequestOverrides = {
  baseUrl?: string
}

export async function getProducts(params: GetProductsParams = {}) {
  const query = new URLSearchParams()

  if (params.category) query.set("category", params.category)
  if (params.model) query.set("model", params.model)
  if (params.color) query.set("color", params.color)
  if (params.page) query.set("page", String(params.page))
  if (params.per_page) query.set("per_page", String(params.per_page))

  const path = query.toString() ? `/products?${query.toString()}` : "/products"

  return requestApi<Product[]>(path, {
    baseUrl: params.baseUrl,
  })
}

export async function getProduct(
  slug: string,
  options: ApiRequestOverrides = {},
) {
  return requestApi<Product>(`/products/${encodeURIComponent(slug)}`, {
    baseUrl: options.baseUrl,
  })
}

export function formatProductPrice(
  product: Pick<Product, "price_usd">,
  locale: Locale,
) {
  return new Intl.NumberFormat(getIntlLocale(locale), {
    style: "currency",
    currency: "USD",
    maximumFractionDigits: 2,
  }).format(Number(product.price_usd))
}
