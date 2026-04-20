import { requestApi } from "./client"
import type { MaterialContent, SiteSection } from "@/lib/types/content"

type ContentApiOptions = {
  baseUrl?: string
}

function localeHeader(locale: string) {
  return { "Accept-Language": locale }
}

export async function getPageContent(
  page: string,
  locale: string,
  options: ContentApiOptions = {},
) {
  const response = await requestApi<Record<string, SiteSection>>(`/content/${page}`, {
    headers: localeHeader(locale),
    query: { locale },
    baseUrl: options.baseUrl,
  })

  return response.data
}

export async function getMaterialContent(
  locale: string,
  options: ContentApiOptions = {},
) {
  const response = await requestApi<MaterialContent>("/materials", {
    headers: localeHeader(locale),
    query: { locale },
    baseUrl: options.baseUrl,
  })

  return response.data
}
