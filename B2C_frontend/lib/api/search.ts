import { normalizeCommunityPost } from "@/lib/api/adapters"
import { requestApi } from "@/lib/api/client"
import { ensureArray, normalizePaginationMeta } from "@/lib/api/normalizers"
import type { CommunityPost, SearchResultShape } from "@/lib/types"

export type SearchPostsParams = {
  q: string
  per_page?: number
}

export async function searchPosts(
  params: SearchPostsParams,
  token?: string | null,
): Promise<SearchResultShape> {
  const response = await requestApi<CommunityPost[]>("/search/posts", {
    query: params,
    token,
  })

  const posts = ensureArray(response.data).map(normalizeCommunityPost)

  return {
    query: params.q,
    posts,
    meta: normalizePaginationMeta(response.meta, posts.length),
  }
}
