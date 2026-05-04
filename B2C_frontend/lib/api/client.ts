import type { ApiPaginationMeta } from "@/lib/types"

type QueryValue = string | number | boolean | null | undefined

export type ApiSuccessResponse<T> = {
  success: true
  message: string | null
  data: T
  meta?: ApiPaginationMeta
}

export type ApiErrorResponse = {
  success: false
  message: string
  errors?: Record<string, string[]>
}

type ApiRequestOptions = {
  method?: string
  body?: BodyInit | FormData | Record<string, unknown> | null
  headers?: HeadersInit
  query?: Record<string, QueryValue>
  token?: string | null
  cache?: RequestCache
  baseUrl?: string
  credentials?: RequestCredentials
}

const DEFAULT_API_BASE_URL = "/api"

export class ApiError extends Error {
  status: number
  errors?: Record<string, string[]>
  code?: string

  constructor(
    message: string,
    status: number,
    errors?: Record<string, string[]>,
    code?: string,
  ) {
    super(message)
    this.name = "ApiError"
    this.status = status
    this.errors = errors
    this.code = code
  }

  firstFieldError(): { field: string; message: string } | null {
    if (!this.errors) return null

    for (const [field, messages] of Object.entries(this.errors)) {
      if (messages.length > 0) {
        return { field, message: messages[0] }
      }
    }

    return null
  }

  flattenedFieldErrors(): string[] {
    if (!this.errors) return []

    return Object.values(this.errors).flat()
  }
}

function isAbsoluteUrl(value: string) {
  return /^https?:\/\//i.test(value)
}

function appendQueryString(
  url: string,
  query?: Record<string, QueryValue>,
) {
  const searchParams = new URLSearchParams()

  for (const [key, value] of Object.entries(query ?? {})) {
    if (value === undefined || value === null || value === "") {
      continue
    }

    searchParams.set(key, String(value))
  }

  const queryString = searchParams.toString()

  return queryString ? `${url}?${queryString}` : url
}

function normalizeRelativeBaseUrl(value: string) {
  const normalized = value.replace(/^\/+|\/+$/g, "")

  return normalized ? `/${normalized}` : ""
}

export function getApiBaseUrl(override?: string) {
  const resolvedBaseUrl = (
    override ??
    process.env.NEXT_PUBLIC_API_BASE_URL ??
    DEFAULT_API_BASE_URL
  ).trim()

  if (!resolvedBaseUrl) {
    return DEFAULT_API_BASE_URL
  }

  return resolvedBaseUrl.replace(/\/+$/, "")
}

function buildUrl(
  path: string,
  query?: Record<string, QueryValue>,
  baseUrl?: string,
) {
  const normalizedPath = path.replace(/^\/+/, "")
  const apiBaseUrl = getApiBaseUrl(baseUrl)

  if (!isAbsoluteUrl(apiBaseUrl)) {
    const relativeBaseUrl = normalizeRelativeBaseUrl(apiBaseUrl)
    const relativePath = [relativeBaseUrl, normalizedPath]
      .filter(Boolean)
      .join("/")
      .replace(/\/{2,}/g, "/")

    return appendQueryString(relativePath.startsWith("/") ? relativePath : `/${relativePath}`, query)
  }

  const url = new URL(normalizedPath, `${apiBaseUrl}/`)

  for (const [key, value] of Object.entries(query ?? {})) {
    if (value === undefined || value === null || value === "") {
      continue
    }

    url.searchParams.set(key, String(value))
  }

  return url.toString()
}

function detectLocale(): string | null {
  if (typeof window === "undefined") return null
  const match = /^\/(en|ko|zh)\b/.exec(window.location.pathname)
  return match ? match[1] : null
}

function isPlainObject(
  value: ApiRequestOptions["body"],
): value is Record<string, unknown> {
  return value !== null && typeof value === "object" && !(value instanceof FormData)
}

