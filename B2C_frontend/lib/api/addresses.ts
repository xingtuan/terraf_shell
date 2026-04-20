import { normalizeAddress } from "@/lib/api/adapters"
import { requestApi } from "@/lib/api/client"
import { ensureArray } from "@/lib/api/normalizers"
import type { Address } from "@/lib/types"

export type AddressPayload = {
  label?: string
  recipient_name: string
  phone?: string
  address_line1: string
  address_line2?: string
  city: string
  state_province?: string
  postal_code?: string
  country: string
  is_default?: boolean
}

export async function listAddresses(token: string) {
  const response = await requestApi<Address[]>("/addresses", {
    token,
  })

  return ensureArray(response.data).map(normalizeAddress)
}

export async function createAddress(payload: AddressPayload, token: string) {
  const response = await requestApi<Address>("/addresses", {
    method: "POST",
    token,
    body: payload,
  })

  return normalizeAddress(response.data)
}

export async function updateAddress(
  id: number,
  payload: Partial<AddressPayload>,
  token: string,
) {
  const response = await requestApi<Address>(`/addresses/${id}`, {
    method: "PATCH",
    token,
    body: payload,
  })

  return normalizeAddress(response.data)
}

export async function deleteAddress(id: number, token: string) {
  await requestApi<{ deleted: boolean }>(`/addresses/${id}`, {
    method: "DELETE",
    token,
  })
}

export async function setDefaultAddress(id: number, token: string) {
  const response = await requestApi<Address>(`/addresses/${id}/default`, {
    method: "POST",
    token,
  })

  return normalizeAddress(response.data)
}
