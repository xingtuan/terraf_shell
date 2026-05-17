import { normalizeCartSummary } from "@/lib/api/adapters"
import { requestApi } from "@/lib/api/client"
import type { CartSummary } from "@/lib/types"

export type CartQuantityAdjustment = {
  type: "quantity_clamped"
  available_quantity: number
  requested_quantity: number
  product_variant_id?: number | null
  product_id?: number | null
  stock_status?: string | null
  inventory_policy?: string | null
  message?: string | null
}

export type AddCartItemResult = {
  cart: CartSummary
  message: string | null
  adjustment: CartQuantityAdjustment | null
}

function normalizeQuantityAdjustment(
  value: unknown,
): CartQuantityAdjustment | null {
  if (value === null || typeof value !== "object" || Array.isArray(value)) {
    return null
  }

  const adjustment = value as Record<string, unknown>
  const availableQuantity = Number(adjustment.available_quantity)
  const requestedQuantity = Number(adjustment.requested_quantity)

  if (
    adjustment.type !== "quantity_clamped" ||
    !Number.isFinite(availableQuantity) ||
    !Number.isFinite(requestedQuantity)
  ) {
    return null
  }

  return {
    type: "quantity_clamped",
    available_quantity: availableQuantity,
    requested_quantity: requestedQuantity,
    product_variant_id:
      adjustment.product_variant_id === null ||
      adjustment.product_variant_id === undefined
        ? null
        : Number(adjustment.product_variant_id),
    product_id:
      adjustment.product_id === null || adjustment.product_id === undefined
        ? null
        : Number(adjustment.product_id),
    stock_status:
      typeof adjustment.stock_status === "string" ? adjustment.stock_status : null,
    inventory_policy:
      typeof adjustment.inventory_policy === "string"
        ? adjustment.inventory_policy
        : null,
    message: typeof adjustment.message === "string" ? adjustment.message : null,
  }
}

export async function getCart(token?: string | null) {
  const response = await requestApi<CartSummary>("/cart", {
    token,
  })

  return normalizeCartSummary(response.data)
}

export async function addCartItem(
  productId: number,
  quantity: number,
  token?: string | null,
  variantId?: number | null,
) {
  const response = await requestApi<CartSummary>("/cart/items", {
    method: "POST",
    token,
    body: {
      product_id: productId,
      variant_id: variantId ?? undefined,
      quantity,
    },
  })

  const responseMeta =
    response.meta && "cart_adjustment" in response.meta
      ? response.meta
      : null

  return {
    cart: normalizeCartSummary(response.data),
    message: response.message,
    adjustment: normalizeQuantityAdjustment(responseMeta?.cart_adjustment),
  } satisfies AddCartItemResult
}

export async function updateCartItem(
  productId: number,
  quantity: number,
  token?: string | null,
  variantId?: number | null,
) {
  const response = await requestApi<CartSummary>(`/cart/items/${productId}`, {
    method: "PATCH",
    token,
    body: {
      variant_id: variantId ?? undefined,
      quantity,
    },
  })

  return normalizeCartSummary(response.data)
}

export async function removeCartItem(
  productId: number,
  token?: string | null,
  variantId?: number | null,
) {
  const response = await requestApi<CartSummary>(`/cart/items/${productId}`, {
    method: "DELETE",
    token,
    query: {
      variant_id: variantId ?? undefined,
    },
  })

  return normalizeCartSummary(response.data)
}

export async function clearCart(token?: string | null) {
  await requestApi<{ cleared: boolean }>("/cart", {
    method: "DELETE",
    token,
  })
}

export async function mergeGuestCart(
  sessionKey: string,
  token: string,
) {
  const response = await requestApi<CartSummary>("/cart/merge", {
    method: "POST",
    token,
    body: {
      session_key: sessionKey,
    },
  })

  return normalizeCartSummary(response.data)
}
