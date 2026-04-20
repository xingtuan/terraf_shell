import Link from "next/link"

import { ProductCard } from "@/components/store/ProductCard"
import { Button } from "@/components/ui/button"
import { getLocalizedHref, type Locale, type SiteMessages } from "@/lib/i18n"
import type { Product } from "@/lib/types"

type ProductGridSectionProps = {
  locale: Locale
  header: SiteMessages["header"]
  content: SiteMessages["storePage"]["grid"]
  products: Product[]
  activeCategory?: string | null
  hasError?: boolean
}

const categoryOptions = [
  { value: null, label: "All" },
  { value: "tableware", label: "Tableware" },
  { value: "planters", label: "Planters" },
  { value: "wellness_interior", label: "Wellness & Interior" },
  { value: "architectural", label: "Architectural" },
] as const

function buildCategoryHref(locale: Locale, category?: string | null) {
  const url = new URL(getLocalizedHref(locale, "store"), "https://shellfin.local")

  if (category) {
    url.searchParams.set("category", category)
  }

  url.hash = "products"

  return `${url.pathname}${url.search}${url.hash}`
}

export function ProductGridSection({
  locale,
  header: _header,
  content,
  products,
  activeCategory = null,
  hasError = false,
}: ProductGridSectionProps) {
  return (
    <section id="products" className="bg-background py-24 lg:py-28">
      <div className="mx-auto max-w-7xl px-6 lg:px-8">
        <div className="mb-10 max-w-3xl">
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

        <div className="mb-10 flex flex-wrap gap-3">
          {categoryOptions.map((option) => {
            const isActive = (option.value ?? null) === (activeCategory ?? null)

            return (
              <Button
                key={option.label}
                asChild
                variant={isActive ? "default" : "outline"}
                className="rounded-full"
              >
                <Link href={buildCategoryHref(locale, option.value)}>
                  {option.label}
                </Link>
              </Button>
            )
          })}
        </div>

        {hasError ? (
          <div className="mb-8 rounded-3xl border border-border/60 bg-card p-8">
            <h3 className="font-serif text-2xl text-foreground">
              Store data is temporarily unavailable.
            </h3>
            <p className="mt-3 max-w-2xl text-muted-foreground">
              The product catalogue could not be loaded from the API. The rest of
              the site is still available, and business inquiries can still be
              submitted.
            </p>
          </div>
        ) : null}

        {!hasError && products.length === 0 ? (
          <div className="rounded-3xl border border-dashed border-border/70 bg-card p-8 text-center">
            <h3 className="font-serif text-2xl text-foreground">
              No active products match this filter.
            </h3>
            <p className="mt-3 text-muted-foreground">
              Try another category or clear the filter to view the full Shellfin
              catalogue.
            </p>
          </div>
        ) : null}

        <div className="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
          {products.map((product) => (
            <ProductCard key={product.id} locale={locale} product={product} />
          ))}
        </div>
      </div>
    </section>
  )
}
