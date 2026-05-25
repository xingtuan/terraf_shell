import type { UserNotification } from "@/lib/types"

function notificationDataString(notification: UserNotification, key: string) {
  const value = notification.data?.[key]

  return typeof value === "string" ? value : null
}

function firstNonEmptyString(...values: Array<string | null | undefined>) {
  for (const value of values) {
    if (typeof value === "string" && value.trim() !== "") {
      return value
    }
  }

  return null
}

export function isSystemAnnouncement(notification: UserNotification): boolean {
  return notification.type === "system_announcement"
}

export function getNotificationTitle(
  notification: UserNotification,
  fallback: string,
): string {
  return (
    firstNonEmptyString(
      notification.title,
      notificationDataString(notification, "title"),
    ) ?? fallback
  )
}

export function getNotificationBody(
  notification: UserNotification,
): string | null {
  return firstNonEmptyString(
    notification.body,
    notificationDataString(notification, "body"),
    notificationDataString(notification, "message"),
  )
}

export function getNotificationExcerpt(
  notification: UserNotification,
  maxLength = 140,
): string | null {
  const body = getNotificationBody(notification)

  if (!body) {
    return null
  }

  const excerpt = body.replace(/\s+/g, " ").trim()

  if (excerpt.length <= maxLength) {
    return excerpt
  }

  if (maxLength <= 3) {
    return excerpt.slice(0, Math.max(0, maxLength))
  }

  return `${excerpt.slice(0, maxLength - 3).trimEnd()}...`
}
