import {
  ensureArray,
  normalizeMaterialSpecIcon,
  normalizePaginationMeta,
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
  ProductAppliedFilterChip,
  ProductCatalogMeta,
  ProductCategory,
  ProductFacetOption,
  ProductImage,
  ProductSeo,
  ProductSpecification,
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
    id: Number(image.id ?? 0),
    product_id:
      image.product_id === null || image.product_id === undefined
        ? undefined
        : Number(image.product_id),
    alt_text: image.alt_text ?? null,
    caption: image.caption ?? null,
    media_url: resolveApiUrl(image.media_url),
    sort_order: image.sort_order ?? 0,
  }
}

function normalizeProductSpecification(
  specification: ProductSpecification,
): ProductSpecification {
  return {
    key: specification.key ?? "",
    label: specification.label ?? "",
    value: specification.value ?? "",
    unit: specification.unit ?? null,
    group: specification.group ?? null,
  }
}

function normalizeProductSeo(seo?: ProductSeo | null): ProductSeo | null {
  if (!seo) {
    return null
  }

  return {
    title: seo.title ?? null,
    description: seo.description ?? null,
  }
}

export function normalizeProduct(
  product: Product,
  depth = 0,
): Product {
  const title = product.title ?? product.name ?? ""
  const primaryImageUrl = resolveApiUrl(
    product.primary_image_url ?? product.image_url,
  )
  const galleryImages = ensureArray(product.gallery_images).map(normalizeProductImage)

  const normalizedGallery =
    galleryImages.length > 0
      ? galleryImages
      : primaryImageUrl
        ? [
            {
              id: 0,
              product_id: Number(product.id ?? 0),
              alt_text: title,
              caption: product.subtitle ?? product.short_description ?? null,
              media_url: primaryImageUrl,
              sort_order: 0,
              created_at: null,
              updated_at: null,
            },
          ]
        : []

  return {
    ...product,
    id: Number(product.id ?? 0),
    title,
    name: title,
    slug: product.slug ?? "",
    sku: product.sku ?? null,
    subtitle: product.subtitle ?? product.short_description ?? null,
    short_description: product.short_description ?? product.subtitle ?? null,
    long_description:
      product.long_description ?? product.full_description ?? null,
    full_description:
      product.full_description ?? product.long_description ?? null,
    category: product.category ?? "",
    category_label: product.category_label ?? null,
    category_detail: product.category_detail
      ? normalizeProductCategory(product.category_detail)
      : null,
    model: product.model ?? "",
    model_label: product.model_label ?? null,
    finish: product.finish ?? "",
    finish_label: product.finish_label ?? null,
    color: product.color ?? "",
    color_label: product.color_label ?? null,
    technique: product.technique ?? "",
    technique_label: product.technique_label ?? null,
    currency: product.currency ?? "USD",
    price_usd:
      product.price_usd === null || product.price_usd === undefined
        ? "0.00"
        : String(product.price_usd),
    price:
      product.price === null || product.price === undefined
        ? product.price_usd === null || product.price_usd === undefined
          ? "0.00"
          : String(product.price_usd)
        : String(product.price),
    compare_at_price_usd:
      product.compare_at_price_usd === null ||
      product.compare_at_price_usd === undefined
        ? null
        : String(product.compare_at_price_usd),
    compare_at_price:
      product.compare_at_price === null || product.compare_at_price === undefined
        ? product.compare_at_price_usd === null ||
          product.compare_at_price_usd === undefined
          ? null
          : String(product.compare_at_price_usd)
        : String(product.compare_at_price),
    on_sale: Boolean(product.on_sale),
    featured: Boolean(product.featured),
    is_bestseller: Boolean(product.is_bestseller),
    is_new: Boolean(product.is_new),
    in_stock: Boolean(product.in_stock),
    can_add_to_cart: Boolean(product.can_add_to_cart ?? product.in_stock),
    inquiry_only: Boolean(product.inquiry_only),
    sample_request_enabled: Boolean(product.sample_request_enabled),
    stock_quantity:
      product.stock_quantity === null || product.stock_quantity === undefined
        ? null
        : Number(product.stock_quantity),
    stock_status: product.stock_status ?? null,
    stock_status_label: product.stock_status_label ?? null,
    lead_time: product.lead_time ?? null,
    availability_text: product.availability_text ?? null,
    primary_image_url: primaryImageUrl,
    image_url: primaryImageUrl,
    gallery_images: normalizedGallery,
    features: ensureArray(product.features).map((feature) => String(feature)),
    use_cases: ensureArray(product.use_cases).map((useCase) => String(useCase)),
    use_case_labels: ensureArray(product.use_case_labels).map((label) =>
      String(label),
    ),
    dimensions: product.dimensions ?? null,
    weight_grams:
      product.weight_grams === null || product.weight_grams === undefined
        ? null
        : Number(product.weight_grams),
    specifications: ensureArray(product.specifications).map(
      normalizeProductSpecification,
    ),
    certifications: ensureArray(product.certifications).map((item) =>
      String(item),
    ),
    care_instructions: ensureArray(product.care_instructions).map((item) =>
      String(item),
    ),
    material_benefits: ensureArray(product.material_benefits).map((item) =>
      String(item),
    ),
    seo: normalizeProductSeo(product.seo),
    related_products:
      depth >= 1
        ? []
        : ensureArray(product.related_products).map((relatedProduct) =>
            normalizeProduct(relatedProduct, depth + 1),
          ),
    published_at: product.published_at ?? null,
  }
}

