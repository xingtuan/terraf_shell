import { normalizeCommunityUser } from "@/lib/api/adapters"
import { requestApi } from "@/lib/api/client"
import type { AuthSessionPayload, CommunityUser } from "@/lib/types"

export type LoginPayload = {
  email: string
  password: string
  device_name?: string
}

export type RegisterPayload = {
  name: string
  email: string
  password: string
  password_confirmation: string
  device_name?: string
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
}

export async function login(payload: LoginPayload) {
  const response = await requestApi<AuthSessionPayload>("/auth/login", {
    method: "POST",
    body: payload,
  })

  return {
    ...response.data,
    user: normalizeCommunityUser(response.data.user) ?? response.data.user,
  }
}

export async function register(payload: RegisterPayload) {
  const response = await requestApi<AuthSessionPayload>("/auth/register", {
    method: "POST",
    body: payload,
  })

  return {
    ...response.data,
    user: normalizeCommunityUser(response.data.user) ?? response.data.user,
  }
}

export async function getCurrentUser(token: string) {
  const response = await requestApi<CommunityUser>("/auth/me", {
    token,
  })

  return normalizeCommunityUser(response.data) ?? response.data
}

export async function logout(token: string) {
  await requestApi<null>("/auth/logout", {
    method: "POST",
    token,
  })
}

export async function updateProfile(payload: UpdateProfilePayload, token: string) {
  const response = await requestApi<CommunityUser>("/auth/profile", {
    method: "PUT",
    token,
    body: payload,
  })

  return normalizeCommunityUser(response.data) ?? response.data
}
