import {
  ApiError,
  getLocalizedErrorMessage,
} from "@/lib/api/client"
import type { CartQuantityAdjustment } from "@/lib/api/cart"
import type { SiteMessages } from "@/lib/i18n"

type CommonErrorMessages = SiteMessages["common"]["errors"]
type CartQuantityMessages = SiteMessages["cartQuantity"]

export function formatQuantityCountMessage(template: string, count: number) {
  return template.replace("{count}", String(count))
}

export function getCartAdjustmentMessage(
  adjustment: CartQuantityAdjustment | null | undefined,
  quantityMessages: CartQuantityMessages,
) {
  if (!adjustment) {
    return null
  }

  return adjustment.available_quantity > 0
    ? formatQuantityCountMessage(
        quantityMessages.addedMaximumAvailable,
        adjustment.available_quantity,
      )
    : quantityMessages.quantityUnavailable
}

export function getLocalizedCartQuantityErrorMessage(
  error: unknown,
  commonErrors: CommonErrorMessages,
  quantityMessages: CartQuantityMessages,
) {
  if (error instanceof ApiError) {
    const availableQuantity = Number(error.meta?.available_quantity)

    if (Number.isFinite(availableQuantity)) {
      return availableQuantity > 0
        ? formatQuantityCountMessage(
            quantityMessages.onlyAvailable,
            availableQuantity,
          )
        : quantityMessages.quantityUnavailable
    }
  }

  return getLocalizedErrorMessage(error, commonErrors)
}
