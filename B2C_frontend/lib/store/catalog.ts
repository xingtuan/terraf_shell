import { getLocalizedHref, type Locale } from "@/lib/i18n"
import type { ProductSortOption, ProductStockStatus } from "@/lib/types"

export type StoreCatalogAttributeFilters = Record<
  string,
  string | { min?: string; max?: string }
>

export type StoreCatalogFilters = {
  search: string
  sort: ProductSortOption
  category: string
  stock_status: ProductStockStatus | ""
  attributes: StoreCatalogAttributeFilters
  price_min: string
  price_max: string
  page: number
}

export type StoreCatalogFilterChipKey =
  | "search"
  | "category"
  | "stock_status"
  | "price"
  | `attributes.${string}`

export const DEFAULT_STORE_CATALOG_FILTERS: StoreCatalogFilters = {
  search: "",
  sort: "featured",
  category: "",
  stock_status: "",
  attributes: {},
  price_min: "",
  price_max: "",
  page: 1,
}

function parseAttributeFilters(
  searchParams: Record<string, string | string[] | undefined>,
): StoreCatalogAttributeFilters {
  const attributes: StoreCatalogAttributeFilters = {}

  for (const [key, rawValue] of Object.entries(searchParams)) {
    const match = /^attributes\[([^\]]+)\](?:\[([^\]]+)\])?$/.exec(key)

    if (!match) {
      continue
    }

    const attributeKey = match[1]
    const rangeKey = match[2]
    const value = firstValue(rawValue).trim()

    if (value === "") {
      continue
    }

    if (rangeKey === "min" || rangeKey === "max") {
      const current =
        typeof attributes[attributeKey] === "object" && !Array.isArray(attributes[attributeKey])
          ? attributes[attributeKey]
          : {}

      attributes[attributeKey] = {
        ...current,
        [rangeKey]: value,
      }

      continue
    }

    attributes[attributeKey] = value
  }

  return attributes
}

const PRODUCT_SORT_OPTIONS: ProductSortOption[] = [
  "featured",
  "newest",
  "best_selling",
  "price_low_to_high",
  "price_high_to_low",
]

const PRODUCT_STOCK_STATUS_OPTIONS: ProductStockStatus[] = [
  "in_stock",
  "low_stock",
  "preorder",
  "made_to_order",
  "sold_out",
]

function firstValue(value?: string | string[]) {
  if (Array.isArray(value)) {
    return value[0] ?? ""
  }

  return value ?? ""
}

export function parseStoreCatalogFilters(
  searchParams: Record<string, string | string[] | undefined>,
): StoreCatalogFilters {
  const search = firstValue(searchParams.search).trim()
  const sortValue = firstValue(searchParams.sort).trim()
  const stockStatusValue = firstValue(searchParams.stock_status).trim()
  const pageValue = Number(firstValue(searchParams.page))

  return {
    ...DEFAULT_STORE_CATALOG_FILTERS,
    search,
    sort: PRODUCT_SORT_OPTIONS.includes(sortValue as ProductSortOption)
      ? (sortValue as ProductSortOption)
      : DEFAULT_STORE_CATALOG_FILTERS.sort,
    category: firstValue(searchParams.category).trim(),
    stock_status: PRODUCT_STOCK_STATUS_OPTIONS.includes(
      stockStatusValue as ProductStockStatus,
    )
      ? (stockStatusValue as ProductStockStatus)
      : DEFAULT_STORE_CATALOG_FILTERS.stock_status,
    attributes: parseAttributeFilters(searchParams),
    price_min: firstValue(searchParams.price_min).trim(),
    price_max: firstValue(searchParams.price_max).trim(),
    page: Number.isFinite(pageValue) && pageValue > 0 ? pageValue : DEFAULT_STORE_CATALOG_FILTERS.page,
  }
}

export function hasActiveStoreCatalogFilters(filters: StoreCatalogFilters) {
  return (
    filters.search !== "" ||
    filters.category !== "" ||
    filters.stock_status !== "" ||
    Object.keys(filters.attributes).length > 0 ||
    filters.price_min !== "" ||
    filters.price_max !== ""
  )
}

export function clearStoreCatalogFilters(
  overrides: Partial<StoreCatalogFilters> = {},
): Partial<StoreCatalogFilters> {
  return {
    ...DEFAULT_STORE_CATALOG_FILTERS,
    ...overrides,
  }
}

export function removeStoreCatalogFilter(
  filters: StoreCatalogFilters,
  key: StoreCatalogFilterChipKey,
): StoreCatalogFilters {
  const nextFilters = {
    ...filters,
    page: 1,
  }

  if (key === "price") {
    return {
      ...nextFilters,
      price_min: "",
      price_max: "",
    }
  }

  if (key.startsWith("attributes.")) {
    const attributeKey = key.slice("attributes.".length)
    const { [attributeKey]: _removed, ...attributes } = nextFilters.attributes

    return {
      ...nextFilters,
      attributes,
    }
  }

  return {
    ...nextFilters,
    [key]: "",
  }
}

export function buildStoreCatalogHref(
  locale: Locale,
  filters: Partial<StoreCatalogFilters>,
) {
  const url = new URL(getLocalizedHref(locale, "store"), "https://oxp.local")

  for (const [key, value] of Object.entries(filters)) {
    if (key === "attributes" && value && typeof value === "object" && !Array.isArray(value)) {
      for (const [attributeKey, attributeValue] of Object.entries(value)) {
        if (typeof attributeValue === "string" && attributeValue.trim() !== "") {
          url.searchParams.set(`attributes[${attributeKey}]`, attributeValue.trim())
        } else if (attributeValue && typeof attributeValue === "object") {
          for (const [rangeKey, rangeValue] of Object.entries(attributeValue)) {
            if (typeof rangeValue === "string" && rangeValue.trim() !== "") {
              url.searchParams.set(`attributes[${attributeKey}][${rangeKey}]`, rangeValue.trim())
            }
          }
        }
      }

      continue
    }

    if (key === "page" && (value === 1 || value === "1")) {
      continue
    }

    if (key === "sort" && value === DEFAULT_STORE_CATALOG_FILTERS.sort) {
      continue
    }

    if (typeof value === "number") {
      if (Number.isFinite(value) && value > 0) {
        url.searchParams.set(key, String(value))
      }

      continue
    }

    if (typeof value === "string" && value.trim() !== "") {
      url.searchParams.set(key, value.trim())
    }
  }

  url.hash = "catalogue"

  return `${url.pathname}${url.search}${url.hash}`
}
