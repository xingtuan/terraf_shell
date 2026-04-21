import { getIntlLocale, type Locale } from "@/lib/i18n"
import type {
  CommunityCategory,
  CommunityComment,
  CommunityPost,
  CommunityTag,
  CommunityUser,
} from "@/lib/types"

export function formatCommunityDate(
  locale: Locale,
  value?: string | null,
  options?: Intl.DateTimeFormatOptions,
) {
  if (!value) {
    return null
  }

  return new Intl.DateTimeFormat(getIntlLocale(locale), {
    dateStyle: "medium",
    timeStyle: "short",
    ...options,
  }).format(new Date(value))
}

export function getCommunityUserName(user?: CommunityUser | null) {
  return user?.name ?? user?.username ?? "Community member"
}

export function getCommunityUserInitials(user?: CommunityUser | null) {
  const source = user?.name?.trim() || user?.username?.trim() || "CM"
  const parts = source.split(/\s+/).filter(Boolean)

  if (parts.length === 1) {
    return parts[0].slice(0, 2).toUpperCase()
  }

  return `${parts[0]?.[0] ?? ""}${parts[1]?.[0] ?? ""}`.toUpperCase()
}

export function getCommunityTaxonomyName(
  item: CommunityCategory | CommunityTag | null | undefined,
  locale: Locale,
) {
  if (!item) {
    return ""
  }

  if (locale === "ko" && item.name_ko?.trim()) {
    return item.name_ko
  }

  if (locale === "zh" && item.name_zh?.trim()) {
    return item.name_zh
  }

  return item.name
}

export function getCommunityPostPreview(post: CommunityPost, maxLength = 220) {
  const preview = (post.excerpt ?? post.content ?? "").trim()

  if (preview.length <= maxLength) {
    return preview
  }

  return `${preview.slice(0, maxLength)}...`
}

export function getCommunityCommentPreview(
  comment: CommunityComment,
  maxLength = 140,
) {
  const preview = comment.content.trim()

  if (preview.length <= maxLength) {
    return preview
  }

  return `${preview.slice(0, maxLength)}...`
}

export function getCommunityPostCoverImage(post: CommunityPost) {
  const mediaImage = post.media?.find(
    (item) =>
      item.media_type === "image" ||
      item.kind?.includes("image") ||
      item.is_image,
  )

  return (
    post.cover_image_url ??
    post.images[0]?.thumbnail_url ??
    post.images[0]?.preview_url ??
    post.images[0]?.url ??
    mediaImage?.thumbnail_url ??
    mediaImage?.preview_url ??
    mediaImage?.url ??
    "/placeholder.jpg"
  )
}

export function getCommunitySupportUrl(post: CommunityPost) {
  return (
    post.funding_url ??
    post.funding_campaign?.external_crowdfunding_url ??
    post.external_crowdfunding_url ??
    null
  )
}
