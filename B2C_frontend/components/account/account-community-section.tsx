"use client"

import type { ReactNode } from "react"
import Link from "next/link"

import { Button } from "@/components/ui/button"
import type { ApiPaginationMeta } from "@/lib/types"
import { AccountPanel } from "@/components/account/account-ui"

type AccountCommunitySectionProps<T> = {
  title: string
  description?: string
  items: T[]
  meta?: ApiPaginationMeta | null
  isLoading?: boolean
  loadingText?: string
  emptyState: ReactNode
  renderItem: (item: T) => ReactNode
  onLoadMore?: () => void
  viewAllHref?: string
  loadingMore?: boolean
  error?: string | null
  viewAllLabel: string
  loadMoreLabel: string
  loadingMoreLabel: string
  showingCountLabel: string
  noMoreItemsLabel?: string
  className?: string
  itemClassName?: string
}

export function AccountCommunitySection<T>({
  title,
  description,
  items,
  meta,
  isLoading = false,
  loadingText,
  emptyState,
  renderItem,
  onLoadMore,
  viewAllHref,
  loadingMore = false,
  error,
  viewAllLabel,
  loadMoreLabel,
  loadingMoreLabel,
  showingCountLabel,
  noMoreItemsLabel,
  className,
  itemClassName = "space-y-4",
}: AccountCommunitySectionProps<T>) {
  const shown = items.length
  const total = meta?.total ?? shown
  const hasMore = total > shown
  const showingText = showingCountLabel
    .replace("{shown}", String(shown))
    .replace("{total}", String(total))

  return (
    <AccountPanel className={className}>
      <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <p className="text-sm uppercase tracking-[0.18em] text-primary">
            {title}
          </p>
          <h2 className="mt-3 font-serif text-3xl text-foreground">{title}</h2>
          {description ? (
            <p className="mt-3 text-sm leading-relaxed text-muted-foreground">
              {description}
            </p>
          ) : null}
          <p className="mt-3 text-sm text-muted-foreground">{showingText}</p>
        </div>

        {viewAllHref ? (
          <Button asChild variant="outline" size="sm">
            <Link href={viewAllHref}>{viewAllLabel}</Link>
          </Button>
        ) : null}
      </div>

      {error ? (
        <div className="mt-5 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
          {error}
        </div>
      ) : null}

      {isLoading ? (
        <div className="mt-6 rounded-[1.5rem] border border-border/60 bg-background/70 p-5 text-sm text-muted-foreground">
          {loadingText}
        </div>
      ) : shown > 0 ? (
        <div className={`mt-6 ${itemClassName}`}>
          {items.map((item) => renderItem(item))}
        </div>
      ) : (
        <div className="mt-6">{emptyState}</div>
      )}

      {!isLoading && shown > 0 ? (
        <div className="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-border/60 pt-5">
          <p className="text-sm text-muted-foreground">
            {hasMore ? showingText : (noMoreItemsLabel ?? showingText)}
          </p>
          {hasMore && onLoadMore ? (
            <Button
              type="button"
              variant="outline"
              disabled={loadingMore}
              onClick={onLoadMore}
            >
              {loadingMore ? loadingMoreLabel : loadMoreLabel}
            </Button>
          ) : null}
        </div>
      ) : null}
    </AccountPanel>
  )
}
