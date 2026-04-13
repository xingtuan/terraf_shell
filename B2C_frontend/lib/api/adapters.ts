import {
  ensureArray,
  normalizeMaterialSpecIcon,
  resolveApiUrl,
} from "@/lib/api/normalizers"
import type {
  ArticleDetail,
  ArticleSummary,
  CommunityCategory,
  CommunityComment,
  CommunityMedia,
  CommunityPost,
  CommunityPostImage,
  CommunityProfile,
  CommunityTag,
  CommunityUser,
  FundingCampaign,
  HomeSection,
  HomeSectionPayload,
  JsonObject,
  MaterialApplication,
  MaterialDetail,
  MaterialSpec,
  MaterialStorySection,
  MaterialSummary,
  NotificationTargetSummary,
  UserNotification,
} from "@/lib/types"

function isJsonObject(value: unknown): value is JsonObject {
  return value !== null && typeof value === "object" && !Array.isArray(value)
}

export function normalizeHomeSection(section: HomeSection): HomeSection {
  return {
    ...section,
    payload: isJsonObject(section.payload)
      ? (section.payload as HomeSectionPayload)
      : null,
    media_url: resolveApiUrl(section.media_url),
  }
}

export function normalizeMaterialSpec(spec: MaterialSpec): MaterialSpec {
  return {
    ...spec,
    label: spec.label ?? "",
    value: spec.value ?? "",
    detail: spec.detail ?? null,
    icon: normalizeMaterialSpecIcon(spec.icon),
    media_url: resolveApiUrl(spec.media_url),
  }
}

export function normalizeMaterialStorySection(
  section: MaterialStorySection,
): MaterialStorySection {
  return {
    ...section,
    title: section.title ?? "",
    content: section.content ?? "",
    media_url: resolveApiUrl(section.media_url),
  }
}

export function normalizeMaterialApplication(
  application: MaterialApplication,
): MaterialApplication {
  return {
    ...application,
    title: application.title ?? "",
    description: application.description ?? "",
    media_url: resolveApiUrl(application.media_url),
  }
}

export function normalizeMaterialSummary(material: MaterialSummary): MaterialSummary {
  return {
    ...material,
    title: material.title ?? "",
    slug: material.slug ?? "",
    media_url: resolveApiUrl(material.media_url),
  }
}

export function normalizeMaterialDetail(material: MaterialDetail): MaterialDetail {
  return {
    ...normalizeMaterialSummary(material),
    specs: ensureArray(material.specs).map(normalizeMaterialSpec),
    story_sections: ensureArray(material.story_sections).map(
      normalizeMaterialStorySection,
    ),
    applications: ensureArray(material.applications).map(
      normalizeMaterialApplication,
    ),
  }
}

export function normalizeArticleSummary(article: ArticleSummary): ArticleSummary {
  return {
    ...article,
    title: article.title ?? "",
    slug: article.slug ?? "",
    excerpt: article.excerpt ?? null,
    media_url: resolveApiUrl(article.media_url),
  }
}

export function normalizeArticleDetail(article: ArticleDetail): ArticleDetail {
  return {
    ...normalizeArticleSummary(article),
    content: article.content ?? "",
  }
}

export function normalizeCommunityProfile(
  profile?: CommunityProfile | null,
): CommunityProfile | null {
  if (!profile) {
    return null
  }

  return {
    ...profile,
    avatar_url: resolveApiUrl(profile.avatar_url),
  }
}

export function normalizeCommunityUser(
  user?: CommunityUser | null,
): CommunityUser | null {
  if (!user) {
    return null
  }

  return {
    ...user,
    avatar_url: resolveApiUrl(user.avatar_url),
    profile: normalizeCommunityProfile(user.profile),
    is_following: Boolean(user.is_following),
  }
}

export function normalizeCommunityCategory(
  category?: CommunityCategory | null,
): CommunityCategory | null {
  if (!category) {
    return null
  }

  return {
    ...category,
    name: category.name ?? "",
    slug: category.slug ?? "",
  }
}

export function normalizeCommunityTag(tag: CommunityTag): CommunityTag {
  return {
    ...tag,
    name: tag.name ?? "",
    slug: tag.slug ?? "",
  }
}

export function normalizeCommunityPostImage(
  image: CommunityPostImage,
): CommunityPostImage {
  return {
    ...image,
    url: resolveApiUrl(image.url) ?? image.url,
    preview_url: resolveApiUrl(image.preview_url),
    thumbnail_url: resolveApiUrl(image.thumbnail_url),
  }
}

export function normalizeCommunityMedia(media: CommunityMedia): CommunityMedia {
  return {
    ...media,
    url: resolveApiUrl(media.url),
    preview_url: resolveApiUrl(media.preview_url),
    thumbnail_url: resolveApiUrl(media.thumbnail_url),
    external_url: resolveApiUrl(media.external_url),
    metadata: isJsonObject(media.metadata) ? media.metadata : null,
  }
}

export function normalizeFundingCampaign(
  campaign?: FundingCampaign | null,
): FundingCampaign | null {
  if (!campaign) {
    return null
  }

  return {
    ...campaign,
    support_enabled: Boolean(campaign.support_enabled),
  }
}

export function normalizeCommunityPost(post: CommunityPost): CommunityPost {
  return {
    ...post,
    title: post.title ?? "",
    slug: post.slug ?? "",
    content: post.content ?? "",
    user: normalizeCommunityUser(post.user),
    category: normalizeCommunityCategory(post.category),
    tags: ensureArray(post.tags).map(normalizeCommunityTag),
    images: ensureArray(post.images).map(normalizeCommunityPostImage),
    media: ensureArray(post.media).map(normalizeCommunityMedia),
    funding_campaign: normalizeFundingCampaign(post.funding_campaign),
    is_liked: Boolean(post.is_liked),
    is_favorited: Boolean(post.is_favorited),
    can_edit: Boolean(post.can_edit),
    can_delete: Boolean(post.can_delete),
  }
}

export function normalizeCommunityComment(
  comment: CommunityComment,
): CommunityComment {
  return {
    ...comment,
    content: comment.content ?? "",
    user: normalizeCommunityUser(comment.user),
    replies: ensureArray(comment.replies).map(normalizeCommunityComment),
    can_edit: Boolean(comment.can_edit),
    can_delete: Boolean(comment.can_delete),
    is_liked: Boolean(comment.is_liked),
  }
}

function normalizeNotificationTarget(
  target?: NotificationTargetSummary | null,
): NotificationTargetSummary | null {
  if (!target) {
    return null
  }

  if ("slug" in target) {
    return {
      ...target,
      slug: target.slug ?? "",
      title: target.title ?? "",
    }
  }

  if ("username" in target) {
    return {
      ...target,
      name: target.name ?? "",
      username: target.username ?? "",
    }
  }

  return {
    ...target,
    content: target.content ?? "",
  }
}

export function normalizeUserNotification(
  notification: UserNotification,
): UserNotification {
  return {
    ...notification,
    actor: normalizeCommunityUser(notification.actor),
    target: normalizeNotificationTarget(notification.target),
    action_url: resolveApiUrl(notification.action_url),
    data: isJsonObject(notification.data) ? notification.data : {},
    is_read: Boolean(notification.is_read),
  }
}
