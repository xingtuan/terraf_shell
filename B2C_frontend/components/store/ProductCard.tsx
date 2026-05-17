"use client"

import { useState } from "react"
import Image from "next/image"
import Link from "next/link"

import { ProductAvailabilityBadge } from "@/components/store/ProductAvailabilityBadge"
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
import {
  getCartAdjustmentMessage,
  getLocalizedCartQuantityErrorMessage,
} from "@/lib/store/cart-messages"
import { getProductAvailabilitySummary, supportsProjectEnquiry } from "@/lib/store/product-display"
import type { Product } from "@/lib/types"
import { useCart } from "@/hooks/useCart"
import { toast } from "@/hooks/use-toast"

type ProductCardProps = {
  locale: Locale
  product: Product
}

function productCategoryName(product: Product) {
  return product.category_detail?.name ?? product.category_slug ?? null
}

function productCardAttributes(product: Product) {
  return (product.attributes ?? [])
    .filter((attribute) => {
      const value = attribute.display_label ?? attribute.value

      return (
        (attribute.is_filterable || attribute.is_specification) &&
        attribute.key !== "material_family" &&
        value !== null &&
        value !== undefined &&
        String(value).trim().length > 0
      )
    })
    .slice(0, 3)
}

export function ProductCard({ locale, product }: ProductCardProps) {
  const { addItem } = useCart()
  const messages = getMessages(locale)
  const t = messages.productCard
  const productHref = getProductDetailHref(locale, product.slug)
  const [addError, setAddError] = useState<string | null>(null)
  const badges = [
    product.featured ? t.featuredBadge : null,
    product.is_bestseller ? t.bestSellerBadge : null,
    product.is_new ? t.newBadge : null,
  ].filter((badge): badge is string => Boolean(badge))
  const cardAttributes = productCardAttributes(product)
  const categoryName = productCategoryName(product) ?? product.subtitle ?? ""

  async function handleAddToCart() {
    setAddError(null)

    try {
      const result = await addItem(product.id, 1)
      const adjustmentMessage = getCartAdjustmentMessage(
        result?.adjustment,
        messages.cartQuantity,
      )

      if (adjustmentMessage) {
        setAddError(adjustmentMessage)
        toast({
          title: adjustmentMessage,
        })
      }
    } catch (nextError) {
      const message = getLocalizedCartQuantityErrorMessage(
        nextError,
        messages.common.errors,
        messages.cartQuantity,
      )

      setAddError(message)
      toast({
        title: message,
        variant: "destructive",
      })
    }
  }

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
                {categoryName}
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
            <ProductAvailabilityBadge product={product} fallbackLabel={t.inStock} />
          </div>
        </div>
      </Link>

      <div className="space-y-5 p-6">
        <div className="space-y-3">
          <div className="flex flex-wrap gap-2 text-xs uppercase tracking-[0.18em] text-muted-foreground">
            {cardAttributes.map((attribute) => (
              <span className="rounded-full border border-border/60 px-3 py-1">
                {attribute.display_label ?? String(attribute.value ?? "")}
              </span>
            ))}
            {cardAttributes.length === 0 && categoryName ? (
              <span className="rounded-full border border-border/60 px-3 py-1">
                {categoryName}
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

        {cardAttributes.length > 0 ? (
          <div className="flex flex-wrap gap-2">
            {cardAttributes.map((attribute) => (
              <span
                key={`${attribute.key}-${attribute.display_label}`}
                className="rounded-full bg-muted px-3 py-1 text-xs text-muted-foreground"
              >
                {attribute.label}: {attribute.display_label ?? String(attribute.value ?? "")}
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
                {product.compare_at_price_amount ? (
                  <p className="text-sm text-muted-foreground line-through">
                    {formatCurrencyAmount(
                      product.compare_at_price_amount,
                      locale,
                      product.currency ?? "NZD",
                    )}
                  </p>
                ) : null}
              </div>
              <p className="mt-2 text-sm text-muted-foreground">
                {getProductAvailabilitySummary(product, t.defaultAvailability)}
              </p>
            </div>
            {product.stock_quantity !== null && product.stock_status === "low_stock" ? (
              <p className="text-sm text-amber-700">
                {t.stockLeft.replace("{count}", String(product.stock_quantity))}
              </p>
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
                void handleAddToCart()
              }}
            >
              {t.addToCart}
            </Button>
          ) : (
            <Button asChild className="flex-1">
              <Link href={getProductInquiryHref(locale, product)}>
                {supportsProjectEnquiry(product) ? t.bulkEnquiry : t.requestUpdate}
              </Link>
            </Button>
          )}
        </div>

        {addError ? (
          <p
            className="rounded-2xl bg-destructive/10 px-4 py-3 text-sm text-destructive"
            role="alert"
          >
            {addError}
          </p>
        ) : null}

        {product.sample_request_enabled && !product.can_add_to_cart ? (
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
