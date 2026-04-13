import {
  normalizeCommunityCategory,
  normalizeCommunityPost,
  normalizeCommunityTag,
} from "@/lib/api/adapters"
import { requestApi } from "@/lib/api/client"
import { ensureArray, normalizePaginationMeta } from "@/lib/api/normalizers"
import type {
  ApiPaginationMeta,
  CommunityCategory,
  CommunityPost,
  CommunityTag,
} from "@/lib/types"

export type ListPostsParams = {
  q?: string
  category_id?: number
  category?: string
  user_id?: number
  tag?: string
  featured?: boolean
  pinned?: boolean
  mine?: boolean
  sort?:
    | "latest"
    | "hot"
    | "popular"
    | "trending"
    | "most_liked"
    | "most_commented"
    | "most_discussed"
  per_page?: number
}

export type UpsertPostPayload = {
  title: string
  content: string
  excerpt?: string
  category_id?: number | null
  tag_ids?: number[]
}

export async function listPosts(
  params: ListPostsParams = {},
  token?: string | null,
): Promise<{ posts: CommunityPost[]; meta: ApiPaginationMeta }> {
  const response = await requestApi<CommunityPost[]>("/posts", {
    query: params,
    token,
  })

  const posts = ensureArray(response.data).map(normalizeCommunityPost)

  return {
    posts,
    meta: normalizePaginationMeta(response.meta, posts.length),
  }
}

export async function getPost(identifier: string, token?: string | null) {
  const response = await requestApi<CommunityPost>(
    `/posts/${encodeURIComponent(identifier)}`,
    {
      token,
    },
  )

  return normalizeCommunityPost(response.data)
}

export async function createPost(payload: UpsertPostPayload, token: string) {
  const response = await requestApi<CommunityPost>("/posts", {
    method: "POST",
    token,
    body: payload,
  })

  return normalizeCommunityPost(response.data)
}

export async function updatePost(
  postId: number,
  payload: Partial<UpsertPostPayload>,
  token: string,
) {
  const response = await requestApi<CommunityPost>(`/posts/${postId}`, {
    method: "PATCH",
    token,
    body: payload,
  })

  return normalizeCommunityPost(response.data)
}

export async function deletePost(postId: number, token: string) {
  await requestApi<null>(`/posts/${postId}`, {
    method: "DELETE",
    token,
  })
}

export async function listCategories() {
  const response = await requestApi<CommunityCategory[]>("/categories")

  return ensureArray(response.data)
    .map(normalizeCommunityCategory)
    .filter((category): category is CommunityCategory => category !== null)
}

export async function listTags() {
  const response = await requestApi<CommunityTag[]>("/tags")

  return ensureArray(response.data).map(normalizeCommunityTag)
}
