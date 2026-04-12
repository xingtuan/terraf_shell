import Image from "next/image"
import Link from "next/link"

import { Button } from "@/components/ui/button"
import {
  getLocalizedHref,
  type Locale,
  type SiteMessages,
} from "@/lib/i18n"
import type { Product, ProductCategory } from "@/lib/types"

type ProductGridSectionProps = {
  locale: Locale
  header: SiteMessages["header"]
  content: SiteMessages["storePage"]["grid"]
  products: Product[]
  categories: ProductCategory[]
}

export function ProductGridSection({
  locale,
  header,
  content,
  products,
  categories,
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
                {category.label}
              </p>
              <p className="text-sm leading-relaxed text-muted-foreground">
                {category.description}
              </p>
            </div>
          ))}
        </div>

        <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
          {products.map((product) => (
            <article
              key={product.id}
              className="overflow-hidden rounded-3xl border border-border/60 bg-card"
            >
              <div className="grid grid-cols-1 md:grid-cols-[1.05fr_0.95fr]">
                <div className="relative min-h-[320px]">
                  <Image
                    src={product.image}
                    alt={product.name}
                    fill
                    className="object-cover"
                  />
                </div>
                <div className="flex flex-col p-8">
                  <div className="mb-4 flex items-center justify-between gap-4">
                    <span className="rounded-full bg-primary/10 px-3 py-1 text-xs uppercase tracking-[0.18em] text-primary">
                      {product.categoryLabel}
                    </span>
                    <span className="text-sm text-muted-foreground">
                      {content.pricePrefix} {product.priceLabel}
                    </span>
                  </div>

                  <h3 className="mb-3 font-serif text-2xl text-foreground">
                    {product.name}
                  </h3>
                  <p className="mb-6 leading-relaxed text-muted-foreground">
                    {product.description}
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
                        {product.availability}
                      </p>
                    </div>
                    <Button asChild variant="outline">
                      <Link href={`${getLocalizedHref(locale, "contact")}#contact-form`}>
                        {header.contact}
                      </Link>
                    </Button>
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
