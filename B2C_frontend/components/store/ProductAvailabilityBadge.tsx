"use client"

import type { Product } from "@/lib/types"
import { cn } from "@/lib/utils"

type ProductAvailabilityBadgeProps = {
  product: Pick<Product, "stock_status" | "stock_status_label" | "in_stock">
  fallbackLabel: string
  className?: string
}

function productAvailabilityTone(status?: Product["stock_status"]) {
  switch (status) {
    case "low_stock":
      return "bg-amber-100 text-amber-700"
    case "sold_out":
      return "bg-red-100 text-red-700"
    case "preorder":
    case "made_to_order":
      return "bg-sky-100 text-sky-700"
    default:
      return "bg-emerald-100 text-emerald-700"
  }
}

export function ProductAvailabilityBadge({
  product,
  fallbackLabel,
  className,
}: ProductAvailabilityBadgeProps) {
  return (
    <span
      className={cn(
        "rounded-full px-3 py-1 text-[11px] uppercase tracking-[0.18em]",
        productAvailabilityTone(product.stock_status),
        className,
      )}
    >
      {product.stock_status_label || fallbackLabel}
    </span>
  )
}
