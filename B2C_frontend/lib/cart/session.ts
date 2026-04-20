"use client"

export const CART_SESSION_STORAGE_KEY = "shellfin_cart_session"

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

export function getCartSessionKey() {
  if (typeof window === "undefined") {
    return null
  }

  const storedValue = window.localStorage.getItem(CART_SESSION_STORAGE_KEY)

  if (storedValue?.trim()) {
    return storedValue
  }

  return parseDocumentCookie(CART_SESSION_STORAGE_KEY)
}

export function syncCartSessionKeyFromCookie() {
  if (typeof window === "undefined") {
    return null
  }

  const sessionKey = parseDocumentCookie(CART_SESSION_STORAGE_KEY)

  if (sessionKey?.trim()) {
    window.localStorage.setItem(CART_SESSION_STORAGE_KEY, sessionKey)
    return sessionKey
  }

  return getCartSessionKey()
}

export function persistCartSessionKey(sessionKey: string) {
  if (typeof window === "undefined") {
    return
  }

  window.localStorage.setItem(CART_SESSION_STORAGE_KEY, sessionKey)
}

export function clearCartSessionKey() {
  if (typeof window === "undefined") {
    return
  }

  window.localStorage.removeItem(CART_SESSION_STORAGE_KEY)
}
