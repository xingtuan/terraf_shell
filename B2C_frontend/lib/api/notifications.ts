import { normalizeUserNotification } from "@/lib/api/adapters"
import { requestApi } from "@/lib/api/client"
import { ensureArray, normalizePaginationMeta } from "@/lib/api/normalizers"
import type {
  NotificationPaginationMeta,
  PaginatedResult,
  UserNotification,
} from "@/lib/types"

export type ListNotificationsParams = {
  per_page?: number
}

export async function listNotifications(
  token: string,
  params: ListNotificationsParams = {},
): Promise<PaginatedResult<UserNotification, NotificationPaginationMeta>> {
  const response = await requestApi<UserNotification[]>("/notifications", {
    token,
    query: params,
  })

  const items = ensureArray(response.data).map(normalizeUserNotification)

  return {
    items,
    meta: {
      ...normalizePaginationMeta(response.meta, items.length),
      unread_count: (response.meta as NotificationPaginationMeta | undefined)
        ?.unread_count,
    },
  }
}

export async function markNotificationRead(
  notificationId: number,
  token: string,
) {
  const response = await requestApi<UserNotification>(
    `/notifications/${notificationId}/read`,
    {
      method: "PATCH",
      token,
    },
  )

  return normalizeUserNotification(response.data)
}
