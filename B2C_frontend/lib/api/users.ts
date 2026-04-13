import {
  normalizeCommunityComment,
  normalizeCommunityPost,
  normalizeCommunityUser,
} from "@/lib/api/adapters"
import { requestApi } from "@/lib/api/client"
import { ensureArray, normalizePaginationMeta } from "@/lib/api/normalizers"
import type {
  CommunityComment,
  CommunityPost,
  CommunityUser,
  FollowStatePayload,
  PaginatedResult,
} from "@/lib/types"

type ListUserContentParams = {
  per_page?: number
  sort?: "latest" | "hot" | "popular" | "trending" | "most_liked" | "most_commented" | "most_discussed"
}

type ListUserRelationsParams = {
  per_page?: number
}

async function getPaginatedPosts(
  path: string,
  params: ListUserContentParams = {},
  token?: string | null,
): Promise<PaginatedResult<CommunityPost>> {
  const response = await requestApi<CommunityPost[]>(path, {
    query: params,
    token,
  })
  const items = ensureArray(response.data).map(normalizeCommunityPost)

  return {
    items,
    meta: normalizePaginationMeta(response.meta, items.length),
  }
}

async function getPaginatedComments(
  path: string,
  params: ListUserContentParams = {},
  token?: string | null,
): Promise<PaginatedResult<CommunityComment>> {
  const response = await requestApi<CommunityComment[]>(path, {
    query: params,
    token,
  })
  const items = ensureArray(response.data).map(normalizeCommunityComment)

  return {
    items,
    meta: normalizePaginationMeta(response.meta, items.length),
  }
}

async function getPaginatedUsers(
  path: string,
  params: ListUserRelationsParams = {},
  token?: string | null,
): Promise<PaginatedResult<CommunityUser>> {
  const response = await requestApi<CommunityUser[]>(path, {
    query: params,
    token,
  })
  const items = ensureArray(response.data)
    .map(normalizeCommunityUser)
    .filter((user): user is CommunityUser => user !== null)

  return {
    items,
    meta: normalizePaginationMeta(response.meta, items.length),
  }
}

export async function getUserProfile(userId: number, token?: string | null) {
  const response = await requestApi<CommunityUser>(`/users/${userId}`, {
    token,
  })

  return normalizeCommunityUser(response.data)
}

export async function getUserPosts(
  userId: number,
  params: ListUserContentParams = {},
  token?: string | null,
) {
  return getPaginatedPosts(`/users/${userId}/posts`, params, token)
}

export async function getUserComments(
  userId: number,
  params: ListUserContentParams = {},
  token?: string | null,
) {
  return getPaginatedComments(`/users/${userId}/comments`, params, token)
}

export async function getUserFollowers(
  userId: number,
  params: ListUserRelationsParams = {},
  token?: string | null,
) {
  return getPaginatedUsers(`/users/${userId}/followers`, params, token)
}

export async function getUserFollowing(
  userId: number,
  params: ListUserRelationsParams = {},
  token?: string | null,
) {
  return getPaginatedUsers(`/users/${userId}/following`, params, token)
}

export async function toggleFollowUser(
  userId: number,
  isFollowing: boolean,
  token: string,
) {
  const response = await requestApi<FollowStatePayload>(`/users/${userId}/follow`, {
    method: isFollowing ? "DELETE" : "POST",
    token,
  })

  return response.data
}