function parseApiPayload(rawText: string) {
  if (!rawText) {
    return null
  }

  try {
    return JSON.parse(rawText) as ApiSuccessResponse<unknown> | ApiErrorResponse
  } catch {
    return null
  }
}

export async function requestApi<T>(
  path: string,
  options: ApiRequestOptions = {},
): Promise<ApiSuccessResponse<T>> {
  const headers = new Headers(options.headers)
  let body = options.body

  headers.set("Accept", "application/json")

  const locale = detectLocale()
  if (locale) {
    headers.set("Accept-Language", locale)
  }

  if (options.token) {
    headers.set("Authorization", `Bearer ${options.token}`)
  }

  if (isPlainObject(body)) {
    headers.set("Content-Type", "application/json")
    body = JSON.stringify(body)
  }

  let response: Response

  try {
    response = await fetch(buildUrl(path, options.query, options.baseUrl), {
      method: options.method ?? "GET",
      headers,
      body: body ?? undefined,
      cache: options.cache ?? "no-store",
      credentials: options.credentials ?? "include",
    })
  } catch {
    throw new ApiError("The API is unavailable right now.", 0, undefined, "api_unavailable")
  }

  const rawText = await response.text()
  const payload = parseApiPayload(rawText)
  const validationErrors =
    payload !== null && "errors" in payload ? payload.errors : undefined

  if (!response.ok || payload === null || payload.success === false) {
    throw new ApiError(
      payload?.message ?? "The request could not be completed.",
      response.status,
      validationErrors,
      payload?.message ? undefined : "request_failed",
    )
  }

  return payload as ApiSuccessResponse<T>
}

// Laravel's generic 422 message that carries no information on its own.
// When we detect it we surface the first field-level error instead.
const GENERIC_VALIDATION_MESSAGES = new Set([
  "The given data was invalid.",
  "Validation failed.",
  "Unprocessable Content",
])

/**
 * Returns a user-facing error string.
 *
 * Priority order:
 *   1. Field-level errors from `error.errors` (e.g. "The email has already been taken.")
 *   2. `error.message` when it is specific (not a generic validation wrapper)
 *   3. Fallback string
 */
export function getErrorMessage(error: unknown): string {
  if (error instanceof ApiError) {
    const first = error.firstFieldError()

    if (first) {
      return first.message
    }

    if (GENERIC_VALIDATION_MESSAGES.has(error.message)) {
      return "Please check your input and try again."
    }

    return error.message
  }

  if (error instanceof Error) {
    return error.message
  }

  return "The request could not be completed."
}

/**
 * Like `getErrorMessage` but maps known error codes to i18n strings
 * and surfaces field errors when the top-level message is a generic wrapper.
 */
export function getLocalizedErrorMessage(
  error: unknown,
  t: { apiUnavailable: string; requestFailed: string; validation?: string },
): string {
  if (error instanceof ApiError) {
    if (error.code === "api_unavailable") return t.apiUnavailable
    if (error.code === "request_failed") return t.requestFailed

    const first = error.firstFieldError()

    if (first) {
      return first.message
    }

    if (GENERIC_VALIDATION_MESSAGES.has(error.message)) {
      return t.validation ?? "Please check your input and try again."
    }

    return error.message
  }

  if (error instanceof Error) {
    return error.message
  }

  return t.requestFailed
}

/**
 * Returns a flat map of field → first error message, or null when there are
 * no field-level errors.  Use this to render per-field red-text beneath inputs.
 *
 * Example return value:
 *   { email: "The email has already been taken.", password: "..." }
 */
export function getFieldErrors(error: unknown): Record<string, string> | null {
  if (!(error instanceof ApiError) || !error.errors) {
    return null
  }

  const result: Record<string, string> = {}

  for (const [field, messages] of Object.entries(error.errors)) {
    if (messages.length > 0) {
      result[field] = messages[0]
    }
  }

  return Object.keys(result).length > 0 ? result : null
}
