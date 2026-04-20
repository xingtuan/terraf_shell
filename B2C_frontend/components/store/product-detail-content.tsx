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
import type { Product, ProductImage } from "@/lib/types"

type ProductDetailContentProps = {
  locale: Locale
  header: SiteMessages["header"]
  product: Product
}

function resolveGallery(product: Product): ProductImage[] {
  if (product.gallery_images.length > 0) {
    return product.gallery_images
  }

  return product.cover_image_url
    ? [
        {
          id: 0,
          media_url: product.cover_image_url,
          alt_text: product.name,
          sort_order: 0,
        },
      ]
    : []
}

export function ProductDetailContent({
  locale,
  header,
  product,
}: ProductDetailContentProps) {
  const gallery = resolveGallery(product)
  const primaryImage = gallery[0]?.media_url || product.cover_image_url || "/placeholder.jpg"
  const priceLabel =
    product.inquiry_only || product.price_from === null
      ? "Inquiry only"
      : formatProductPrice(product, locale)

  return (
    <section className="bg-background py-20 lg:py-24">
      <div className="mx-auto max-w-7xl px-6 lg:px-8">
        <div className="grid grid-cols-1 gap-10 lg:grid-cols-[1.05fr_0.95fr]">
          <div>
            <div className="relative overflow-hidden rounded-3xl border border-border/60 bg-card">
              <div className="relative min-h-[420px]">
                <Image
                  src={primaryImage}
                  alt={gallery[0]?.alt_text || product.name}
                  fill
                  className="object-cover"
                />
              </div>
            </div>

            {gallery.length > 1 ? (
              <div className="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3">
                {gallery.slice(1).map((image) => (
                  <div
                    key={image.id}
                    className="relative overflow-hidden rounded-2xl border border-border/60 bg-card"
                  >
                    <div className="relative min-h-[160px]">
                      <Image
                        src={image.media_url || "/placeholder.jpg"}
                        alt={image.alt_text || product.name}
                        fill
                        className="object-cover"
                      />
                    </div>
                  </div>
                ))}
              </div>
            ) : null}
          </div>

          <div className="flex flex-col rounded-3xl border border-border/60 bg-card p-8 lg:p-10">
            <div className="mb-5 flex flex-wrap items-center gap-3">
              <span className="rounded-full bg-primary/10 px-3 py-1 text-xs uppercase tracking-[0.18em] text-primary">
                {product.category?.name || "Product"}
              </span>
              {product.featured ? (
                <span className="rounded-full border border-primary/20 px-3 py-1 text-xs uppercase tracking-[0.18em] text-primary">
                  Featured
                </span>
              ) : null}
            </div>

            <h2 className="font-serif text-3xl text-foreground md:text-4xl">
              {product.name}
            </h2>
            <p className="mt-4 text-lg leading-relaxed text-muted-foreground">
              {product.short_description}
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
                  {product.availability_text || "Available on inquiry"}
                </p>
              </div>
            </div>

            {product.features.length > 0 ? (
              <div className="mt-8 flex flex-wrap gap-2">
                {product.features.map((feature) => (
                  <span
                    key={feature}
                    className="rounded-full border border-border/70 px-3 py-1 text-xs text-muted-foreground"
                  >
                    {feature}
                  </span>
                ))}
              </div>
            ) : null}

            {product.full_description ? (
              <div className="mt-8 border-t border-border/70 pt-8">
                <h3 className="font-serif text-2xl text-foreground">
                  Product overview
                </h3>
                <p className="mt-4 whitespace-pre-line leading-relaxed text-muted-foreground">
                  {product.full_description}
                </p>
              </div>
            ) : null}

            <div className="mt-8 flex flex-wrap gap-3">
              <Button asChild>
                <Link href={getProductInquiryHref(locale, product)}>
                  {header.contact}
                </Link>
              </Button>
              {product.sample_request_enabled ? (
                <Button asChild variant="outline">
                  <Link href={getProductSampleRequestHref(locale, product)}>
                    {header.primaryCta}
                  </Link>
                </Button>
              ) : null}
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
                <Link href={getLocalizedHref(locale, "store")} className="text-foreground underline underline-offset-4">
                  Back to store
                </Link>
                <Link href={getLocalizedHref(locale, "contact")} className="text-foreground underline underline-offset-4">
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
