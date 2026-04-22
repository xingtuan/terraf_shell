import { getIntlLocale, type Locale } from "@/lib/i18n"
import type { Address, StoreOrder } from "@/lib/types"

export function formatAccountDate(
  locale: Locale,
  value?: string | null,
  options: Intl.DateTimeFormatOptions = {
    year: "numeric",
    month: "short",
    day: "numeric",
  },
) {
  if (!value) {
    return null
  }

  return new Intl.DateTimeFormat(getIntlLocale(locale), options).format(
    new Date(value),
  )
}

export function formatAccountMonthYear(locale: Locale, value?: string | null) {
  return formatAccountDate(locale, value, {
    year: "numeric",
    month: "long",
  })
}

export function getOrderStatusClasses(status: StoreOrder["status"]) {
  switch (status) {
    case "confirmed":
      return "bg-sky-100 text-sky-700"
    case "processing":
      return "bg-amber-100 text-amber-700"
    case "shipped":
      return "bg-violet-100 text-violet-700"
    case "delivered":
      return "bg-emerald-100 text-emerald-700"
    case "cancelled":
      return "bg-red-100 text-red-700"
    default:
      return "bg-muted text-foreground"
  }
}

export function getDefaultAddress(addresses: Address[]) {
  return addresses.find((address) => address.is_default) ?? addresses[0] ?? null
}

export function formatAddressSummary(address: Address) {
  return [
    address.address_line1,
    address.address_line2,
    [
      address.city,
      address.state_province,
      address.postal_code,
    ]
      .filter(Boolean)
      .join(", "),
    address.country,
  ]
    .filter(Boolean)
    .join(", ")
}
