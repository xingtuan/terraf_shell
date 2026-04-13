import type { ApiPaginationMeta, MaterialSpecIcon } from "@/lib/types"

const DEFAULT_API_BASE_URL = "http://127.0.0.1:8000/api"

function getApiOrigin() {
  const apiBaseUrl = (process.env.NEXT_PUBLIC_API_BASE_URL ?? DEFAULT_API_BASE_URL).replace(
    /\/+$/,
    "",
  )

  return new URL(apiBaseUrl).origin
}

export function ensureArray<T>(value: T[] | null | undefined): T[] {
  return Array.isArray(value) ? value : []
}

export function normalizePaginationMeta(
  meta: Partial<ApiPaginationMeta> | undefined,
  fallbackLength = 0,
): ApiPaginationMeta {
  return {
    current_page: meta?.current_page ?? 1,
    per_page: meta?.per_page ?? fallbackLength,
    total: meta?.total ?? fallbackLength,
    last_page: meta?.last_page ?? 1,
  }
}

export function resolveApiUrl(url?: string | null) {
  if (!url) {
    return null
  }

  try {
    return new URL(url, `${getApiOrigin()}/`).toString()
  } catch {
    return url
  }
}

export function normalizeMaterialSpecIcon(icon?: string | null): MaterialSpecIcon {
  const normalizedIcon = icon?.trim().toLowerCase()

  if (!normalizedIcon) {
    return "feather"
  }

  if (
    normalizedIcon.includes("leaf") ||
    normalizedIcon.includes("eco") ||
    normalizedIcon.includes("nature")
  ) {
    return "leaf"
  }

  if (
    normalizedIcon.includes("shield") ||
    normalizedIcon.includes("durab") ||
    normalizedIcon.includes("strength")
  ) {
    return "shield"
  }

  if (
    normalizedIcon.includes("badge") ||
    normalizedIcon.includes("cert") ||
    normalizedIcon.includes("trace")
  ) {
    return "badge"
  }

  return "feather"
}

export function firstNonEmptyString(...values: Array<string | null | undefined>) {
  for (const value of values) {
    if (typeof value === "string" && value.trim()) {
      return value.trim()
    }
  }

  return null
}

export function stripHtml(value?: string | null) {
  if (!value) {
    return ""
  }

  return value.replace(/<[^>]+>/g, " ").replace(/\s+/g, " ").trim()
}
