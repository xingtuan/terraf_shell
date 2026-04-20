import {
  normalizeProduct,
  normalizeProductCategory,
} from "@/lib/api/adapters"
import { requestApi } from "@/lib/api/client"
import { ensureArray } from "@/lib/api/normalizers"
import {
  getIntlLocale,
  type Locale,
} from "@/lib/i18n"
import type { Product, ProductCategory } from "@/lib/types"

type ApiRequestOverrides = {
  baseUrl?: string
}

export type ListProductsParams = {
  category?: string | number
  featured?: boolean
  sort?: "sort_order" | "newest"
}

export async function getProductCategories(
  locale: Locale,
  options: ApiRequestOverrides = {},
): Promise<ProductCategory[]> {
  const response = await requestApi<ProductCategory[]>("/product-categories", {
    query: {
      locale,
    },
    baseUrl: options.baseUrl,
  })

  return ensureArray(response.data).map(normalizeProductCategory)
}

export async function getProducts(
  locale: Locale,
  params: ListProductsParams = {},
  options: ApiRequestOverrides = {},
): Promise<Product[]> {
  const response = await requestApi<Product[]>("/products", {
    query: {
      locale,
      ...params,
    },
    baseUrl: options.baseUrl,
  })

  return ensureArray(response.data).map(normalizeProduct)
}

export async function getFeaturedProducts(
  locale: Locale,
  options: ApiRequestOverrides = {},
) {
  const response = await requestApi<Product[]>("/products/featured", {
    query: {
      locale,
    },
    baseUrl: options.baseUrl,
  })

  return ensureArray(response.data).map(normalizeProduct)
}

export async function getProduct(
  identifier: string,
  locale: Locale,
  options: ApiRequestOverrides = {},
) {
  const response = await requestApi<Product>(
    `/products/${encodeURIComponent(identifier)}`,
    {
      query: {
        locale,
      },
      baseUrl: options.baseUrl,
    },
  )

  return normalizeProduct(response.data)
}

export function formatProductPrice(
  product: Pick<Product, "price_from" | "currency">,
  locale: Locale,
) {
  if (product.price_from === null || product.price_from === undefined) {
    return null
  }

  return new Intl.NumberFormat(getIntlLocale(locale), {
    style: "currency",
    currency: product.currency,
    maximumFractionDigits: product.currency === "KRW" ? 0 : 2,
  }).format(product.price_from)
}
