"use client"

import Image from "next/image"
import Link from "next/link"

import { Button } from "@/components/ui/button"
import {
  formatCurrencyAmount,
  formatProductPrice,
} from "@/lib/api/products"
import { getMessages, type Locale } from "@/lib/i18n"
import {
  getProductDetailHref,
  getProductInquiryHref,
  getProductSampleRequestHref,
} from "@/lib/product-links"
import type { Product } from "@/lib/types"
import { useCart } from "@/hooks/useCart"

type ProductCardProps = {
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

export function ProductCard({ locale, product }: ProductCardProps) {
  const { addItem } = useCart()
  const t = getMessages(locale).productCard
  const productHref = getProductDetailHref(locale, product.slug)
  const badges = [
    product.featured ? t.featuredBadge : null,
    product.is_bestseller ? t.bestSellerBadge : null,
    product.is_new ? t.newBadge : null,
  ].filter((badge): badge is string => Boolean(badge))

  return (
    <article className="group overflow-hidden rounded-[2rem] border border-border/60 bg-card transition-transform duration-300 hover:-translate-y-1">
      <Link href={productHref} className="block">
        <div className="relative min-h-[320px] overflow-hidden bg-muted">
          <Image
            src={product.primary_image_url || product.image_url || "/placeholder.jpg"}
            alt={product.name}
            fill
            className="object-cover transition-transform duration-500 group-hover:scale-[1.03]"
          />
          <div className="absolute inset-x-0 top-0 flex items-start justify-between gap-3 p-4">
            <div className="flex flex-wrap gap-2">
              <span className="rounded-full bg-background/90 px-3 py-1 text-[11px] uppercase tracking-[0.18em] text-foreground">
                {product.category_label || product.category}
              </span>
              {badges.map((badge) => (
                <span
                  key={badge}
                  className="rounded-full bg-primary/90 px-3 py-1 text-[11px] uppercase tracking-[0.18em] text-primary-foreground"
                >
                  {badge}
                </span>
              ))}
            </div>
            <span
              className={`rounded-full px-3 py-1 text-[11px] uppercase tracking-[0.18em] ${stockTone(
                product,
              )}`}
            >
              {product.stock_status_label || (product.in_stock ? t.inStock : t.soldOut)}
            </span>
          </div>
        </div>
      </Link>

      <div className="space-y-5 p-6">
        <div className="space-y-3">
          <div className="flex flex-wrap gap-2 text-xs uppercase tracking-[0.18em] text-muted-foreground">
            {product.model_label ? (
              <span className="rounded-full border border-border/60 px-3 py-1">
                {product.model_label}
              </span>
            ) : null}
            {product.finish_label ? (
              <span className="rounded-full border border-border/60 px-3 py-1">
                {product.finish_label}
              </span>
            ) : null}
            {product.color_label ? (
              <span className="rounded-full border border-border/60 px-3 py-1">
                {product.color_label}
              </span>
            ) : null}
          </div>

          <div>
            <Link href={productHref} className="transition-colors hover:text-primary">
              <h3 className="font-serif text-2xl text-foreground">{product.name}</h3>
            </Link>
            {product.subtitle ? (
              <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
                {product.subtitle}
              </p>
            ) : null}
          </div>
        </div>

        {product.use_case_labels?.length ? (
          <div className="flex flex-wrap gap-2">
            {product.use_case_labels.slice(0, 3).map((useCase) => (
              <span
                key={useCase}
                className="rounded-full bg-muted px-3 py-1 text-xs text-muted-foreground"
              >
                {useCase}
              </span>
            ))}
          </div>
        ) : null}

        <div className="rounded-3xl bg-background p-4">
          <div className="flex items-end justify-between gap-4">
            <div>
              <div className="flex items-center gap-3">
                <p className="text-xl font-medium text-foreground">
                  {formatProductPrice(product, locale)}
                </p>
                {product.compare_at_price_usd ? (
                  <p className="text-sm text-muted-foreground line-through">
                    {formatCurrencyAmount(
                      product.compare_at_price_usd,
                      locale,
                      product.currency ?? "USD",
                    )}
                  </p>
                ) : null}
              </div>
              <p className="mt-2 text-sm text-muted-foreground">
                {product.lead_time || product.availability_text || "Small-batch availability"}
              </p>
            </div>
            {product.stock_quantity !== null && product.stock_status === "low_stock" ? (
              <p className="text-sm text-amber-700">{t.stockLeft.replace("{count}", String(product.stock_quantity))}</p>
            ) : null}
          </div>
        </div>

        <div className="flex flex-wrap gap-3 border-t border-border/60 pt-1">
          <Button asChild variant="outline" className="flex-1">
            <Link href={productHref}>{t.viewDetails}</Link>
          </Button>
          {product.can_add_to_cart ? (
            <Button
              type="button"
              className="flex-1"
              onClick={() => {
                void addItem(product.id, 1)
              }}
            >
              {t.addToCart}
            </Button>
          ) : (
            <Button asChild className="flex-1">
              <Link href={getProductInquiryHref(locale, product)}>
                {product.inquiry_only ? t.bulkEnquiry : t.requestUpdate}
              </Link>
            </Button>
          )}
        </div>

        {!product.can_add_to_cart && product.sample_request_enabled ? (
          <Button asChild variant="ghost" className="w-full justify-start px-0 text-primary">
            <Link href={getProductSampleRequestHref(locale, product)}>
              {t.requestSample}
            </Link>
          </Button>
        ) : null}
      </div>
    </article>
  )
}
