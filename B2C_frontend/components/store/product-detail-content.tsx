"use client"

import { useState } from "react"
import Image from "next/image"
import Link from "next/link"

import { ProductCard } from "@/components/store/ProductCard"
import { Button } from "@/components/ui/button"
import { formatProductPrice } from "@/lib/api/products"
import { getLocalizedHref, type Locale } from "@/lib/i18n"
import type { Product } from "@/lib/types"
import { useCart } from "@/hooks/useCart"

type ProductDetailContentProps = {
  locale: Locale
  product: Product
  relatedProducts: Product[]
}

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

const colorLabels: Record<string, string> = {
  ocean_bone: "Ocean Bone",
  forged_ash: "Forged Ash",
}

const colorTemperatureLabels: Record<string, string> = {
  ocean_bone: "Warm mineral white",
  forged_ash: "Smoked mineral grey",
}

const techniqueLabels: Record<string, string> = {
  original_pure: "Original Pure",
  precision_inlay: "Precision Inlay",
  driftwood_blend: "Driftwood Blend",
}

const productDescriptionMap: Record<string, string> = {
  "lite_15:ocean_bone":
    "A smooth brilliance in warm white, tuned for lighter everyday use and a quietly refined table presence.",
  "lite_15:forged_ash":
    "A slimmer shell body with a forged mineral tone, bringing graphic contrast and quick handling to daily service.",
  "heritage_16:ocean_bone":
    "A calmer matte expression with more grounded weight, designed for plated courses and premium hospitality settings.",
  "heritage_16:forged_ash":
    "A deeper, mineral-led silhouette with heritage density and a darker tactile surface for statement presentation.",
}

const processSteps = [
  "Collected oyster shell is reclaimed from coastal food systems.",
  "The shells are purified and mineral-tuned into Ocean Bone or Forged Ash.",
  "Shellfin pellets are refined for stable, repeatable forming.",
  "Compression moulding turns the material into lightweight finished objects.",
]

const certifications = [
  "Water 0%",
  "Toxicity Free",
  "Natural Antibacterial",
  "Impact Resistant",
]

