import {
  ApiError,
  type ApiErrorResponse,
  getApiBaseUrl,
  requestApi,
  type ApiSuccessResponse,
} from "@/lib/api/client"
import { getStoredAuthToken } from "@/lib/auth/token-storage"

export interface UploadedMedia {
  id: number
  url: string
  path: string
  type: string
  mime: string
  size: number
  original_name: string
}

type MediaCategory =
  | "products"
  | "designs"
  | "community"
  | "avatars"
  | "general"

function isAbsoluteUrl(value: string) {
  return /^https?:\/\//i.test(value)
}

function buildApiUrl(path: string) {
  const normalizedPath = path.replace(/^\/+/, "")
  const apiBaseUrl = getApiBaseUrl()

  if (!isAbsoluteUrl(apiBaseUrl)) {
    const relativeBaseUrl = apiBaseUrl.replace(/^\/+|\/+$/g, "")
    const relativePath = [relativeBaseUrl, normalizedPath]
      .filter(Boolean)
      .join("/")
      .replace(/\/{2,}/g, "/")

    return relativePath.startsWith("/") ? relativePath : `/${relativePath}`
  }

  return new URL(normalizedPath, `${apiBaseUrl}/`).toString()
}

function parseApiPayload(rawText: string) {
  if (!rawText) {
    return null
  }

  try {
    return JSON.parse(rawText) as
      | ApiSuccessResponse<UploadedMedia>
      | ApiErrorResponse
  } catch {
    return null
  }
}

export async function uploadMedia(
  file: File,
  category: MediaCategory = "general",
  onProgress?: (percent: number) => void,
): Promise<UploadedMedia> {
  if (typeof window === "undefined") {
    throw new ApiError("Media uploads must run in the browser.", 0)
  }

  const formData = new FormData()
  const token = getStoredAuthToken()

  formData.append("file", file)
  formData.append("category", category)

  return new Promise<UploadedMedia>((resolve, reject) => {
    const xhr = new XMLHttpRequest()

    xhr.open("POST", buildApiUrl("/media/upload"))
    xhr.withCredentials = true
    xhr.setRequestHeader("Accept", "application/json")

    if (token) {
      xhr.setRequestHeader("Authorization", `Bearer ${token}`)
    }

    xhr.upload.addEventListener("progress", (event) => {
      if (!event.lengthComputable || !onProgress) {
        return
      }

      onProgress(Math.round((event.loaded / event.total) * 100))
    })

    xhr.addEventListener("error", () => {
      reject(new ApiError("The API is unavailable right now.", 0))
    })

    xhr.addEventListener("load", () => {
      const payload = parseApiPayload(xhr.responseText ?? "")
      const errors =
        payload !== null && "errors" in payload ? payload.errors : undefined

      if (
        xhr.status < 200 ||
        xhr.status >= 300 ||
        payload === null ||
        payload.success === false
      ) {
        reject(
          new ApiError(
            payload?.message ?? "The request could not be completed.",
            xhr.status,
            errors,
          ),
        )

        return
      }

      onProgress?.(100)
      resolve(payload.data)
    })

    xhr.send(formData)
  })
}

export async function deleteMedia(path: string): Promise<void> {
  await requestApi<null>("/media", {
    method: "DELETE",
    body: { path },
    token: getStoredAuthToken(),
  })
}
