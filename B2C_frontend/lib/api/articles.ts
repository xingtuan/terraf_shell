import {
  normalizeArticleDetail,
  normalizeArticleSummary,
} from "@/lib/api/adapters"
import { requestApi } from "@/lib/api/client"
import { ensureArray, normalizePaginationMeta } from "@/lib/api/normalizers"
import type { ArticleDetail, ArticleSummary, PaginatedResult } from "@/lib/types"

export type ListArticlesParams = {
  category?: string
  per_page?: number
}

export async function listArticles(
  params: ListArticlesParams = {},
): Promise<PaginatedResult<ArticleSummary>> {
  const response = await requestApi<ArticleSummary[]>("/articles", {
    query: params,
  })

  const items = ensureArray(response.data).map(normalizeArticleSummary)

  return {
    items,
    meta: normalizePaginationMeta(response.meta, items.length),
  }
}

export async function getLatestArticles(limit = 3) {
  const response = await listArticles({ per_page: limit })

  return response.items
}

export async function getArticle(identifier: string) {
  const response = await requestApi<ArticleDetail>(
    `/articles/${encodeURIComponent(identifier)}`,
  )

  return normalizeArticleDetail(response.data)
}
