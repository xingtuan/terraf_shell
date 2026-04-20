import Image from "next/image"
import Link from "next/link"

import { Button } from "@/components/ui/button"
import { formatProductPrice } from "@/lib/api/products"
import { getLocalizedHref, type Locale, type SiteMessages } from "@/lib/i18n"
import {
  getProductDevelopmentHref,
  getProductInquiryHref,
  getProductSampleRequestHref,
} from "@/lib/product-links"
import type { Product } from "@/lib/types"

type ProductDetailContentProps = {
  locale: Locale
  header: SiteMessages["header"]
  product: Product
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

export function ProductDetailContent({
  locale,
  header,
  product,
}: ProductDetailContentProps) {
  const priceLabel = formatProductPrice(product, locale)

  return (
    <section className="bg-background py-20 lg:py-24">
      <div className="mx-auto max-w-7xl px-6 lg:px-8">
        <div className="grid grid-cols-1 gap-10 lg:grid-cols-[1.05fr_0.95fr]">
          <div>
            <div className="relative overflow-hidden rounded-3xl border border-border/60 bg-card">
              <div className="relative min-h-[420px]">
                <Image
                  src={product.image_url || "/placeholder.jpg"}
                  alt={product.name}
                  fill
                  className="object-cover"
                />
              </div>
            </div>
          </div>

          <div className="flex flex-col rounded-3xl border border-border/60 bg-card p-8 lg:p-10">
            <div className="mb-5 flex flex-wrap items-center gap-3">
              <span className="rounded-full bg-primary/10 px-3 py-1 text-xs uppercase tracking-[0.18em] text-primary">
                {categoryLabels[product.category] || product.category}
              </span>
              <span className="rounded-full border border-primary/20 px-3 py-1 text-xs uppercase tracking-[0.18em] text-primary">
                {product.in_stock ? "In stock" : "Inquiry"}
              </span>
            </div>

            <h2 className="font-serif text-3xl text-foreground md:text-4xl">
              {product.name}
            </h2>
            <p className="mt-4 text-lg leading-relaxed text-muted-foreground">
              Built in the Shellfin material system with a {finishLabels[product.finish] || product.finish.toLowerCase()}{" "}
              finish, {colorLabels[product.color] || product.color} colorway, and{" "}
              {techniqueLabels[product.technique] || product.technique} technique.
            </p>

            <div className="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div className="rounded-2xl border border-border/60 bg-background p-5">
                <p className="text-xs uppercase tracking-[0.16em] text-muted-foreground">
                  Price
                </p>
                <p className="mt-2 text-lg text-foreground">{priceLabel}</p>
              </div>
              <div className="rounded-2xl border border-border/60 bg-background p-5">
                <p className="text-xs uppercase tracking-[0.16em] text-muted-foreground">
                  Availability
                </p>
                <p className="mt-2 text-lg text-foreground">
                  {product.in_stock ? "Ready for order" : "Available on inquiry"}
                </p>
              </div>
            </div>

            <div className="mt-8 grid grid-cols-1 gap-4 sm:grid-cols-2">
              <div className="rounded-2xl border border-border/60 bg-background p-5">
                <p className="text-xs uppercase tracking-[0.16em] text-muted-foreground">
                  Model
                </p>
                <p className="mt-2 text-foreground">
                  {modelLabels[product.model] || product.model}
                </p>
              </div>
              <div className="rounded-2xl border border-border/60 bg-background p-5">
                <p className="text-xs uppercase tracking-[0.16em] text-muted-foreground">
                  Finish
                </p>
                <p className="mt-2 text-foreground">
                  {finishLabels[product.finish] || product.finish}
                </p>
              </div>
              <div className="rounded-2xl border border-border/60 bg-background p-5">
                <p className="text-xs uppercase tracking-[0.16em] text-muted-foreground">
                  Color
                </p>
                <p className="mt-2 text-foreground">
                  {colorLabels[product.color] || product.color}
                </p>
              </div>
              <div className="rounded-2xl border border-border/60 bg-background p-5">
                <p className="text-xs uppercase tracking-[0.16em] text-muted-foreground">
                  Technique
                </p>
                <p className="mt-2 text-foreground">
                  {techniqueLabels[product.technique] || product.technique}
                </p>
              </div>
            </div>

            <div className="mt-8 flex flex-wrap gap-3">
              <Button asChild>
                <Link href={getProductInquiryHref(locale, product)}>
                  {header.contact}
                </Link>
              </Button>
              <Button asChild variant="outline">
                <Link href={getProductSampleRequestHref(locale, product)}>
                  {header.primaryCta}
                </Link>
              </Button>
              <Button asChild variant="outline">
                <Link href={getProductDevelopmentHref(locale, product)}>
                  Start development
                </Link>
              </Button>
            </div>

            <div className="mt-10 border-t border-border/70 pt-8 text-sm text-muted-foreground">
              <p>
                Need a broader retail, hospitality, or collaboration conversation?
              </p>
              <div className="mt-4 flex flex-wrap gap-4">
                <Link
                  href={getLocalizedHref(locale, "store")}
                  className="text-foreground underline underline-offset-4"
                >
                  Back to store
                </Link>
                <Link
                  href={getLocalizedHref(locale, "contact")}
                  className="text-foreground underline underline-offset-4"
                >
                  Contact team
                </Link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  )
}
