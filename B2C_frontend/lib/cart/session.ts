"use client"

export const CART_SESSION_STORAGE_KEY = "oxp_cart_session"
const LEGACY_CART_SESSION_STORAGE_KEY = ["shell", "fin_cart_session"].join("")

function parseDocumentCookie(name: string) {
  if (typeof document === "undefined") {
    return null
  }

  const value = document.cookie
    .split(";")
    .map((part) => part.trim())
    .find((part) => part.startsWith(`${name}=`))

  if (!value) {
    return null
  }

  return decodeURIComponent(value.slice(name.length + 1))
}

function clearDocumentCookie(name: string) {
  if (typeof document === "undefined") {
    return
  }

  document.cookie = `${name}=; path=/; max-age=0; samesite=lax`
}

function migrateStoredCartSessionKey() {
  const legacyValue = window.localStorage.getItem(LEGACY_CART_SESSION_STORAGE_KEY)

  if (legacyValue?.trim()) {
    window.localStorage.setItem(CART_SESSION_STORAGE_KEY, legacyValue)
    window.localStorage.removeItem(LEGACY_CART_SESSION_STORAGE_KEY)
    return legacyValue
  }

  window.localStorage.removeItem(LEGACY_CART_SESSION_STORAGE_KEY)

  return null
}

export function getCartSessionKey() {
  if (typeof window === "undefined") {
    return null
  }

  const storedValue = window.localStorage.getItem(CART_SESSION_STORAGE_KEY)

  if (storedValue?.trim()) {
    return storedValue
  }

  const migratedValue = migrateStoredCartSessionKey()

  if (migratedValue?.trim()) {
    return migratedValue
  }

  return (
    parseDocumentCookie(CART_SESSION_STORAGE_KEY) ??
    parseDocumentCookie(LEGACY_CART_SESSION_STORAGE_KEY)
  )
}

export function syncCartSessionKeyFromCookie() {
  if (typeof window === "undefined") {
    return null
  }

  const sessionKey =
    parseDocumentCookie(CART_SESSION_STORAGE_KEY) ??
    parseDocumentCookie(LEGACY_CART_SESSION_STORAGE_KEY)

  if (sessionKey?.trim()) {
    window.localStorage.setItem(CART_SESSION_STORAGE_KEY, sessionKey)
    window.localStorage.removeItem(LEGACY_CART_SESSION_STORAGE_KEY)
    return sessionKey
  }

  return getCartSessionKey()
}

export function persistCartSessionKey(sessionKey: string) {
  if (typeof window === "undefined") {
    return
  }

  window.localStorage.setItem(CART_SESSION_STORAGE_KEY, sessionKey)
  window.localStorage.removeItem(LEGACY_CART_SESSION_STORAGE_KEY)
  clearDocumentCookie(LEGACY_CART_SESSION_STORAGE_KEY)
}

export function clearCartSessionKey() {
  if (typeof window === "undefined") {
    return
  }

  window.localStorage.removeItem(CART_SESSION_STORAGE_KEY)
  window.localStorage.removeItem(LEGACY_CART_SESSION_STORAGE_KEY)
  clearDocumentCookie(LEGACY_CART_SESSION_STORAGE_KEY)
}
