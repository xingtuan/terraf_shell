import Link from "next/link"

import { ProductCard } from "@/components/store/ProductCard"
import { StoreFaq } from "@/components/store/store-faq"
import { StorePagination } from "@/components/store/StorePagination"
import { StoreTrustPanel } from "@/components/store/store-trust-panel"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { buildStoreCatalogHref, type StoreCatalogFilters } from "@/lib/store/catalog"
import { getLocalizedHref, type Locale, type SiteMessages } from "@/lib/i18n"
import type { Product, ProductCatalogMeta } from "@/lib/types"

type ProductGridSectionProps = {
  locale: Locale
  content: SiteMessages["storePage"]["grid"]
  faqContent: SiteMessages["storePage"]["faq"]
  trustContent: SiteMessages["home"]["credibility"]
  products: Product[]
  filters: StoreCatalogFilters
  meta?: ProductCatalogMeta | null
  hasError?: boolean
}

function selectedCategoryName(
  meta: ProductCatalogMeta | null | undefined,
  category: string,
) {
  return meta?.facets.categories.find((item) => item.slug === category)?.name
}

export function ProductGridSection({
  locale,
  content,
  faqContent,
  trustContent,
  products,
  filters,
  meta,
  hasError = false,
}: ProductGridSectionProps) {
  const activeCategoryLabel =
    filters.category !== "" ? selectedCategoryName(meta, filters.category) : null

  return (
    <section id="catalogue" className="bg-background py-24 lg:py-28">
      <div className="mx-auto max-w-7xl space-y-10 px-6 lg:px-8">
        <div className="max-w-3xl">
          <p className="mb-4 text-sm uppercase tracking-[0.2em] text-primary">
            {content.eyebrow}
          </p>
          <h2 className="mb-5 font-serif text-3xl leading-tight text-foreground md:text-4xl lg:text-5xl">
            {content.title}
          </h2>
          <p className="text-lg leading-relaxed text-muted-foreground">
            {content.description}
          </p>
        </div>

        <div className="flex flex-wrap items-center gap-3">
          <span className="text-sm uppercase tracking-[0.18em] text-muted-foreground">
            {content.categoryQuickFilterLabel}
          </span>
          <Button
            asChild
            variant={filters.category === "" ? "default" : "outline"}
            className="rounded-full"
          >
            <Link
              href={buildStoreCatalogHref(locale, {
                ...filters,
                category: "",
                page: 1,
              })}
            >
              All
            </Link>
          </Button>
          {(meta?.facets.categories ?? []).map((category) => (
            <Button
              key={category.slug}
              asChild
              variant={filters.category === category.slug ? "default" : "outline"}
              className="rounded-full"
            >
              <Link
                href={buildStoreCatalogHref(locale, {
                  ...filters,
                  category: category.slug,
                  page: 1,
                })}
              >
                {category.name}
              </Link>
            </Button>
          ))}
        </div>

        <div className="grid gap-8 xl:grid-cols-[0.34fr_0.66fr]">
          <aside className="rounded-[2rem] border border-border/60 bg-card p-6 lg:sticky lg:top-40 lg:h-fit">
            <form
              method="GET"
              action={getLocalizedHref(locale, "store")}
              className="space-y-6"
            >
              <div>
                <p className="text-sm uppercase tracking-[0.2em] text-primary">
                  {content.filtersTitle}
                </p>
                {activeCategoryLabel ? (
                  <p className="mt-3 text-sm leading-relaxed text-muted-foreground">
                    Showing products in {activeCategoryLabel}.
                  </p>
                ) : (
                  <p className="mt-3 text-sm leading-relaxed text-muted-foreground">
                    Search by product, finish, stock state, or use case and keep
                    the full filter state in the URL.
                  </p>
                )}
              </div>

              <div className="space-y-4">
                <label className="block space-y-2">
                  <span className="text-sm text-foreground">Search</span>
                  <Input
                    name="search"
                    defaultValue={filters.search}
                    placeholder={content.searchPlaceholder}
                  />
                </label>

                <div className="grid gap-4 sm:grid-cols-2">
                  <label className="block space-y-2">
                    <span className="text-sm text-foreground">{content.sortLabel}</span>
                    <select
                      name="sort"
                      defaultValue={filters.sort}
                      className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    >
                      {(meta?.sort_options ?? []).map((option) => (
                        <option key={option.value} value={option.value}>
                          {option.label}
                        </option>
                      ))}
                    </select>
                  </label>
                  <label className="block space-y-2">
                    <span className="text-sm text-foreground">
                      {content.modelLabel}
                    </span>
                    <select
                      name="model"
                      defaultValue={filters.model}
                      className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    >
                      <option value="">All</option>
                      {(meta?.facets.models ?? []).map((option) => (
                        <option key={option.value} value={option.value}>
                          {option.label} ({option.count})
                        </option>
                      ))}
                    </select>
                  </label>
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                  <label className="block space-y-2">
                    <span className="text-sm text-foreground">
                      {content.finishLabel}
                    </span>
                    <select
                      name="finish"
                      defaultValue={filters.finish}
                      className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    >
                      <option value="">All</option>
                      {(meta?.facets.finishes ?? []).map((option) => (
                        <option key={option.value} value={option.value}>
                          {option.label} ({option.count})
                        </option>
                      ))}
                    </select>
                  </label>
                  <label className="block space-y-2">
                    <span className="text-sm text-foreground">
                      {content.colorLabel}
                    </span>
                    <select
                      name="color"
                      defaultValue={filters.color}
                      className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    >
                      <option value="">All</option>
                      {(meta?.facets.colors ?? []).map((option) => (
                        <option key={option.value} value={option.value}>
                          {option.label} ({option.count})
                        </option>
                      ))}
                    </select>
                  </label>
                </div>

                <div className="grid gap-4 sm:grid-cols-2">
                  <label className="block space-y-2">
                    <span className="text-sm text-foreground">
                      {content.stockLabel}
                    </span>
                    <select
                      name="stock_status"
                      defaultValue={filters.stock_status}
                      className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    >
                      <option value="">All</option>
                      {(meta?.facets.stock_statuses ?? []).map((option) => (
                        <option key={option.value} value={option.value}>
                          {option.label} ({option.count})
                        </option>
                      ))}
                    </select>
                  </label>
                  <label className="block space-y-2">
                    <span className="text-sm text-foreground">
                      {content.useCaseLabel}
                    </span>
                    <select
                      name="use_case"
                      defaultValue={filters.use_case}
                      className="h-10 w-full rounded-md border border-input bg-background px-3 text-sm"
                    >
                      <option value="">All</option>
                      {(meta?.facets.use_cases ?? []).map((option) => (
                        <option key={option.value} value={option.value}>
                          {option.label} ({option.count})
                        </option>
                      ))}
                    </select>
                  </label>
                </div>

                <div className="space-y-2">
                  <span className="text-sm text-foreground">{content.priceLabel}</span>
                  <div className="grid gap-4 sm:grid-cols-2">
                    <Input
                      name="price_min"
                      defaultValue={filters.price_min}
                      placeholder={`${content.minPrice} ${
                        meta?.facets.price_range.min ?? "0.00"
                      }`}
                    />
                    <Input
                      name="price_max"
                      defaultValue={filters.price_max}
                      placeholder={`${content.maxPrice} ${
                        meta?.facets.price_range.max ?? "0.00"
                      }`}
                    />
                  </div>
                </div>
              </div>

              <div className="flex flex-wrap gap-3 border-t border-border/60 pt-2">
                <Button type="submit">{content.applyFilters}</Button>
                <Button asChild type="button" variant="outline">
                  <Link href={buildStoreCatalogHref(locale, { sort: "featured" })}>
                    {content.clearAll}
                  </Link>
                </Button>
              </div>
            </form>
          </aside>

          <div className="space-y-6">
            {hasError ? (
              <div className="rounded-[2rem] border border-border/60 bg-card p-8">
                <h3 className="font-serif text-2xl text-foreground">
                  {content.errorTitle}
                </h3>
                <p className="mt-3 max-w-2xl text-muted-foreground">
                  {content.errorDescription}
                </p>
                <Button asChild className="mt-6">
                  <Link href={buildStoreCatalogHref(locale, filters)}>
                    {content.retryAction}
                  </Link>
                </Button>
              </div>
            ) : null}

            {!hasError ? (
              <div className="flex flex-wrap items-end justify-between gap-4 rounded-[2rem] border border-border/60 bg-card p-6">
                <div>
                  <p className="text-sm uppercase tracking-[0.18em] text-muted-foreground">
                    {content.resultLabel.replace(
                      "{count}",
                      String(meta?.total ?? products.length),
                    )}
                  </p>
                  <h3 className="mt-2 font-serif text-2xl text-foreground">
                    {filters.search
                      ? `Results for "${filters.search}"`
                      : activeCategoryLabel
                        ? activeCategoryLabel
                        : "All products"}
                  </h3>
                </div>
                <p className="text-sm text-muted-foreground">
                  {content.showingLabel
                    .replace("{page}", String(meta?.current_page ?? 1))
                    .replace("{lastPage}", String(meta?.last_page ?? 1))}
                </p>
              </div>
            ) : null}

            {!hasError && products.length === 0 ? (
              <div className="rounded-[2rem] border border-dashed border-border/70 bg-card p-10 text-center">
                <h3 className="font-serif text-3xl text-foreground">
                  {content.emptyTitle}
                </h3>
                <p className="mx-auto mt-4 max-w-2xl text-muted-foreground">
                  {content.emptyDescription}
                </p>
                <Button asChild className="mt-6">
                  <Link href={buildStoreCatalogHref(locale, { sort: "featured" })}>
                    {content.emptyAction}
                  </Link>
                </Button>
              </div>
            ) : null}

            {!hasError && products.length > 0 ? (
              <>
                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                  {products.map((product) => (
                    <ProductCard key={product.id} locale={locale} product={product} />
                  ))}
                </div>

                {meta ? (
                  <StorePagination locale={locale} filters={filters} meta={meta} />
                ) : null}
              </>
            ) : null}
          </div>
        </div>

        <StoreTrustPanel content={trustContent} />
        <StoreFaq content={faqContent} />
      </div>
    </section>
  )
}
