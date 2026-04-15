import "server-only"

import { headers } from "next/headers"

import { getApiBaseUrl } from "@/lib/api/client"

export async function getServerApiBaseUrl() {
  const configuredApiBaseUrl = getApiBaseUrl()

  if (/^https?:\/\//i.test(configuredApiBaseUrl)) {
    return configuredApiBaseUrl
  }

  const requestHeaders = await headers()
  const host =
    requestHeaders.get("x-forwarded-host") ?? requestHeaders.get("host")

  if (!host) {
    return configuredApiBaseUrl
  }

  const protocol =
    requestHeaders.get("x-forwarded-proto") ??
    (host.includes("localhost") || host.startsWith("127.") ? "http" : "https")

  const normalizedBaseUrl = configuredApiBaseUrl.startsWith("/")
    ? configuredApiBaseUrl
    : `/${configuredApiBaseUrl}`

  return `${protocol}://${host}${normalizedBaseUrl}`.replace(/\/+$/, "")
}
