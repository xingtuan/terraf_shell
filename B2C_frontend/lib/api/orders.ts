import { normalizeStoreOrder } from "@/lib/api/adapters"
import { requestApi } from "@/lib/api/client"
import { ensureArray, normalizePaginationMeta } from "@/lib/api/normalizers"
import type { PaginatedResult, StoreOrder } from "@/lib/types"

export type CreateOrderPayload = {
  address_id?: number
  guest_email?: string
  shipping_method_code: string
  shipping_name?: string
  shipping_phone?: string
  shipping_address_line1?: string
  shipping_address_line2?: string
  shipping_city?: string
  shipping_state_province?: string
  shipping_postal_code?: string
  shipping_country?: string
  shipping_is_rural?: boolean | null
  customer_note?: string
}

type ApiRequestOverrides = {
  baseUrl?: string
}

export async function getOrders(
  token: string,
  page = 1,
  perPage = 10,
): Promise<PaginatedResult<StoreOrder>> {
  const response = await requestApi<StoreOrder[]>("/orders", {
    token,
    query: {
      page,
      per_page: perPage,
    },
  })

  const items = ensureArray(response.data).map(normalizeStoreOrder)

  return {
    items,
    meta: normalizePaginationMeta(response.meta, items.length),
  }
}

export async function getOrder(orderNumber: string, token: string) {
  const response = await requestApi<StoreOrder>(
    `/orders/${encodeURIComponent(orderNumber)}`,
    {
      token,
    },
  )

  return normalizeStoreOrder(response.data)
}

export async function getGuestOrder(
  orderNumber: string,
  token: string,
  options: ApiRequestOverrides = {},
) {
  const response = await requestApi<StoreOrder>(
    `/orders/guest/${encodeURIComponent(orderNumber)}`,
    {
      baseUrl: options.baseUrl,
      query: { token },
    },
  )

  return normalizeStoreOrder(response.data)
}

export async function createOrder(payload: CreateOrderPayload, token?: string | null) {
  const response = await requestApi<StoreOrder>("/orders", {
    method: "POST",
    token,
    body: payload,
  })

  return normalizeStoreOrder(response.data)
}

export async function cancelOrder(orderNumber: string, token: string) {
  const response = await requestApi<StoreOrder>(
    `/orders/${encodeURIComponent(orderNumber)}`,
    {
      method: "DELETE",
      token,
    },
  )

  return normalizeStoreOrder(response.data)
}
