import { getLocalizedHref, type Locale } from "@/lib/i18n"
import type { ProductSortOption, ProductStockStatus } from "@/lib/types"

export type StoreCatalogFilters = {
  search: string
  sort: ProductSortOption
  category: string
  model: string
  finish: string
  color: string
  stock_status: ProductStockStatus | ""
  use_case: string
  price_min: string
  price_max: string
  page: number
}

export type StoreCatalogFilterChipKey =
  | "search"
  | "category"
  | "model"
  | "finish"
  | "color"
  | "stock_status"
  | "use_case"
  | "price"

export const DEFAULT_STORE_CATALOG_FILTERS: StoreCatalogFilters = {
  search: "",
  sort: "featured",
  category: "",
  model: "",
  finish: "",
  color: "",
  stock_status: "",
  use_case: "",
  price_min: "",
  price_max: "",
  page: 1,
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
    model: firstValue(searchParams.model).trim(),
    finish: firstValue(searchParams.finish).trim(),
    color: firstValue(searchParams.color).trim(),
    stock_status: PRODUCT_STOCK_STATUS_OPTIONS.includes(
      stockStatusValue as ProductStockStatus,
    )
      ? (stockStatusValue as ProductStockStatus)
      : DEFAULT_STORE_CATALOG_FILTERS.stock_status,
    use_case: firstValue(searchParams.use_case).trim(),
    price_min: firstValue(searchParams.price_min).trim(),
    price_max: firstValue(searchParams.price_max).trim(),
    page: Number.isFinite(pageValue) && pageValue > 0 ? pageValue : DEFAULT_STORE_CATALOG_FILTERS.page,
  }
}

export function hasActiveStoreCatalogFilters(filters: StoreCatalogFilters) {
  return (
    filters.search !== "" ||
    filters.category !== "" ||
    filters.model !== "" ||
    filters.finish !== "" ||
    filters.color !== "" ||
    filters.stock_status !== "" ||
    filters.use_case !== "" ||
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

  return {
    ...nextFilters,
    [key]: "",
  }
}

export function buildStoreCatalogHref(
  locale: Locale,
  filters: Partial<StoreCatalogFilters>,
) {
  const url = new URL(getLocalizedHref(locale, "store"), "https://shellfin.local")

  for (const [key, value] of Object.entries(filters)) {
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