function normalizeProductFacetOption(option: ProductFacetOption): ProductFacetOption {
  return {
    value: option.value ?? "",
    label: option.label ?? "",
    count: Number(option.count ?? 0),
  }
}

function normalizeProductAppliedFilterChip(
  chip: ProductAppliedFilterChip,
): ProductAppliedFilterChip {
  return {
    key: chip.key ?? "",
    value: chip.value ?? "",
    display: chip.display ?? chip.value ?? "",
  }
}

export function normalizeProductCatalogMeta(
  meta: Partial<ProductCatalogMeta> | undefined,
): ProductCatalogMeta {
  const pagination = normalizePaginationMeta(meta)

  return {
    ...pagination,
    sort: (meta?.sort ?? "featured") as ProductCatalogMeta["sort"],
    sort_options: ensureArray(meta?.sort_options).map((option) => ({
      value: (option.value ?? "featured") as ProductCatalogMeta["sort"],
      label: option.label ?? "",
    })),
    facets: {
      categories: ensureArray(meta?.facets?.categories).map(normalizeProductCategory),
      models: ensureArray(meta?.facets?.models).map(normalizeProductFacetOption),
      finishes: ensureArray(meta?.facets?.finishes).map(
        normalizeProductFacetOption,
      ),
      colors: ensureArray(meta?.facets?.colors).map(normalizeProductFacetOption),
      stock_statuses: ensureArray(meta?.facets?.stock_statuses).map(
        normalizeProductFacetOption,
      ),
      use_cases: ensureArray(meta?.facets?.use_cases).map(
        normalizeProductFacetOption,
      ),
      price_range: {
        min: String(meta?.facets?.price_range?.min ?? "0.00"),
        max: String(meta?.facets?.price_range?.max ?? "0.00"),
      },
    },
    applied_filters: Object.fromEntries(
      Object.entries(meta?.applied_filters ?? {}).map(([key, value]) => [
        key,
        String(value),
      ]),
    ),
    applied_filter_chips: ensureArray(meta?.applied_filter_chips).map(
      normalizeProductAppliedFilterChip,
    ),
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
    estimated_shipping_usd:
      cart.estimated_shipping_usd === null ||
      cart.estimated_shipping_usd === undefined
        ? "0.00"
        : String(cart.estimated_shipping_usd),
    estimated_tax_usd:
      cart.estimated_tax_usd === null || cart.estimated_tax_usd === undefined
        ? "0.00"
        : String(cart.estimated_tax_usd),
    estimated_total_usd:
      cart.estimated_total_usd === null ||
      cart.estimated_total_usd === undefined
        ? String(cart.subtotal_usd ?? "0.00")
        : String(cart.estimated_total_usd),
    free_shipping_threshold_usd:
      cart.free_shipping_threshold_usd === null ||
      cart.free_shipping_threshold_usd === undefined
        ? "200.00"
        : String(cart.free_shipping_threshold_usd),
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
    item_count: Number(order.item_count ?? 0),
    subtotal_usd:
      order.subtotal_usd === null || order.subtotal_usd === undefined
        ? "0.00"
        : String(order.subtotal_usd),
    shipping_usd:
      order.shipping_usd === null || order.shipping_usd === undefined
        ? "0.00"
        : String(order.shipping_usd),
    tax_usd:
      order.tax_usd === null || order.tax_usd === undefined
        ? "0.00"
        : String(order.tax_usd),
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
    download_url: resolveApiUrl(media.download_url),
    is_image: Boolean(media.is_image),
    is_document: Boolean(media.is_document),
    is_external: Boolean(media.is_external),
    download_count: Number(media.download_count ?? 0),
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
    content_json: isJsonObject(post.content_json) ? post.content_json : null,
    cover_image_url: resolveApiUrl(post.cover_image_url),
    cover_image_path: post.cover_image_path ?? null,
    reading_time: Number(post.reading_time ?? 0),
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
  const replies = ensureArray(comment.replies).map(normalizeCommunityComment)

  return {
    ...comment,
    body: comment.body ?? comment.content ?? "",
    content: comment.content ?? comment.body ?? "",
    user: normalizeCommunityUser(comment.user),
    replies_count: Number(comment.replies_count ?? replies.length),
    replies,
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
