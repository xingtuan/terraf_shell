import type { CartSummaryItem, Product, ProductInventoryPolicy } from "@/lib/types"

const PROJECT_ENQUIRY_USE_CASES = new Set([
  "hospitality_service",
  "design_projects",
  "retail_gifting",
])

export function getProductAvailabilitySummary(
  product: Pick<Product, "lead_time" | "availability_text">,
  fallback: string,
) {
  return product.lead_time || product.availability_text || fallback
}

export function getProductQuantityLimit(
  product: Pick<Product, "stock_quantity" | "can_add_to_cart" | "default_variant">,
  fallback = 10,
) {
  if (!product.can_add_to_cart) {
    return 1
  }

  const variant = product.default_variant

  if (isOpenInventoryPolicy(variant?.inventory_policy)) {
    return fallback > 0 ? fallback : 99
  }

  if (typeof variant?.stock_quantity === "number" && variant.stock_quantity > 0) {
    return variant.stock_quantity
  }

  if (typeof product.stock_quantity === "number" && product.stock_quantity > 0) {
    return product.stock_quantity
  }

  return fallback
}

export function getCartItemQuantityLimit(
  item: CartSummaryItem,
  fallback = 10,
) {
  const variant = item.variant

  if (isOpenInventoryPolicy(variant?.inventory_policy ?? item.inventory_policy)) {
    return fallback > 0 ? fallback : 99
  }

  if (typeof variant?.stock_quantity === "number" && variant.stock_quantity > 0) {
    return variant.stock_quantity
  }

  if (
    typeof item.product?.stock_quantity === "number" &&
    item.product.stock_quantity > 0
  ) {
    return item.product.stock_quantity
  }

  if (typeof item.max_quantity === "number" && item.max_quantity > 0) {
    return item.max_quantity
  }

  return fallback
}

function isOpenInventoryPolicy(
  policy?: ProductInventoryPolicy | null,
) {
  return policy === "continue" || policy === "preorder"
}

export function supportsProjectEnquiry(
  product: Pick<Product, "inquiry_only" | "attributes">,
) {
  const applicationValues = (product.attributes ?? [])
    .filter((attribute) => ["use_case", "application"].includes(attribute.key ?? ""))
    .map((attribute) => String(attribute.value ?? attribute.display_label ?? ""))

  return Boolean(
    product.inquiry_only ||
      applicationValues.some((useCase) => PROJECT_ENQUIRY_USE_CASES.has(useCase)),
  )
}
