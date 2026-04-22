import Link from "next/link"
import { X } from "lucide-react"

import {
  buildStoreCatalogHref,
  clearStoreCatalogFilters,
  removeStoreCatalogFilter,
  type StoreCatalogFilterChipKey,
  type StoreCatalogFilters,
} from "@/lib/store/catalog"
import { type Locale, type SiteMessages } from "@/lib/i18n"
import type { ProductAppliedFilterChip } from "@/lib/types"

type StoreActiveFiltersProps = {
  locale: Locale
  filters: StoreCatalogFilters
  chips: ProductAppliedFilterChip[]
  content: SiteMessages["storePage"]["grid"]
}

function filterLabel(
  key: ProductAppliedFilterChip["key"],
  content: SiteMessages["storePage"]["grid"],
) {
  switch (key) {
    case "search":
      return content.searchLabel
    case "category":
      return content.categoryQuickFilterLabel
    case "model":
      return content.modelLabel
    case "finish":
      return content.finishLabel
    case "color":
      return content.colorLabel
    case "stock_status":
      return content.stockLabel
    case "use_case":
      return content.useCaseLabel
    case "price":
      return content.priceLabel
    default:
      return key
  }
}

export function StoreActiveFilters({
  locale,
  filters,
  chips,
  content,
}: StoreActiveFiltersProps) {
  if (chips.length === 0) {
    return null
  }

  return (
    <div className="rounded-[1.75rem] border border-border/60 bg-card p-5">
      <div className="flex flex-wrap items-center justify-between gap-3">
        <p className="text-sm uppercase tracking-[0.18em] text-muted-foreground">
          {content.activeFiltersLabel}
        </p>
        <Link
          href={buildStoreCatalogHref(
            locale,
            clearStoreCatalogFilters({ sort: filters.sort }),
          )}
          className="text-sm text-primary transition-colors hover:text-primary/80"
        >
          {content.clearAll}
        </Link>
      </div>

      <div className="mt-4 flex flex-wrap gap-2">
        {chips.map((chip) => {
          const label = filterLabel(chip.key, content)

          return (
            <Link
              key={`${chip.key}-${chip.value}`}
              href={buildStoreCatalogHref(
                locale,
                removeStoreCatalogFilter(
                  filters,
                  chip.key as StoreCatalogFilterChipKey,
                ),
              )}
              className="inline-flex items-center gap-2 rounded-full border border-border/60 bg-background px-4 py-2 text-sm text-foreground transition-colors hover:border-foreground/30"
              aria-label={content.removeFilterLabel.replace("{label}", label)}
            >
              <span className="text-xs uppercase tracking-[0.18em] text-muted-foreground">
                {label}
              </span>
              <span>{chip.display}</span>
              <X className="size-3.5 text-muted-foreground" />
            </Link>
          )
        })}
      </div>
    </div>
  )
}
