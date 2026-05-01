const TOKEN_STORAGE_KEY = "oxp.community.auth-token"
const LEGACY_TOKEN_STORAGE_KEY = ["shell", "fin.community.auth-token"].join("")

function migrateStoredAuthToken() {
  const legacyToken = window.localStorage.getItem(LEGACY_TOKEN_STORAGE_KEY)

  if (legacyToken?.trim()) {
    window.localStorage.setItem(TOKEN_STORAGE_KEY, legacyToken)
    window.localStorage.removeItem(LEGACY_TOKEN_STORAGE_KEY)
    return legacyToken
  }

  window.localStorage.removeItem(LEGACY_TOKEN_STORAGE_KEY)

  return null
}

export function getStoredAuthToken() {
  if (typeof window === "undefined") {
    return null
  }

  return window.localStorage.getItem(TOKEN_STORAGE_KEY) ?? migrateStoredAuthToken()
}

export function setStoredAuthToken(token: string) {
  if (typeof window === "undefined") {
    return
  }

  window.localStorage.setItem(TOKEN_STORAGE_KEY, token)
  window.localStorage.removeItem(LEGACY_TOKEN_STORAGE_KEY)
}

export function clearStoredAuthToken() {
  if (typeof window === "undefined") {
    return
  }

  window.localStorage.removeItem(TOKEN_STORAGE_KEY)
  window.localStorage.removeItem(LEGACY_TOKEN_STORAGE_KEY)
}
