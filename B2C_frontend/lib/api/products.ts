import {
  normalizeProduct,
  normalizeProductCatalogMeta,
} from "@/lib/api/adapters"
import { requestApi } from "@/lib/api/client"
import { ensureArray } from "@/lib/api/normalizers"
import { getIntlLocale, type Locale } from "@/lib/i18n"
import type {
  Product,
  ProductCatalogResult,
  ProductSortOption,
  ProductStockStatus,
} from "@/lib/types"

export type GetProductsParams = {
  search?: string
  sort?: ProductSortOption
  category?: string
  model?: string
  finish?: string
  color?: string
  stock_status?: ProductStockStatus
  use_case?: string
  price_min?: string | number
  price_max?: string | number
  page?: number
  per_page?: number
  locale?: Locale
  baseUrl?: string
}

type ApiRequestOverrides = {
  baseUrl?: string
  locale?: Locale
}

export async function getProducts(
  params: GetProductsParams = {},
): Promise<ProductCatalogResult> {
  const response = await requestApi<Product[]>("/products", {
    baseUrl: params.baseUrl,
    query: {
      search: params.search,
      sort: params.sort,
      category: params.category,
      model: params.model,
      finish: params.finish,
      color: params.color,
      stock_status: params.stock_status,
      use_case: params.use_case,
      price_min: params.price_min,
      price_max: params.price_max,
      page: params.page,
      per_page: params.per_page,
      locale: params.locale,
    },
  })

  const items = ensureArray(response.data).map((product) => normalizeProduct(product))

  return {
    items,
    meta: normalizeProductCatalogMeta(response.meta),
  }
}

export async function getProduct(
  slug: string,
  options: ApiRequestOverrides = {},
) {
  const response = await requestApi<Product>(`/products/${encodeURIComponent(slug)}`, {
    baseUrl: options.baseUrl,
    query: {
      locale: options.locale,
    },
  })

  return normalizeProduct(response.data)
}

export function formatCurrencyAmount(
  amount: string | number | null | undefined,
  locale: Locale,
  currency = "USD",
) {
  return new Intl.NumberFormat(getIntlLocale(locale), {
    style: "currency",
    currency,
    maximumFractionDigits: 2,
  }).format(Number(amount ?? 0))
}

export function formatProductPrice(
  product: Pick<Product, "price_usd" | "currency">,
  locale: Locale,
) {
  return formatCurrencyAmount(product.price_usd, locale, product.currency ?? "USD")
}
