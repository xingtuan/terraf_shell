import Image from "next/image"
import Link from "next/link"

import { Button } from "@/components/ui/button"
import { formatProductPrice } from "@/lib/api/products"
import { getLocalizedHref, type Locale, type SiteMessages } from "@/lib/i18n"
import {
  getProductDetailHref,
  getProductInquiryHref,
  getProductSampleRequestHref,
} from "@/lib/product-links"
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

const categoryLabels: Record<string, string> = {
  tableware: "Tableware",
  planters: "Planters",
  wellness_interior: "Wellness & Interior",
  architectural: "Architectural",
}

const modelLabels: Record<string, string> = {
  lite_15: "1.5 Lite",
  heritage_16: "1.6 Heritage",
}

const finishLabels: Record<string, string> = {
  glossy: "Glossy",
  matte: "Matte",
}

const colorLabels: Record<string, string> = {
  ocean_bone: "Ocean Bone",
  forged_ash: "Forged Ash",
}

const techniqueLabels: Record<string, string> = {
  original_pure: "Original Pure",
  precision_inlay: "Precision Inlay",
  driftwood_blend: "Driftwood Blend",
}

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
  header,
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

        <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
          {products.map((product) => (
            <article
              key={product.id}
              className="overflow-hidden rounded-3xl border border-border/60 bg-card"
            >
              <div className="grid grid-cols-1 md:grid-cols-[1.05fr_0.95fr]">
                <div className="relative min-h-[320px]">
                  <Image
                    src={product.image_url || "/placeholder.jpg"}
                    alt={product.name}
                    fill
                    className="object-cover"
                  />
                </div>
                <div className="flex flex-col p-8">
                  <div className="mb-4 flex items-center justify-between gap-4">
                    <span className="rounded-full bg-primary/10 px-3 py-1 text-xs uppercase tracking-[0.18em] text-primary">
                      {categoryLabels[product.category] || product.category}
                    </span>
                    <span className="text-sm text-muted-foreground">
                      {content.pricePrefix} {formatProductPrice(product, locale)}
                    </span>
                  </div>

                  <h3 className="mb-3 font-serif text-2xl text-foreground">
                    {product.name}
                  </h3>
                  <p className="mb-6 leading-relaxed text-muted-foreground">
                    {[
                      modelLabels[product.model] || product.model,
                      finishLabels[product.finish] || product.finish,
                      colorLabels[product.color] || product.color,
                    ].join(" / ")}
                  </p>

                  <div className="mb-6 flex flex-wrap gap-2">
                    {[techniqueLabels[product.technique] || product.technique].map(
                      (feature) => (
                        <span
                          key={feature}
                          className="rounded-full border border-border/70 px-3 py-1 text-xs text-muted-foreground"
                        >
                          {feature}
                        </span>
                      ),
                    )}
                  </div>

                  <div className="mt-auto flex flex-col gap-4 border-t border-border/70 pt-6 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                      <p className="text-xs uppercase tracking-[0.18em] text-muted-foreground">
                        {content.availabilityLabel}
                      </p>
                      <p className="mt-1 text-sm text-foreground">
                        {product.in_stock ? "In stock" : "Available on inquiry"}
                      </p>
                    </div>
                    <div className="flex flex-wrap gap-3">
                      <Button asChild variant="outline">
                        <Link href={getProductDetailHref(locale, product.slug)}>
                          View details
                        </Link>
                      </Button>
                      <Button asChild variant="outline">
                        <Link href={getProductInquiryHref(locale, product)}>
                          {header.contact}
                        </Link>
                      </Button>
                      <Button asChild>
                        <Link href={getProductSampleRequestHref(locale, product)}>
                          {header.primaryCta}
                        </Link>
                      </Button>
                    </div>
                  </div>
                </div>
              </div>
            </article>
          ))}
        </div>
      </div>
    </section>
  )
}
