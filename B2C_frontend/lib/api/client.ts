import type { ApiPaginationMeta } from "@/lib/types"

type QueryValue = string | number | boolean | null | undefined

type ApiSuccessResponse<T> = {
  success: true
  message: string | null
  data: T
  meta?: ApiPaginationMeta
}

type ApiErrorResponse = {
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
}

const DEFAULT_API_BASE_URL = "http://127.0.0.1:8000/api"

export class ApiError extends Error {
  status: number
  errors?: Record<string, string[]>

  constructor(
    message: string,
    status: number,
    errors?: Record<string, string[]>,
  ) {
    super(message)
    this.name = "ApiError"
    this.status = status
    this.errors = errors
  }
}

function getApiBaseUrl() {
  return (process.env.NEXT_PUBLIC_API_BASE_URL ?? DEFAULT_API_BASE_URL).replace(
    /\/+$/,
    "",
  )
}

function buildUrl(path: string, query?: Record<string, QueryValue>) {
  const normalizedPath = path.replace(/^\/+/, "")
  const url = new URL(normalizedPath, `${getApiBaseUrl()}/`)

  for (const [key, value] of Object.entries(query ?? {})) {
    if (value === undefined || value === null || value === "") {
      continue
    }

    url.searchParams.set(key, String(value))
  }

  return url.toString()
}

function isPlainObject(value: ApiRequestOptions["body"]): value is Record<string, unknown> {
  return value !== null && typeof value === "object" && !(value instanceof FormData)
}

export async function requestApi<T>(
  path: string,
  options: ApiRequestOptions = {},
): Promise<ApiSuccessResponse<T>> {
  const headers = new Headers(options.headers)
  let body = options.body

  if (options.token) {
    headers.set("Authorization", `Bearer ${options.token}`)
  }

  if (isPlainObject(body)) {
    headers.set("Content-Type", "application/json")
    body = JSON.stringify(body)
  }

  const response = await fetch(buildUrl(path, options.query), {
    method: options.method ?? "GET",
    headers,
    body: body ?? undefined,
    cache: options.cache ?? "no-store",
  })

  const rawText = await response.text()
  const payload = rawText
    ? (JSON.parse(rawText) as ApiSuccessResponse<T> | ApiErrorResponse)
    : null
  const validationErrors =
    payload !== null && "errors" in payload ? payload.errors : undefined

  if (!response.ok || payload === null || payload.success === false) {
    throw new ApiError(
      payload?.message ?? "The request could not be completed.",
      response.status,
      validationErrors,
    )
  }

  return payload
}

export function getErrorMessage(error: unknown) {
  if (error instanceof ApiError) {
    return error.message
  }

  if (error instanceof Error) {
    return error.message
  }

  return "The request could not be completed."
}
