"use client"

import { useEffect, useState } from "react"

import {
  getCurrentUser,
  login,
  logout,
  register,
  type LoginPayload,
  type RegisterPayload,
} from "@/lib/api/auth"
import { ApiError } from "@/lib/api/client"
import {
  clearStoredAuthToken,
  getStoredAuthToken,
  setStoredAuthToken,
} from "@/lib/auth/token-storage"
import type { CommunityUser } from "@/lib/types"

export function useAuthSession() {
  const [token, setToken] = useState<string | null>(null)
  const [user, setUser] = useState<CommunityUser | null>(null)
  const [isReady, setIsReady] = useState(false)
  const [isLoadingUser, setIsLoadingUser] = useState(false)

  useEffect(() => {
    const storedToken = getStoredAuthToken()

    if (!storedToken) {
      setIsReady(true)
      return
    }

    setToken(storedToken)
    setIsLoadingUser(true)

    void getCurrentUser(storedToken)
      .then((nextUser) => {
        setUser(nextUser)
      })
      .catch(() => {
        clearStoredAuthToken()
        setToken(null)
        setUser(null)
      })
      .finally(() => {
        setIsLoadingUser(false)
        setIsReady(true)
      })
  }, [])

  async function refreshUser() {
    if (!token) {
      setUser(null)
      return null
    }

    setIsLoadingUser(true)

    try {
      const nextUser = await getCurrentUser(token)
      setUser(nextUser)

      return nextUser
    } catch (error) {
      if (error instanceof ApiError && error.status === 401) {
        clearStoredAuthToken()
        setToken(null)
        setUser(null)
      }

      throw error
    } finally {
      setIsLoadingUser(false)
      setIsReady(true)
    }
  }

  async function loginWithCredentials(payload: LoginPayload) {
    const session = await login(payload)

    setStoredAuthToken(session.token)
    setToken(session.token)
    setUser(session.user)
    setIsReady(true)

    return session.user
  }

  async function registerWithCredentials(payload: RegisterPayload) {
    const session = await register(payload)

    setStoredAuthToken(session.token)
    setToken(session.token)
    setUser(session.user)
    setIsReady(true)

    return session.user
  }

  async function logoutCurrentUser() {
    const activeToken = token

    if (activeToken) {
      try {
        await logout(activeToken)
      } catch {
        // Clearing local state is enough for the frontend if logout fails remotely.
      }
    }

    clearStoredAuthToken()
    setToken(null)
    setUser(null)
    setIsReady(true)
  }

  return {
    token,
    user,
    isReady,
    isLoadingUser,
    login: loginWithCredentials,
    register: registerWithCredentials,
    logout: logoutCurrentUser,
    refreshUser,
  }
}
