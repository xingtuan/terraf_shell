"use client"

import { useState } from "react"
import Link from "next/link"

import { ProductGallery } from "@/components/store/ProductGallery"
import { ProductCard } from "@/components/store/ProductCard"
import { Button } from "@/components/ui/button"
import {
  formatCurrencyAmount,
  formatProductPrice,
} from "@/lib/api/products"
import { getLocalizedHref, getMessages, type Locale } from "@/lib/i18n"
import {
  getProductInquiryHref,
  getProductSampleRequestHref,
} from "@/lib/product-links"
import type { Product } from "@/lib/types"
import { useCart } from "@/hooks/useCart"

type ProductDetailContentProps = {
  locale: Locale
  product: Product
}

function stockTone(product: Product) {
  switch (product.stock_status) {
    case "low_stock":
      return "bg-amber-100 text-amber-700"
    case "sold_out":
      return "bg-red-100 text-red-700"
    case "preorder":
    case "made_to_order":
      return "bg-sky-100 text-sky-700"
    default:
      return "bg-emerald-100 text-emerald-700"
  }
}

export function ProductDetailContent({
  locale,
  product,
}: ProductDetailContentProps) {
  const { addItem } = useCart()
  const t = getMessages(locale).productDetail
  const [quantity, setQuantity] = useState(1)
  const relatedProducts = product.related_products ?? []
  const highlightedSpecs = product.specifications?.slice(0, 6) ?? []

  return (
    <section className="bg-background py-20 lg:py-24">
      <div className="mx-auto max-w-7xl space-y-12 px-6 lg:px-8">
        <div className="grid grid-cols-1 gap-10 lg:grid-cols-[1.02fr_0.98fr]">
          <ProductGallery
            title={product.name}
            images={product.gallery_images ?? []}
          />

          <div className="flex flex-col rounded-[2rem] border border-border/60 bg-card p-8 lg:p-10">
            <div className="flex flex-wrap items-center gap-3">
              <span className="rounded-full bg-primary/10 px-3 py-1 text-xs uppercase tracking-[0.18em] text-primary">
                {product.category_label || product.category}
              </span>
              {product.model_label ? (
                <span className="rounded-full border border-border/70 px-3 py-1 text-xs uppercase tracking-[0.18em] text-muted-foreground">
                  {product.model_label}
                </span>
              ) : null}
              {product.color_label ? (
                <span className="rounded-full border border-border/70 px-3 py-1 text-xs uppercase tracking-[0.18em] text-muted-foreground">
                  {product.color_label}
                </span>
              ) : null}
              {product.is_new ? (
                <span className="rounded-full bg-primary px-3 py-1 text-xs uppercase tracking-[0.18em] text-primary-foreground">
                  {t.newBadge}
                </span>
              ) : null}
              {product.is_bestseller ? (
                <span className="rounded-full bg-foreground px-3 py-1 text-xs uppercase tracking-[0.18em] text-background">
                  {t.bestSellerBadge}
                </span>
              ) : null}
            </div>

            <h1 className="mt-6 font-serif text-4xl leading-tight text-foreground md:text-5xl">
              {product.name}
            </h1>

            {product.subtitle ? (
              <p className="mt-4 text-base leading-relaxed text-muted-foreground">
                {product.subtitle}
              </p>
            ) : null}

            <div className="mt-8 rounded-[1.75rem] border border-border/60 bg-background p-6">
              <div className="flex flex-wrap items-start justify-between gap-6">
                <div>
                  <div className="flex flex-wrap items-end gap-3">
                    <p className="text-3xl font-medium text-foreground">
                      {formatProductPrice(product, locale)}
                    </p>
                    {product.compare_at_price_usd ? (
                      <p className="pb-1 text-base text-muted-foreground line-through">
                        {formatCurrencyAmount(
                          product.compare_at_price_usd,
                          locale,
                          product.currency ?? "USD",
                        )}
                      </p>
                    ) : null}
                  </div>
                  <div className="mt-3 flex flex-wrap items-center gap-3">
                    <span
                      className={`rounded-full px-3 py-1 text-xs uppercase tracking-[0.18em] ${stockTone(
                        product,
                      )}`}
                    >
                      {product.stock_status_label || "Availability"}
                    </span>
                    {product.lead_time ? (
                      <span className="text-sm text-muted-foreground">
                        {product.lead_time}
                      </span>
                    ) : null}
                  </div>
                </div>

                {product.stock_quantity !== null &&
                product.stock_status !== "sold_out" ? (
                  <div className="rounded-2xl border border-border/60 px-4 py-3 text-right">
                    <p className="text-xs uppercase tracking-[0.18em] text-muted-foreground">
                      {t.batchStock}
                    </p>
                    <p className="mt-2 text-xl font-medium text-foreground">
                      {product.stock_quantity}
                    </p>
                  </div>
                ) : null}
              </div>

              <div className="mt-6 flex flex-wrap gap-3">
                <div className="flex items-center rounded-full border border-border/70">
                  <button
                    type="button"
                    className="px-4 py-2 text-foreground transition-colors hover:bg-muted"
                    onClick={() =>
                      setQuantity((currentValue) => Math.max(1, currentValue - 1))
                    }
                  >
                    -
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

                {product.can_add_to_cart ? (
                  <Button
                    type="button"
                    onClick={() => {
                      void addItem(product.id, quantity)
                    }}
                  >
                    {t.addToCart}
                  </Button>
                ) : (
                  <Button asChild>
                    <Link href={getProductInquiryHref(locale, product)}>
                      {product.inquiry_only ? t.bulkEnquiry : t.requestUpdate}
                    </Link>
                  </Button>
                )}

                {product.sample_request_enabled ? (
                  <Button asChild variant="outline">
                    <Link href={getProductSampleRequestHref(locale, product)}>
                      {t.requestSample}
                    </Link>
                  </Button>
                ) : null}
              </div>

              {!product.can_add_to_cart ? (
                <div className="mt-5 rounded-2xl border border-dashed border-border/70 bg-card p-4 text-sm leading-relaxed text-muted-foreground">
                  {product.stock_status === "sold_out"
                    ? t.soldOutMessage
                    : t.bulkOrderMessage}
                </div>
              ) : null}
            </div>

            {product.features?.length ? (
              <div className="mt-8 flex flex-wrap gap-3 text-sm text-muted-foreground">
                {product.features.map((feature) => (
                  <span
                    key={feature}
                    className="rounded-full border border-border/70 px-4 py-2"
                  >
                    {feature}
                  </span>
                ))}
              </div>
            ) : null}

            {product.long_description ? (
              <div className="mt-8 rounded-[1.75rem] bg-muted/40 p-6">
                <p className="text-xs uppercase tracking-[0.18em] text-muted-foreground">
                  {t.productStory}
                </p>
                <p className="mt-4 text-base leading-relaxed text-foreground">
                  {product.long_description}
                </p>
              </div>
            ) : null}
          </div>
        </div>

        <div className="grid gap-6 lg:grid-cols-[1.05fr_0.95fr]">
          <section className="rounded-[2rem] border border-border/60 bg-card p-8 lg:p-10">
            <div className="flex items-end justify-between gap-6">
              <div>
                <p className="text-sm uppercase tracking-[0.2em] text-primary">
                  {t.specificationEyebrow}
                </p>
                <h2 className="mt-3 font-serif text-3xl text-foreground">
                  {t.specificationTitle}
                </h2>
              </div>
              {product.availability_text ? (
                <p className="max-w-sm text-sm leading-relaxed text-muted-foreground">
                  {product.availability_text}
                </p>
              ) : null}
            </div>

            <div className="mt-8 grid gap-4 sm:grid-cols-2">
              {highlightedSpecs.map((specification) => (
                <article
                  key={`${specification.key}-${specification.label}`}
                  className="rounded-3xl border border-border/60 bg-background p-5"
                >
                  <p className="text-xs uppercase tracking-[0.18em] text-muted-foreground">
                    {specification.label}
                  </p>
                  <p className="mt-3 text-lg font-medium text-foreground">
                    {specification.value}
                    {specification.unit ? ` ${specification.unit}` : ""}
                  </p>
                  {specification.group ? (
                    <p className="mt-2 text-sm text-muted-foreground">
                      {specification.group}
                    </p>
                  ) : null}
                </article>
              ))}
            </div>
          </section>

          <section className="grid gap-6">
            <article className="rounded-[2rem] border border-border/60 bg-card p-8">
              <p className="text-sm uppercase tracking-[0.2em] text-primary">
                {t.materialBenefits}
              </p>
              <div className="mt-6 space-y-4">
                {(product.material_benefits ?? []).map((benefit) => (
                  <div key={benefit} className="flex gap-3">
                    <span className="mt-1 size-2 shrink-0 rounded-full bg-primary" />
                    <p className="text-sm leading-relaxed text-foreground">
                      {benefit}
                    </p>
                  </div>
                ))}
              </div>
            </article>

            <article className="rounded-[2rem] border border-border/60 bg-card p-8">
              <p className="text-sm uppercase tracking-[0.2em] text-primary">
                {t.careAndCertification}
              </p>
              <div className="mt-6 grid gap-6 md:grid-cols-2">
                <div>
                  <h3 className="font-medium text-foreground">{t.care}</h3>
                  <div className="mt-3 space-y-3">
                    {(product.care_instructions ?? []).map((instruction) => (
                      <p
                        key={instruction}
                        className="text-sm leading-relaxed text-muted-foreground"
                      >
                        {instruction}
                      </p>
                    ))}
                  </div>
                </div>
                <div>
                  <h3 className="font-medium text-foreground">{t.trustBadges}</h3>
                  <div className="mt-3 flex flex-wrap gap-2">
                    {(product.certifications ?? []).map((certification) => (
                      <span
                        key={certification}
                        className="rounded-full border border-border/60 px-3 py-2 text-xs uppercase tracking-[0.16em] text-foreground"
                      >
                        {certification}
                      </span>
                    ))}
                  </div>
                </div>
              </div>
            </article>
          </section>
        </div>

        <section className="rounded-[2rem] border border-border/60 bg-card p-8 lg:p-10">
          <div className="grid gap-6 lg:grid-cols-[0.8fr_1.2fr]">
            <div>
              <p className="text-sm uppercase tracking-[0.2em] text-primary">
                {t.conversionEyebrow}
              </p>
              <h2 className="mt-3 font-serif text-3xl text-foreground">
                {t.conversionTitle}
              </h2>
            </div>
            <div className="grid gap-4 md:grid-cols-3">
              <div className="rounded-3xl border border-border/60 bg-background p-5">
                <p className="text-sm font-medium text-foreground">{t.bulkEnquiryTitle}</p>
                <p className="mt-3 text-sm leading-relaxed text-muted-foreground">
                  {t.bulkEnquiryDescription}
                </p>
                <Button asChild variant="ghost" className="mt-5 px-0 text-primary">
                  <Link href={getProductInquiryHref(locale, product)}>
                    {t.bulkEnquiryAction}
                  </Link>
                </Button>
              </div>
              <div className="rounded-3xl border border-border/60 bg-background p-5">
                <p className="text-sm font-medium text-foreground">{t.requestSampleTitle}</p>
                <p className="mt-3 text-sm leading-relaxed text-muted-foreground">
                  {t.requestSampleDescription}
                </p>
                <Button asChild variant="ghost" className="mt-5 px-0 text-primary">
                  <Link href={getProductSampleRequestHref(locale, product)}>
                    {t.requestSampleAction}
                  </Link>
                </Button>
              </div>
              <div className="rounded-3xl border border-border/60 bg-background p-5">
                <p className="text-sm font-medium text-foreground">
                  {t.materialReviewTitle}
                </p>
                <p className="mt-3 text-sm leading-relaxed text-muted-foreground">
                  {t.materialReviewDescription}
                </p>
                <Button asChild variant="ghost" className="mt-5 px-0 text-primary">
                  <Link href={getLocalizedHref(locale, "material")}>
                    {t.materialReviewAction}
                  </Link>
                </Button>
              </div>
            </div>
          </div>
        </section>

        {relatedProducts.length > 0 ? (
          <section>
            <div className="mb-8 flex items-end justify-between gap-6">
              <div>
                <p className="text-sm uppercase tracking-[0.2em] text-primary">
                  {t.relatedEyebrow}
                </p>
                <h2 className="mt-3 font-serif text-3xl text-foreground">
                  {t.relatedTitle}
                </h2>
              </div>
              <Button asChild variant="outline">
                <Link href={getLocalizedHref(locale, "store")}>
                  {t.browseAllProducts}
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
