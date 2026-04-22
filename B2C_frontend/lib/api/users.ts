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
  UserProfile,
} from "@/lib/types"

type UserIdentifier = string | number

type ApiRequestOverrides = {
  baseUrl?: string
}

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

export type UpdateProfilePayload = {
  name?: string
  username?: string
  email?: string
  bio?: string
  location?: string
  website?: string
  school_or_company?: string
  region?: string
  portfolio_url?: string
  open_to_collab?: boolean
  avatar_url?: string | null
  avatar_path?: string | null
}

function buildUserPath(identifier: UserIdentifier) {
  return `/users/${encodeURIComponent(String(identifier))}`
}

function normalizeUserProfile(user?: CommunityUser | null): UserProfile | null {
  const normalized = normalizeCommunityUser(user)

  if (!normalized) {
    return null
  }

  return {
    ...normalized,
    bio: normalized.profile?.bio ?? null,
    joined_at: normalized.created_at ?? null,
  }
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
  options: ApiRequestOverrides = {},
) {
  const response = await requestApi<CommunityUser>(buildUserPath(username), {
    token,
    baseUrl: options.baseUrl,
  })

  return normalizeUserProfile(response.data)
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

export async function getUserFavorites(
  username: UserIdentifier,
  pageOrParams: number | ListUserContentParams = {},
  token?: string | null,
) {
  const params =
    typeof pageOrParams === "number"
      ? ({ page: pageOrParams } satisfies ListUserContentParams)
      : pageOrParams

  return getPaginatedPosts(`${buildUserPath(username)}/favorites`, params, token)
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

export async function updateProfile(payload: UpdateProfilePayload, token: string) {
  const response = await requestApi<CommunityUser>("/auth/profile", {
    method: "PUT",
    token,
    body: payload,
  })

  return normalizeUserProfile(response.data)
}
