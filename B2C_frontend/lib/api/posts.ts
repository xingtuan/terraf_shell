import { requestApi } from "@/lib/api/client"
import type { ApiPaginationMeta, CommunityPost } from "@/lib/types"

export type ListPostsParams = {
  sort?: "latest" | "hot"
  per_page?: number
}

export async function listPosts(
  params: ListPostsParams = {},
  token?: string | null,
): Promise<{ posts: CommunityPost[]; meta: ApiPaginationMeta }> {
  const response = await requestApi<CommunityPost[]>("/posts", {
    query: params,
    token,
  })

  return {
    posts: response.data,
    meta: response.meta ?? {
      current_page: 1,
      per_page: response.data.length,
      total: response.data.length,
      last_page: 1,
    },
  }
}

export async function getPost(identifier: string, token?: string | null) {
  const response = await requestApi<CommunityPost>(
    `/posts/${encodeURIComponent(identifier)}`,
    {
      token,
    },
  )

  return response.data
}
