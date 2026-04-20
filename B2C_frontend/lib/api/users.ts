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

type UserIdentifier = string | number

type ListUserContentParams = {
  page?: number
  per_page?: number
  sort?:
    | "latest"
    | "hot"
    | "popular"
    | "trending"
    | "most_liked"
    | "most_commented"
    | "most_discussed"
}

type ListUserRelationsParams = {
  page?: number
  per_page?: number
}

function buildUserPath(identifier: UserIdentifier) {
  return `/users/${encodeURIComponent(String(identifier))}`
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

export async function getUserProfile(
  username: UserIdentifier,
  token?: string | null,
) {
  const response = await requestApi<CommunityUser>(buildUserPath(username), {
    token,
  })

  return normalizeCommunityUser(response.data)
}

export async function getUserPosts(
  username: UserIdentifier,
  pageOrParams: number | ListUserContentParams = {},
  token?: string | null,
) {
  const params =
    typeof pageOrParams === "number"
      ? ({ page: pageOrParams } satisfies ListUserContentParams)
      : pageOrParams

  return getPaginatedPosts(`${buildUserPath(username)}/posts`, params, token)
}

export async function getUserComments(
  username: UserIdentifier,
  params: ListUserContentParams = {},
  token?: string | null,
) {
  return getPaginatedComments(`${buildUserPath(username)}/comments`, params, token)
}

export async function getUserFollowers(
  username: UserIdentifier,
  params: ListUserRelationsParams = {},
  token?: string | null,
) {
  return getPaginatedUsers(`${buildUserPath(username)}/followers`, params, token)
}

export async function getUserFollowing(
  username: UserIdentifier,
  params: ListUserRelationsParams = {},
  token?: string | null,
) {
  return getPaginatedUsers(`${buildUserPath(username)}/following`, params, token)
}

export async function followUser(username: UserIdentifier, token: string) {
  const response = await requestApi<FollowStatePayload>(
    `${buildUserPath(username)}/follow`,
    {
      method: "POST",
      token,
    },
  )

  return response.data
}

export async function unfollowUser(username: UserIdentifier, token: string) {
  const response = await requestApi<FollowStatePayload>(
    `${buildUserPath(username)}/follow`,
    {
      method: "DELETE",
      token,
    },
  )

  return response.data
}

export async function toggleFollowUser(
  username: UserIdentifier,
  isFollowing: boolean,
  token: string,
) {
  return isFollowing ? unfollowUser(username, token) : followUser(username, token)
}
