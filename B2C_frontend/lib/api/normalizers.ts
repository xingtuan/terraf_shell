import { getApiBaseUrl } from "./client.ts"
import type { ApiPaginationMeta, MaterialSpecIcon } from "@/lib/types"

function getConfiguredMediaOrigin() {
  const mediaBaseUrl = process.env.NEXT_PUBLIC_MEDIA_BASE_URL?.trim()

  if (mediaBaseUrl && /^https?:\/\//i.test(mediaBaseUrl)) {
    try {
      return new URL(mediaBaseUrl).origin
    } catch {
      return null
    }
  }

  return null
}

function getApiOrigin() {
  const configuredMediaOrigin = getConfiguredMediaOrigin()

  if (configuredMediaOrigin) {
    return configuredMediaOrigin
  }

  const apiBaseUrl = getApiBaseUrl()

  if (/^https?:\/\//i.test(apiBaseUrl)) {
    return new URL(apiBaseUrl).origin
  }

  if (typeof window !== "undefined") {
    return window.location.origin
  }

  return null
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

  const legacyPublicMediaUrl = rewriteLegacyPublicMediaUrl(url)

  if (legacyPublicMediaUrl) {
    return legacyPublicMediaUrl
  }

  if (/^https?:\/\//i.test(url)) {
    return url
  }

  const apiOrigin = getApiOrigin()

  if (!apiOrigin) {
    return url
  }

  try {
    return new URL(url, `${apiOrigin}/`).toString()
  } catch {
    return url
  }
}

export function rewriteLegacyPublicMediaUrl(url?: string | null) {
  if (!url) {
    return null
  }

  const legacyPrefix = "/media/files/public/"
  const storagePrefix = "/storage/"
  const path = extractUrlPath(url)
  let storedPath: string | null = null

  if (path?.startsWith(legacyPrefix)) {
    storedPath = path.slice(legacyPrefix.length)
  } else if (path?.startsWith(storagePrefix)) {
    storedPath = path.slice(storagePrefix.length)
  }

  if (!storedPath) {
    return null
  }

  return buildPublicLocalMediaUrl(storedPath)
}

function extractUrlPath(url: string) {
  if (/^https?:\/\//i.test(url)) {
    try {
      return new URL(url).pathname
    } catch {
      return null
    }
  }

  return url.startsWith("/") ? url : null
}

function buildPublicLocalMediaUrl(storedPath: string) {
  const normalizedStoredPath = storedPath.replace(/^\/+/, "")
  const configuredMediaBaseUrl = process.env.NEXT_PUBLIC_MEDIA_BASE_URL?.trim()

  if (configuredMediaBaseUrl && /^https?:\/\//i.test(configuredMediaBaseUrl)) {
    try {
      const mediaBase = new URL(configuredMediaBaseUrl)
      mediaBase.pathname = `/storage/${normalizedStoredPath}`
      mediaBase.search = ""
      mediaBase.hash = ""

      return mediaBase.toString()
    } catch {
      // Fall through to API based URLs.
    }
  }

  const apiBaseUrl = getApiBaseUrl()
  const apiMediaPath = `/media/files/public/${normalizedStoredPath}`

  if (/^https?:\/\//i.test(apiBaseUrl)) {
    try {
      const apiBase = new URL(apiBaseUrl)
      const basePath = apiBase.pathname.replace(/\/+$/, "")
      const mediaPath = `${basePath}${apiMediaPath}`.replace(/\/{2,}/g, "/")

      apiBase.pathname = mediaPath
      apiBase.search = ""
      apiBase.hash = ""

      return apiBase.toString()
    } catch {
      // Fall through to relative URL.
    }
  }

  if (apiBaseUrl.startsWith("/")) {
    const basePath = apiBaseUrl.replace(/\/+$/, "")
    return `${basePath}${apiMediaPath}`.replace(/\/{2,}/g, "/")
  }

  const apiOrigin = getApiOrigin()
  const rewrittenPath = `/storage/${normalizedStoredPath}`

  if (!apiOrigin) {
    return rewrittenPath
  }

  try {
    return new URL(rewrittenPath, `${apiOrigin}/`).toString()
  } catch {
    return rewrittenPath
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
