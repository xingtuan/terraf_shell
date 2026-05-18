import type { SiteMessages } from "@/lib/i18n"

type ShippingMethodLabels = SiteMessages["shippingMethods"]
type ShippingMethodKey = keyof ShippingMethodLabels
type PaymentMethodLabels = SiteMessages["paymentMethods"]

type ShippingMethodLike = {
  code?: string | null
  label?: string | null
  description?: string | null
  service_code?: string | null
}

function normalizeKey(value?: string | null) {
  return value?.trim().toLowerCase().replace(/[\s-]+/g, "_") ?? ""
}

function humanize(value: string) {
  return value
    .replace(/[_-]+/g, " ")
    .replace(/\b\w/g, (letter) => letter.toUpperCase())
}

function resolveShippingMethodKey(
  method?: ShippingMethodLike | null,
): ShippingMethodKey | null {
  const keys = [
    normalizeKey(method?.code),
    normalizeKey(method?.service_code),
    normalizeKey(method?.label),
  ].filter(Boolean)

  if (keys.some((key) => ["standard", "fallback_standard_nz"].includes(key))) {
    return "standard"
  }

  if (
    keys.some((key) =>
      ["express", "priority", "priority_nz_delivery", "fallback_priority_nz"].includes(
        key,
      ),
    )
  ) {
    return "express"
  }

  if (keys.some((key) => key.includes("priority") || key.includes("express"))) {
    return "express"
  }

  if (keys.some((key) => key.includes("standard"))) {
    return "standard"
  }

  return null
}

export function getLocalizedShippingMethodLabel(
  method: ShippingMethodLike | null | undefined,
  labels: ShippingMethodLabels,
) {
  const key = resolveShippingMethodKey(method)

  if (key && labels[key]) {
    return labels[key].label
  }

  return method?.label ?? (method?.code ? humanize(method.code) : null)
}

export function getLocalizedShippingMethodDescription(
  method: ShippingMethodLike | null | undefined,
  labels: ShippingMethodLabels,
) {
  const key = resolveShippingMethodKey(method)

  if (key && labels[key]) {
    return labels[key].description
  }

  return method?.description ?? null
}

export function getPaymentMethodLabel(
  method: string | null | undefined,
  labels: PaymentMethodLabels,
) {
  const key = normalizeKey(method || "manual")

  return labels[key as keyof PaymentMethodLabels] ?? humanize(key)
}
