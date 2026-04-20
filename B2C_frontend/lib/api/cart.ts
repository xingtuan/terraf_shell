import { normalizeCartSummary } from "@/lib/api/adapters"
import { requestApi } from "@/lib/api/client"
import type { CartSummary } from "@/lib/types"

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
) {
  const response = await requestApi<CartSummary>("/cart/items", {
    method: "POST",
    token,
    body: {
      product_id: productId,
      quantity,
    },
  })

  return normalizeCartSummary(response.data)
}

export async function updateCartItem(
  productId: number,
  quantity: number,
  token?: string | null,
) {
  const response = await requestApi<CartSummary>(`/cart/items/${productId}`, {
    method: "PATCH",
    token,
    body: {
      quantity,
    },
  })

  return normalizeCartSummary(response.data)
}

export async function removeCartItem(productId: number, token?: string | null) {
  const response = await requestApi<CartSummary>(`/cart/items/${productId}`, {
    method: "DELETE",
    token,
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
