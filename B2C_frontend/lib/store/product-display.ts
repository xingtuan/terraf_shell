import type { Product } from "@/lib/types"

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
  product: Pick<Product, "stock_quantity" | "can_add_to_cart">,
  fallback = 10,
) {
  if (!product.can_add_to_cart) {
    return 1
  }

  if (typeof product.stock_quantity === "number" && product.stock_quantity > 0) {
    return Math.min(product.stock_quantity, 99)
  }

  return fallback
}

export function supportsProjectEnquiry(
  product: Pick<Product, "inquiry_only" | "use_cases">,
) {
  return Boolean(
    product.inquiry_only ||
      product.use_cases?.some((useCase) => PROJECT_ENQUIRY_USE_CASES.has(useCase)),
  )
}
