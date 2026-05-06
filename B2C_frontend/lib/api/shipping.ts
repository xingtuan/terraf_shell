import { requestApi } from "@/lib/api/client"
import { ensureArray } from "@/lib/api/normalizers"
import type {
  AddressDetailsResponse,
  AddressSearchResponse,
  NzAddress,
  ShippingOption,
  ShippingQuote,
} from "@/lib/types"

function normalizeAddressSearchResponse(
  response: AddressSearchResponse,
): AddressSearchResponse {
  return {
    items: ensureArray(response.items).map((item) => ({
      id: String(item.id ?? ""),
      label: item.label ?? "",
      postcode: item.postcode ?? null,
      city: item.city ?? null,
      is_rural:
        item.is_rural === null || item.is_rural === undefined
          ? null
          : Boolean(item.is_rural),
    })),
    unavailable: Boolean(response.unavailable),
    source: response.source ?? "fallback",
  }
}

function normalizeAddressDetailsResponse(
  response: AddressDetailsResponse,
): AddressDetailsResponse {
  const address = response.address

  return {
    address: address
      ? {
          line1: address.line1 ?? "",
          line2: address.line2 ?? null,
          suburb: address.suburb ?? null,
          city: address.city ?? "",
          region: address.region ?? null,
          postcode: address.postcode ?? "",
          country: "NZ",
          is_rural:
            address.is_rural === null || address.is_rural === undefined
              ? null
              : Boolean(address.is_rural),
        }
      : null,
    unavailable: Boolean(response.unavailable),
    source: response.source ?? "fallback",
  }
}

function normalizeShippingOption(option: ShippingOption): ShippingOption {
  return {
    code: option.code ?? "",
    label: option.label ?? "",
    description: option.description ?? null,
    amount:
      option.amount === null || option.amount === undefined
        ? "0.00"
        : String(option.amount),
    currency: option.currency ?? "NZD",
    eta_min_days:
      option.eta_min_days === null || option.eta_min_days === undefined
        ? null
        : Number(option.eta_min_days),
    eta_max_days:
      option.eta_max_days === null || option.eta_max_days === undefined
        ? null
        : Number(option.eta_max_days),
    service_code: option.service_code ?? null,
    is_default: Boolean(option.is_default),
    source: option.source ?? "fallback",
    rural_surcharge:
      option.rural_surcharge === null || option.rural_surcharge === undefined
        ? null
        : String(option.rural_surcharge),
  }
}

function normalizeShippingQuote(quote: ShippingQuote): ShippingQuote {
  return {
    options: ensureArray(quote.options).map(normalizeShippingOption),
    tax: {
      label: quote.tax?.label ?? "GST included",
      rate: Number(quote.tax?.rate ?? 0.15),
      amount:
        quote.tax?.amount === null || quote.tax?.amount === undefined
          ? "0.00"
          : String(quote.tax.amount),
      included: Boolean(quote.tax?.included ?? true),
    },
    totals: {
      subtotal: String(quote.totals?.subtotal ?? "0.00"),
      shipping: String(quote.totals?.shipping ?? "0.00"),
      tax: String(quote.totals?.tax ?? "0.00"),
      total: String(quote.totals?.total ?? "0.00"),
      currency: quote.totals?.currency ?? "NZD",
    },
  }
}

export async function searchAddresses(query: string) {
  const response = await requestApi<AddressSearchResponse>(
    "/store/address-search",
    {
      query: { query },
    },
  )

  return normalizeAddressSearchResponse(response.data)
}

export async function getAddressDetails(id: string) {
  const response = await requestApi<AddressDetailsResponse>(
    "/store/address-details",
    {
      query: { id },
    },
  )

  return normalizeAddressDetailsResponse(response.data)
}

export async function getShippingOptions(address: NzAddress) {
  const response = await requestApi<ShippingQuote>("/store/shipping-options", {
    method: "POST",
    body: {
      address,
    },
  })

  return normalizeShippingQuote(response.data)
}
