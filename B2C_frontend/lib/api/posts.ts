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

type ApiRequestOverrides = {
  baseUrl?: string
}

export type ListPostsParams = {
  page?: number
  q?: string
  search?: string
  category_id?: number
  category?: string
  user_id?: number
  liked_by?: string
  favorited_by?: string
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

export type PostFormPayload = {
  title: string
  category_id?: number | null
  tags?: string | string[]
  content: string
  content_json?: string | null
  funding_url?: string | null
  images?: File[]
  attachments?: File[]
  excerpt?: string | null
  cover_image_url?: string | null
  cover_image_path?: string | null
  tag_ids?: number[]
}

export type UpsertPostPayload = PostFormPayload

function buildPostBody(payload: Partial<PostFormPayload>) {
  const hasImages = Array.isArray(payload.images) && payload.images.length > 0
  const hasAttachments =
    Array.isArray(payload.attachments) && payload.attachments.length > 0

  if (!hasImages && !hasAttachments) {
    return {
      ...(payload.title !== undefined ? { title: payload.title } : {}),
      ...(payload.category_id !== undefined
        ? { category_id: payload.category_id }
        : {}),
      ...(payload.tags !== undefined ? { tags: payload.tags } : {}),
      ...(payload.content !== undefined ? { content: payload.content } : {}),
      ...(payload.content_json !== undefined
        ? { content_json: payload.content_json }
        : {}),
      ...(payload.funding_url !== undefined
        ? { funding_url: payload.funding_url }
        : {}),
      ...(payload.excerpt !== undefined ? { excerpt: payload.excerpt } : {}),
      ...(payload.cover_image_url !== undefined
        ? { cover_image_url: payload.cover_image_url }
        : {}),
      ...(payload.cover_image_path !== undefined
        ? { cover_image_path: payload.cover_image_path }
        : {}),
      ...(payload.tag_ids !== undefined ? { tag_ids: payload.tag_ids } : {}),
    }
  }

  const form = new FormData()

  if (payload.title !== undefined) {
    form.append("title", payload.title)
  }

  if (payload.category_id !== undefined) {
    form.append(
      "category_id",
      payload.category_id === null ? "" : String(payload.category_id),
    )
  }

  if (payload.tags !== undefined) {
    if (Array.isArray(payload.tags)) {
      payload.tags.forEach((tag) => form.append("tags[]", tag))
    } else {
      form.append("tags", payload.tags ?? "")
    }
  }

  if (payload.content !== undefined) {
    form.append("content", payload.content)
  }

  if (payload.content_json !== undefined) {
    form.append("content_json", payload.content_json ?? "")
  }

  if (payload.funding_url !== undefined) {
    form.append("funding_url", payload.funding_url ?? "")
  }

  if (payload.excerpt !== undefined) {
    form.append("excerpt", payload.excerpt ?? "")
  }

  if (payload.cover_image_url !== undefined) {
    form.append("cover_image_url", payload.cover_image_url ?? "")
  }

  if (payload.cover_image_path !== undefined) {
    form.append("cover_image_path", payload.cover_image_path ?? "")
  }

  if (payload.tag_ids) {
    payload.tag_ids.forEach((tagId) => form.append("tag_ids[]", String(tagId)))
  }

  payload.images?.forEach((image) => form.append("images[]", image))
  payload.attachments?.forEach((attachment) =>
    form.append("attachments[]", attachment),
  )

  return form
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

type GetPostOptions = ApiRequestOverrides & {
  token?: string | null
}

export async function getPost(
  identifier: string,
  options: GetPostOptions = {},
) {
  const response = await requestApi<CommunityPost>(
    `/posts/${encodeURIComponent(identifier)}`,
    {
      token: options.token,
      baseUrl: options.baseUrl,
    },
  )

  return normalizeCommunityPost(response.data)
}

export async function createPost(payload: PostFormPayload, token: string) {
  const response = await requestApi<CommunityPost>("/posts", {
    method: "POST",
    token,
    body: buildPostBody(payload),
  })

  return normalizeCommunityPost(response.data)
}

export async function updatePost(
  postId: number,
  payload: Partial<PostFormPayload>,
  token: string,
) {
  const response = await requestApi<CommunityPost>(`/posts/${postId}`, {
    method: "PUT",
    token,
    body: buildPostBody(payload),
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
