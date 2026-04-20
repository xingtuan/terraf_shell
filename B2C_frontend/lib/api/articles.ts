import {
  normalizeArticleDetail,
  normalizeArticleSummary,
} from "@/lib/api/adapters"
import { requestApi } from "@/lib/api/client"
import { ensureArray, normalizePaginationMeta } from "@/lib/api/normalizers"
import type { ArticleDetail, ArticleSummary, PaginatedResult } from "@/lib/types"

type ApiRequestOverrides = {
  baseUrl?: string
  locale?: string
}

export type ListArticlesParams = {
  category?: string
  per_page?: number
  locale?: string
}

export async function listArticles(
  params: ListArticlesParams = {},
  options: ApiRequestOverrides = {},
): Promise<PaginatedResult<ArticleSummary>> {
  const response = await requestApi<ArticleSummary[]>("/articles", {
    query: params,
    baseUrl: options.baseUrl,
  })

  const items = ensureArray(response.data).map(normalizeArticleSummary)

  return {
    items,
    meta: normalizePaginationMeta(response.meta, items.length),
  }
}

export async function getLatestArticles(
  limit = 3,
  options: ApiRequestOverrides = {},
) {
  const response = await listArticles({ per_page: limit }, options)

  return response.items
}

export async function getArticle(
  identifier: string,
  options: ApiRequestOverrides = {},
) {
  const response = await requestApi<ArticleDetail>(
    `/articles/${encodeURIComponent(identifier)}`,
    {
      query: {
        locale: options.locale,
      },
      baseUrl: options.baseUrl,
    },
  )

  return normalizeArticleDetail(response.data)
}
