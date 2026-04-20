"use client"

import {
  createElement,
  createContext,
  useContext,
  useEffect,
  useMemo,
  useState,
  type ReactNode,
} from "react"

import {
  getCurrentUser,
  login,
  logout,
  register,
  type LoginPayload,
  type RegisterPayload,
} from "@/lib/api/auth"
import { ApiError } from "@/lib/api/client"
import { mergeGuestCart } from "@/lib/api/cart"
import {
  clearStoredAuthToken,
  getStoredAuthToken,
  setStoredAuthToken,
} from "@/lib/auth/token-storage"
import { clearCartSessionKey, getCartSessionKey } from "@/lib/cart/session"
import type { CommunityUser } from "@/lib/types"

type AuthSessionValue = {
  token: string | null
  user: CommunityUser | null
  isReady: boolean
  isLoadingUser: boolean
  login: (payload: LoginPayload) => Promise<CommunityUser>
  register: (payload: RegisterPayload) => Promise<CommunityUser>
  logout: () => Promise<void>
  refreshUser: () => Promise<CommunityUser | null>
}

const AuthSessionContext = createContext<AuthSessionValue | null>(null)

type AuthSessionProviderProps = {
  children: ReactNode
}

export function AuthSessionProvider({ children }: AuthSessionProviderProps) {
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

  async function finalizeAuthenticatedSession(
    nextToken: string,
    fallbackUser: CommunityUser,
  ) {
    const guestCartSessionKey = getCartSessionKey()

    if (guestCartSessionKey) {
      try {
        await mergeGuestCart(guestCartSessionKey, nextToken)
        clearCartSessionKey()
      } catch {
        // If cart merge fails, the user session should still succeed.
      }
    }

    const nextUser = await getCurrentUser(nextToken).catch(() => fallbackUser)

    setStoredAuthToken(nextToken)
    setToken(nextToken)
    setUser(nextUser)
    setIsReady(true)

    return nextUser
  }

  async function loginWithCredentials(payload: LoginPayload) {
    const session = await login(payload)

    return finalizeAuthenticatedSession(session.token, session.user)
  }

  async function registerWithCredentials(payload: RegisterPayload) {
    const session = await register(payload)

    return finalizeAuthenticatedSession(session.token, session.user)
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

  const value = useMemo<AuthSessionValue>(
    () => ({
      token,
      user,
      isReady,
      isLoadingUser,
      login: loginWithCredentials,
      register: registerWithCredentials,
      logout: logoutCurrentUser,
      refreshUser,
    }),
    [token, user, isReady, isLoadingUser],
  )

  return createElement(AuthSessionContext.Provider, { value }, children)
}

export function useAuthSession() {
  const context = useContext(AuthSessionContext)

  if (!context) {
    throw new Error("useAuthSession must be used within AuthSessionProvider.")
  }

  return context
}
