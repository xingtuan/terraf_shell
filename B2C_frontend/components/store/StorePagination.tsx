import {
  Pagination,
  PaginationContent,
  PaginationEllipsis,
  PaginationItem,
  PaginationLink,
  PaginationNext,
  PaginationPrevious,
} from "@/components/ui/pagination"
import { buildStoreCatalogHref, type StoreCatalogFilters } from "@/lib/store/catalog"
import type { ProductCatalogMeta } from "@/lib/types"
import type { Locale } from "@/lib/i18n"

type StorePaginationProps = {
  locale: Locale
  filters: StoreCatalogFilters
  meta: ProductCatalogMeta
}

function paginationRange(currentPage: number, lastPage: number) {
  const pages = new Set<number>([1, lastPage, currentPage, currentPage - 1, currentPage + 1])

  return [...pages]
    .filter((page) => page >= 1 && page <= lastPage)
    .sort((left, right) => left - right)
}

export function StorePagination({
  locale,
  filters,
  meta,
}: StorePaginationProps) {
  if (meta.last_page <= 1) {
    return null
  }

  const pages = paginationRange(meta.current_page, meta.last_page)

  return (
    <Pagination className="justify-start">
      <PaginationContent>
        <PaginationItem>
          {meta.current_page > 1 ? (
            <PaginationPrevious
              href={buildStoreCatalogHref(locale, {
                ...filters,
                page: meta.current_page - 1,
              })}
            />
          ) : (
            <PaginationPrevious
              href="#"
              aria-disabled="true"
              className="pointer-events-none opacity-50"
            />
          )}
        </PaginationItem>

        {pages.map((page, index) => {
          const previousPage = pages[index - 1]

          return (
            <PaginationItem key={page}>
              {previousPage !== undefined && page - previousPage > 1 ? (
                <PaginationEllipsis />
              ) : null}
              <PaginationLink
                href={buildStoreCatalogHref(locale, {
                  ...filters,
                  page,
                })}
                isActive={page === meta.current_page}
              >
                {page}
              </PaginationLink>
            </PaginationItem>
          )
        })}

        <PaginationItem>
          {meta.current_page < meta.last_page ? (
            <PaginationNext
              href={buildStoreCatalogHref(locale, {
                ...filters,
                page: meta.current_page + 1,
              })}
            />
          ) : (
            <PaginationNext
              href="#"
              aria-disabled="true"
              className="pointer-events-none opacity-50"
            />
          )}
        </PaginationItem>
      </PaginationContent>
    </Pagination>
  )
}
