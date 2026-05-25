import { getLocalizedHref, type Locale } from "./i18n.ts"
import { resolveCmsHref } from "./page-content.ts"
import type { UserNotification } from "./types.ts"

const COMMUNITY_POST_NOTIFICATION_TYPES = new Set([
  "comment",
  "reply",
  "like",
  "favorite",
  "submission_approved",
  "submission_rejected",
  "concept_featured",
])

function dataString(notification: UserNotification, key: string) {
  const value = notification.data?.[key]

  return typeof value === "string" && value.trim() !== ""
    ? value.trim()
    : null
}

function dataNumberString(notification: UserNotification, key: string) {
  const value = notification.data?.[key]

  if (typeof value === "number" && Number.isFinite(value)) {
    return String(value)
  }

  return typeof value === "string" && value.trim() !== ""
    ? value.trim()
    : null
}

function parseActionUrl(actionUrl?: string | null) {
  if (!actionUrl?.trim()) {
    return null
  }

  try {
    const parsed = /^https?:\/\//i.test(actionUrl)
      ? new URL(actionUrl)
      : new URL(actionUrl, "https://oxp.local")

    return {
      isExternal: /^https?:\/\//i.test(actionUrl) && parsed.origin !== "https://oxp.local",
      pathname: parsed.pathname,
      hash: parsed.hash,
    }
  } catch {
    return null
  }
}

function targetPostSlug(notification: UserNotification) {
  return notification.target && "slug" in notification.target
    ? notification.target.slug
    : null
}

function actionPostSlug(notification: UserNotification) {
  const parsed = parseActionUrl(notification.action_url)

  if (!parsed) {
    return null
  }

  if (
    parsed.isExternal &&
    !COMMUNITY_POST_NOTIFICATION_TYPES.has(notification.type)
  ) {
    return null
  }

  const segments = parsed.pathname.split("/").filter(Boolean)
  const postsIndex = segments.indexOf("posts")
  const slug = postsIndex >= 0 ? segments[postsIndex + 1] : null

  if (!slug) {
    return null
  }

  try {
    return decodeURIComponent(slug)
  } catch {
    return slug
  }
}

function commentHash(notification: UserNotification) {
  const commentId = dataNumberString(notification, "comment_id")

  if (commentId) {
    return `#comment-${commentId}`
  }

  const hash = parseActionUrl(notification.action_url)?.hash

  return hash?.startsWith("#comment-") ? hash : ""
}

function targetUsername(notification: UserNotification) {
  return notification.target && "username" in notification.target
    ? notification.target.username
    : null
}

export function resolveNotificationHref(
  locale: Locale,
  notification: UserNotification,
): string {
  const fallback = getLocalizedHref(locale, "community")
  const postSlug =
    targetPostSlug(notification) ??
    dataString(notification, "post_slug") ??
    actionPostSlug(notification)

  if (postSlug) {
    return `${getLocalizedHref(locale, `community/${postSlug}`)}${commentHash(
      notification,
    )}`
  }

  if (notification.type === "follow") {
    const username =
      dataString(notification, "username") ?? notification.actor?.username

    if (username) {
      return getLocalizedHref(locale, `community/u/${username}`)
    }
  }

  const username = targetUsername(notification)

  if (
    notification.type !== "follow" &&
    notification.target_type === "user" &&
    username
  ) {
    return getLocalizedHref(locale, `community/u/${username}`)
  }

  if (notification.action_url) {
    return resolveCmsHref(locale, notification.action_url, fallback)
  }

  return fallback
}