export function ProductDetailContent({
  locale,
  product,
  relatedProducts,
}: ProductDetailContentProps) {
  const { addItem } = useCart()
  const priceLabel = formatProductPrice(product, locale)
  const [quantity, setQuantity] = useState(1)
  const description =
    productDescriptionMap[`${product.model}:${product.color}`] ||
    "Shellfin objects are formed from oyster shell material for lighter handling, durable daily use, and a calm mineral finish."

  return (
    <section className="bg-background py-20 lg:py-24">
      <div className="mx-auto max-w-7xl space-y-18 px-6 lg:px-8">
        <div className="grid grid-cols-1 gap-10 lg:grid-cols-[1.05fr_0.95fr]">
          <div className="relative overflow-hidden rounded-[2rem] border border-border/60 bg-card">
            <div className="relative min-h-[520px] bg-[radial-gradient(circle_at_top,rgba(255,255,255,0.4),transparent_60%)]">
              <Image
                src={product.image_url || "/placeholder.jpg"}
                alt={product.name}
                fill
                className="object-cover"
              />
            </div>
          </div>

          <div className="flex flex-col rounded-[2rem] border border-border/60 bg-card p-8 lg:p-10">
            <div className="flex flex-wrap items-center gap-3">
              <span className="rounded-full bg-primary/10 px-3 py-1 text-xs uppercase tracking-[0.18em] text-primary">
                {categoryLabels[product.category] || product.category}
              </span>
              <span className="rounded-full border border-border/70 px-3 py-1 text-xs uppercase tracking-[0.18em] text-muted-foreground">
                {modelLabels[product.model] || product.model}
              </span>
              <span className="rounded-full border border-border/70 px-3 py-1 text-xs uppercase tracking-[0.18em] text-muted-foreground">
                {(colorLabels[product.color] || product.color)} ·{" "}
                {colorTemperatureLabels[product.color] || "Mineral tone"}
              </span>
            </div>

            <h1 className="mt-6 font-serif text-4xl leading-tight text-foreground md:text-5xl">
              {product.name}
            </h1>
            <p className="mt-4 text-base leading-relaxed text-muted-foreground">
              {techniqueLabels[product.technique] || product.technique}
            </p>

            <div className="mt-8 flex flex-wrap items-end justify-between gap-6 border-b border-border/60 pb-8">
              <div>
                <p className="text-3xl font-medium text-foreground">{priceLabel} USD</p>
                <p
                  className={`mt-2 text-sm ${
                    product.in_stock ? "text-emerald-600" : "text-red-600"
                  }`}
                >
                  {product.in_stock ? "In Stock" : "Out of Stock"}
                </p>
              </div>

              <div className="flex flex-wrap items-center gap-3">
                <div className="flex items-center rounded-full border border-border/70">
                  <button
                    type="button"
                    className="px-4 py-2 text-foreground transition-colors hover:bg-muted"
                    onClick={() =>
                      setQuantity((currentValue) => Math.max(1, currentValue - 1))
                    }
                  >
                    −
                  </button>
                  <span className="min-w-10 text-center text-sm font-medium">
                    {quantity}
                  </span>
                  <button
                    type="button"
                    className="px-4 py-2 text-foreground transition-colors hover:bg-muted"
                    onClick={() =>
                      setQuantity((currentValue) => Math.min(10, currentValue + 1))
                    }
                  >
                    +
                  </button>
                </div>

                <Button
                  type="button"
                  disabled={!product.in_stock}
                  onClick={() => {
                    void addItem(product.id, quantity)
                  }}
                >
                  Add to Cart
                </Button>

                {!product.in_stock ? (
                  <Button type="button" variant="outline" disabled>
                    Notify Me
                  </Button>
                ) : null}
              </div>
            </div>

            <div className="mt-8 flex flex-wrap gap-3 text-sm text-muted-foreground">
              <span className="rounded-full border border-border/70 px-4 py-2">
                35% lighter
              </span>
              <span className="rounded-full border border-border/70 px-4 py-2">
                0% absorption
              </span>
              <span className="rounded-full border border-border/70 px-4 py-2">
                Natural antibacterial
              </span>
            </div>

            <div className="mt-8 space-y-4">
              <div>
                <p className="text-xs uppercase tracking-[0.18em] text-muted-foreground">
                  Product Description
                </p>
                <p className="mt-3 text-base leading-relaxed text-foreground">
                  {description}
                </p>
              </div>
              <div className="rounded-3xl bg-muted/40 p-5 text-sm leading-relaxed text-muted-foreground">
                Shellfin products are built from reclaimed oyster shell mineral pellets and compression moulded into durable objects for hospitality, retail, and home rituals.
              </div>
            </div>
          </div>
        </div>

        <section className="rounded-[2rem] border border-border/60 bg-card p-8 lg:p-10">
          <div className="flex flex-col gap-3 lg:flex-row lg:items-end lg:justify-between">
            <div>
              <p className="text-sm uppercase tracking-[0.2em] text-primary">
                From Shell to Table
              </p>
              <h2 className="mt-3 font-serif text-3xl text-foreground">
                The Shellfin process in four steps
              </h2>
            </div>
            <p className="max-w-2xl text-sm leading-relaxed text-muted-foreground">
              Every product begins with reclaimed shell material and ends as a durable object ready for premium daily use.
            </p>
          </div>

          <div className="mt-8 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            {processSteps.map((step, index) => (
              <div
                key={step}
                className="rounded-3xl border border-border/60 bg-background p-5"
              >
                <p className="text-xs uppercase tracking-[0.18em] text-primary">
                  Step {index + 1}
                </p>
                <p className="mt-4 text-sm leading-relaxed text-foreground">{step}</p>
              </div>
            ))}
          </div>
        </section>

        <section className="rounded-[2rem] border border-border/60 bg-card p-8 lg:p-10">
          <p className="text-sm uppercase tracking-[0.2em] text-primary">
            Certification
          </p>
          <div className="mt-6 flex flex-wrap gap-3">
            {certifications.map((badge) => (
              <span
                key={badge}
                className="rounded-full border border-border/70 px-4 py-2 text-sm text-foreground"
              >
                {badge}
              </span>
            ))}
          </div>
        </section>

        {relatedProducts.length > 0 ? (
          <section>
            <div className="mb-8 flex items-end justify-between gap-6">
              <div>
                <p className="text-sm uppercase tracking-[0.2em] text-primary">
                  You may also like
                </p>
                <h2 className="mt-3 font-serif text-3xl text-foreground">
                  More from this collection
                </h2>
              </div>
              <Button asChild variant="outline">
                <Link href={getLocalizedHref(locale, "store")}>
                  Browse all products
                </Link>
              </Button>
            </div>

            <div className="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
              {relatedProducts.map((relatedProduct) => (
                <ProductCard
                  key={relatedProduct.id}
                  locale={locale}
                  product={relatedProduct}
                />
              ))}
            </div>
          </section>
        ) : null}
      </div>
    </section>
  )
}
