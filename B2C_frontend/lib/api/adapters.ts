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
  Address,
  CartSummary,
  CartSummaryItem,
  Product,
  ProductCategory,
  ProductImage,
  NotificationTargetSummary,
  ShippingAddressSnapshot,
  StoreOrder,
  StoreOrderItem,
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

export function normalizeProductCategory(
  category: ProductCategory,
): ProductCategory {
  return {
    ...category,
    slug: category.slug ?? "",
    name: category.name ?? "",
    description: category.description ?? null,
  }
}

export function normalizeProductImage(image: ProductImage): ProductImage {
  return {
    ...image,
    alt_text: image.alt_text ?? null,
    caption: image.caption ?? null,
    media_url: resolveApiUrl(image.media_url),
    sort_order: image.sort_order ?? 0,
  }
}

export function normalizeProduct(product: Product): Product {
  return {
    ...product,
    name: product.name ?? "",
    slug: product.slug ?? "",
    category: product.category ?? "",
    model: product.model ?? "",
    finish: product.finish ?? "",
    color: product.color ?? "",
    technique: product.technique ?? "",
    price_usd:
      product.price_usd === null || product.price_usd === undefined
        ? "0.00"
        : String(product.price_usd),
    in_stock: Boolean(product.in_stock),
    image_url: resolveApiUrl(product.image_url),
  }
}

export function normalizeCartSummaryItem(item: CartSummaryItem): CartSummaryItem {
  return {
    ...item,
    quantity: Number(item.quantity ?? 0),
    unit_price_usd:
      item.unit_price_usd === null || item.unit_price_usd === undefined
        ? "0.00"
        : String(item.unit_price_usd),
    line_total:
      item.line_total === null || item.line_total === undefined
        ? "0.00"
        : String(item.line_total),
    product: item.product ? normalizeProduct(item.product) : null,
  }
}

export function normalizeCartSummary(cart: CartSummary): CartSummary {
  return {
    ...cart,
    id: Number(cart.id ?? 0),
    item_count: Number(cart.item_count ?? 0),
    subtotal_usd:
      cart.subtotal_usd === null || cart.subtotal_usd === undefined
        ? "0.00"
        : String(cart.subtotal_usd),
    items: ensureArray(cart.items).map(normalizeCartSummaryItem),
  }
}

export function normalizeAddress(address: Address): Address {
  return {
    ...address,
    label: address.label ?? null,
    phone: address.phone ?? null,
    address_line2: address.address_line2 ?? null,
    state_province: address.state_province ?? null,
    postal_code: address.postal_code ?? null,
    is_default: Boolean(address.is_default),
  }
}

function normalizeShippingAddress(
  address: ShippingAddressSnapshot,
): ShippingAddressSnapshot {
  return {
    ...address,
    phone: address.phone ?? null,
    address_line2: address.address_line2 ?? null,
    state_province: address.state_province ?? null,
    postal_code: address.postal_code ?? null,
  }
}

export function normalizeStoreOrderItem(item: StoreOrderItem): StoreOrderItem {
  return {
    ...item,
    product_sku: item.product_sku ?? null,
    quantity: Number(item.quantity ?? 0),
    unit_price_usd:
      item.unit_price_usd === null || item.unit_price_usd === undefined
        ? "0.00"
        : String(item.unit_price_usd),
    subtotal_usd:
      item.subtotal_usd === null || item.subtotal_usd === undefined
        ? "0.00"
        : String(item.subtotal_usd),
    product: item.product ? normalizeProduct(item.product) : null,
  }
}

export function normalizeStoreOrder(order: StoreOrder): StoreOrder {
  return {
    ...order,
    subtotal_usd:
      order.subtotal_usd === null || order.subtotal_usd === undefined
        ? "0.00"
        : String(order.subtotal_usd),
    shipping_usd:
      order.shipping_usd === null || order.shipping_usd === undefined
        ? "0.00"
        : String(order.shipping_usd),
    total_usd:
      order.total_usd === null || order.total_usd === undefined
        ? "0.00"
        : String(order.total_usd),
    shipping_address: normalizeShippingAddress(order.shipping_address),
    items: ensureArray(order.items).map(normalizeStoreOrderItem),
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
