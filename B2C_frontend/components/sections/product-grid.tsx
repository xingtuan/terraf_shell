import Image from "next/image"
import Link from "next/link"

import { Button } from "@/components/ui/button"
import { formatProductPrice } from "@/lib/api/products"
import { type Locale, type SiteMessages } from "@/lib/i18n"
import {
  getProductDetailHref,
  getProductInquiryHref,
  getProductSampleRequestHref,
} from "@/lib/product-links"
import type { Product, ProductCategory } from "@/lib/types"

type ProductGridSectionProps = {
  locale: Locale
  header: SiteMessages["header"]
  content: SiteMessages["storePage"]["grid"]
  products: Product[]
  categories: ProductCategory[]
  hasError?: boolean
}

export function ProductGridSection({
  locale,
  header,
  content,
  products,
  categories,
  hasError = false,
}: ProductGridSectionProps) {
  return (
    <section id="products" className="bg-background py-24 lg:py-28">
      <div className="mx-auto max-w-7xl px-6 lg:px-8">
        <div className="mb-14 max-w-3xl">
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

        <div className="mb-12 grid grid-cols-1 gap-4 md:grid-cols-3">
          {categories.map((category) => (
            <div key={category.id} className="rounded-2xl border border-border/60 bg-card p-6">
              <p className="mb-2 text-sm uppercase tracking-[0.18em] text-primary">
                {category.name}
              </p>
              <p className="text-sm leading-relaxed text-muted-foreground">
                {category.description}
              </p>
            </div>
          ))}
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

        {products.length === 0 ? (
          <div className="rounded-3xl border border-dashed border-border/70 bg-card p-8 text-center">
            <h3 className="font-serif text-2xl text-foreground">
              No published products are available yet.
            </h3>
            <p className="mt-3 text-muted-foreground">
              When the admin publishes product records, they will appear here
              automatically.
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
                    src={product.cover_image_url || "/placeholder.jpg"}
                    alt={product.name}
                    fill
                    className="object-cover"
                  />
                </div>
                <div className="flex flex-col p-8">
                  <div className="mb-4 flex items-center justify-between gap-4">
                    <span className="rounded-full bg-primary/10 px-3 py-1 text-xs uppercase tracking-[0.18em] text-primary">
                      {product.category?.name || "Product"}
                    </span>
                    <span className="text-sm text-muted-foreground">
                      {product.inquiry_only || product.price_from === null
                        ? "Inquiry only"
                        : `${content.pricePrefix} ${formatProductPrice(product, locale)}`}
                    </span>
                  </div>

                  <h3 className="mb-3 font-serif text-2xl text-foreground">
                    {product.name}
                  </h3>
                  <p className="mb-6 leading-relaxed text-muted-foreground">
                    {product.short_description}
                  </p>

                  <div className="mb-6 flex flex-wrap gap-2">
                    {product.features.map((feature) => (
                      <span
                        key={feature}
                        className="rounded-full border border-border/70 px-3 py-1 text-xs text-muted-foreground"
                      >
                        {feature}
                      </span>
                    ))}
                  </div>

                  <div className="mt-auto flex flex-col gap-4 border-t border-border/70 pt-6 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                      <p className="text-xs uppercase tracking-[0.18em] text-muted-foreground">
                        {content.availabilityLabel}
                      </p>
                      <p className="mt-1 text-sm text-foreground">
                        {product.availability_text || "Available on inquiry"}
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
                      {product.sample_request_enabled ? (
                        <Button asChild>
                          <Link href={getProductSampleRequestHref(locale, product)}>
                            {header.primaryCta}
                          </Link>
                        </Button>
                      ) : null}
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
