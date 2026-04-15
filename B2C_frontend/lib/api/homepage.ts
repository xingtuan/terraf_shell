import { requestApi } from "@/lib/api/client"
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
  const response = await requestApi<HomeSection[]>("/home-sections", {
    baseUrl: options.baseUrl,
  })

  return ensureArray(response.data).map(normalizeHomeSection)
}

export function findHomeSection(sections: HomeSection[], key: string) {
  return sections.find((section) => section.key === key) ?? null
}
