import { requestApi } from "@/lib/api/client"
import { getPageSections, findPageSection, type PageKey } from "@/lib/api/page-sections"
import { ensureArray } from "@/lib/api/normalizers"
import {
  normalizeArticleSummary,
  normalizeHomeSection,
  normalizeMaterialSummary,
} from "@/lib/api/adapters"
import type {
  ArticleSummary,
  HomeSection,
  HomepageContent,
  MaterialSummary,
} from "@/lib/types"

type ApiRequestOverrides = {
  baseUrl?: string
  locale?: string
  page?: PageKey
}

type HomepageApiPayload = {
  home_sections?: HomeSection[] | null
  materials?: MaterialSummary[] | null
  articles?: ArticleSummary[] | null
}

export async function getHomepageContent(
  options: ApiRequestOverrides = {},
) {
  const response = await requestApi<HomepageApiPayload>("/homepage", {
    query: {
      locale: options.locale,
    },
    baseUrl: options.baseUrl,
  })

  return {
    home_sections: ensureArray(response.data.home_sections).map(
      normalizeHomeSection,
    ),
    materials: ensureArray(response.data.materials).map(normalizeMaterialSummary),
    articles: ensureArray(response.data.articles).map(normalizeArticleSummary),
  } satisfies HomepageContent
}

export async function getHomeSections(options: ApiRequestOverrides = {}) {
  return getPageSections({
    baseUrl: options.baseUrl,
    locale: options.locale,
    page: options.page ?? "home",
  })
}

export function findHomeSection(sections: HomeSection[], key: string) {
  return findPageSection(sections, key)
}
