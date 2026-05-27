"use client"

import { useEffect, useMemo, useState } from "react"
import Link from "next/link"

import { CertificationsAtAGlance } from "@/components/sections/certifications-at-a-glance"
import { TechnicalDownloadsSection } from "@/components/sections/material-proof-sections"
import { ProductAvailabilityBadge } from "@/components/store/ProductAvailabilityBadge"
import { ProductGallery } from "@/components/store/ProductGallery"
import { ProductCard } from "@/components/store/ProductCard"
import { ProductSpecificationGrid } from "@/components/store/ProductSpecificationGrid"
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
import {
  getCartAdjustmentMessage,
  getLocalizedCartQuantityErrorMessage,
  formatQuantityCountMessage,
} from "@/lib/store/cart-messages"
import {
  getLocalizedStockStatusLabel,
  getProductAvailabilitySummary,
  getProductQuantityLimit,
  supportsProjectEnquiry,
} from "@/lib/store/product-display"
import type { Product, ProductImage, ProductVariant } from "@/lib/types"
import { useCart } from "@/hooks/useCart"
import { toast } from "@/hooks/use-toast"

type ProductDetailContentProps = {
  locale: Locale
  product: Product
}

function variantOptionSummary(variant?: ProductVariant | null) {
  const options = Object.entries(variant?.option_values ?? {})
    .filter(([, value]) => typeof value === "string" && value.trim().length > 0)
    .map(([key, value]) => `${key.replace(/_/g, " ")}: ${String(value).replace(/_/g, " ")}`)

  return options.length > 0
    ? options.join(" / ")
    : variant?.display_title || variant?.title || variant?.sku || ""
}

function productCategoryName(product: Product) {
  return product.category_detail?.name ?? product.category_slug ?? null
}

function displayAttributeValue(attribute: NonNullable<Product["attributes"]>[number]) {
  const value = attribute.display_label ?? attribute.value

  if (value === null || value === undefined) {
    return null
  }

  return String(value)
}

function visibleProductAttributes(product: Product) {
  return (product.attributes ?? [])
    .filter((attribute) => {
      const value = displayAttributeValue(attribute)

      return (
        (attribute.is_filterable || attribute.is_specification) &&
        attribute.key !== "material_family" &&
        value !== null &&
        value.trim().length > 0
      )
    })
}

function productWithSelectedVariant(
  product: Product,
  variant?: ProductVariant | null,
): Product {
  if (!variant) {
    return product
  }

  const variantImageUrl =
    variant.image_url && (!variant.is_default || !product.primary_image_url)
      ? variant.image_url
      : null

  return {
    ...product,
    sku: variant.sku || product.sku,
    currency: variant.currency ?? product.currency ?? "NZD",
    price_amount: variant.price_amount,
    compare_at_price_amount: variant.compare_at_price_amount ?? null,
    stock_quantity: variant.stock_quantity ?? null,
    stock_status: variant.stock_status ?? null,
    stock_status_label:
      variant.availability_label ?? variant.stock_status_label ?? product.stock_status_label,
    in_stock: Boolean(variant.is_in_stock),
    can_add_to_cart: Boolean(variant.can_add_to_cart) && !product.inquiry_only,
    default_variant: variant,
    weight_grams: variant.weight_grams ?? product.weight_grams,
    primary_image_url: variantImageUrl ?? product.primary_image_url,
    image_url: variantImageUrl ?? product.image_url,
  }
}

export function ProductDetailContent({
  locale,
  product,
}: ProductDetailContentProps) {
  const { addItem } = useCart()
  const messages = getMessages(locale)
  const t = messages.productDetail
  const certificationMessages = messages.certificationsAtAGlance
  const relatedProducts = product.related_products ?? []
  const activeVariants = useMemo(
    () => (product.variants ?? []).filter((variant) => variant.is_active !== false),
    [product.variants],
  )
  const initialVariantId =
    product.default_variant?.id ??
    activeVariants.find((variant) => variant.is_default)?.id ??
    activeVariants[0]?.id ??
    null
  const [selectedVariantId, setSelectedVariantId] = useState<number | null>(
    initialVariantId,
  )
  const selectedVariant = useMemo(
    () =>
      activeVariants.find((variant) => variant.id === selectedVariantId) ??
      product.default_variant ??
      activeVariants[0] ??
      null,
    [activeVariants, product.default_variant, selectedVariantId],
  )
  const selectedProduct = useMemo(
    () => productWithSelectedVariant(product, selectedVariant),
    [product, selectedVariant],
  )
  const maxQuantity = getProductQuantityLimit(selectedProduct)
  const [quantity, setQuantity] = useState(1)
  const [addError, setAddError] = useState<string | null>(null)
  const hasVariantSelector =
    activeVariants.length > 1 ||
    activeVariants.some(
      (variant) => Object.keys(variant.option_values ?? {}).length > 0,
    )
  const selectedGalleryImages = useMemo<ProductImage[]>(() => {
    const variantImageUrl =
      selectedVariant?.image_url &&
      (!selectedVariant.is_default || !product.primary_image_url)
        ? selectedVariant.image_url
        : null

    if (!selectedVariant || !variantImageUrl) {
      return product.gallery_images ?? []
    }

    const variantImage: ProductImage = {
      id: -selectedVariant.id,
      product_id: product.id,
      alt_text: `${product.name} ${selectedVariant.display_title ?? ""}`.trim(),
      caption: selectedVariant.display_title ?? null,
      media_url: variantImageUrl,
      sort_order: -1,
    }

    return [
      variantImage,
      ...(product.gallery_images ?? []).filter(
        (image) => image.media_url !== variantImageUrl,
      ),
    ]
  }, [product.gallery_images, product.id, product.name, product.primary_image_url, selectedVariant])

  useEffect(() => {
    setQuantity((currentQuantity) =>
      Math.min(Math.max(currentQuantity, 1), maxQuantity),
    )
    setAddError(null)
  }, [maxQuantity, product.id, selectedVariant?.id])

  async function handleAddToCart() {
    setAddError(null)

    try {
      const result = await addItem(product.id, quantity, selectedVariant?.id ?? null)
      const adjustmentMessage = getCartAdjustmentMessage(
        result?.adjustment,
        messages.cartQuantity,
      )

      if (adjustmentMessage) {
        setAddError(adjustmentMessage)
        toast({ title: adjustmentMessage })
      } else {
        toast({ title: messages.cartQuantity.itemAdded })
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

  const supportCards = useMemo(
    () =>
      [
        supportsProjectEnquiry(product)
          ? {
              title: t.bulkEnquiryTitle,
              description: t.bulkEnquiryDescription,
              label: t.bulkEnquiryAction,
              href: getProductInquiryHref(locale, product),
            }
          : null,
        product.sample_request_enabled
          ? {
              title: t.requestSampleTitle,
              description: t.requestSampleDescription,
              label: t.requestSampleAction,
              href: getProductSampleRequestHref(locale, product),
            }
          : null,
        {
          title: t.materialReviewTitle,
          description: t.materialReviewDescription,
          label: t.materialReviewAction,
          href: getLocalizedHref(locale, "material"),
        },
      ].filter(
        (
          card,
        ): card is {
          title: string
          description: string
          label: string
          href: string
        } => Boolean(card),
      ),
    [locale, product, t],
  )
  const sellingPoints =
    product.selling_points?.length
      ? product.selling_points
      : [
          t.recoveredShellBadge,
          t.premiumSurfaceBadge,
          t.nzDeliveryBadge,
        ]
  const categoryName = productCategoryName(product)
  const usefulAttributes = visibleProductAttributes(product)
  const applicationAttributes = usefulAttributes
    .filter((attribute) =>
      ["use_case", "application"].includes(attribute.key ?? ""),
    )
    .map(displayAttributeValue)
    .filter((value): value is string => Boolean(value))
  const applicationLabels = applicationAttributes.length
    ? applicationAttributes
    : [categoryName || t.defaultUseCase]
  const atAGlance = [
    product.weight_grams
      ? { label: t.glanceWeight, value: `${product.weight_grams} g` }
      : null,
    ...usefulAttributes
      .filter((attribute) => !["use_case", "application"].includes(attribute.key ?? ""))
      .slice(0, 5)
      .map((attribute) => ({
        label: attribute.label ?? "",
        value: displayAttributeValue(attribute) ?? "",
      })),
    applicationLabels.length
      ? { label: t.glanceUseCase, value: applicationLabels.join(", ") }
      : null,
    product.care_instructions?.[0]
      ? { label: t.glanceCare, value: product.care_instructions[0] }
      : null,
    product.lead_time
      ? { label: t.glanceLeadTime, value: product.lead_time }
      : null,
    selectedProduct.stock_status || selectedProduct.stock_status_label
      ? {
          label: t.glanceStock,
          value: getLocalizedStockStatusLabel(
            selectedProduct,
            messages.store.stockStatus,
            selectedProduct.stock_status_label ?? t.availabilityLabel,
          ),
        }
      : null,
  ].filter((item): item is { label: string; value: string } => Boolean(item))
  const reassuranceItems = [
    t.reassuranceNzDelivery,
    t.reassuranceShippingCalculated,
    t.reassuranceEmailConfirmation,
    supportsProjectEnquiry(product) ? t.reassuranceTradeQuote : null,
  ].filter((item): item is string => Boolean(item))
  const productFaqs = (product.product_faqs ?? [])
    .filter((faq) => faq.question && faq.answer)
    .map((faq) => ({
      question: faq.question ?? "",
      answer: faq.answer ?? "",
    }))
  const faqs = [
    ...productFaqs,
    {
      question: t.faqOysterShellQuestion,
      answer: t.faqOysterShellAnswer,
    },
    {
      question: t.faqShippingQuestion,
      answer: t.faqShippingAnswer,
    },
    {
      question: t.faqSampleQuestion,
      answer: product.sample_request_enabled
        ? t.faqSampleAvailableAnswer
        : t.faqSampleUnavailableAnswer,
    },
    {
      question: t.faqB2bQuestion,
      answer: supportsProjectEnquiry(product)
        ? t.faqB2bAvailableAnswer
        : t.faqB2bUnavailableAnswer,
    },
  ]

  return (
    <section className="bg-background py-20 lg:py-24">
      <div className="mx-auto max-w-7xl space-y-12 px-6 lg:px-8">
        <div className="grid grid-cols-1 gap-10 lg:grid-cols-[1.02fr_0.98fr]">
          <ProductGallery
            title={product.name}
            images={selectedGalleryImages}
          />

          <div className="flex flex-col rounded-[2rem] border border-border/60 bg-card p-8 lg:p-10">
            <div className="flex flex-wrap items-center gap-3">
              {categoryName ? (
                <span className="rounded-full bg-primary/10 px-3 py-1 text-xs uppercase tracking-[0.18em] text-primary">
                  {categoryName}
                </span>
              ) : null}
              {usefulAttributes.slice(0, 3).map((attribute) => (
                <span
                  key={`${attribute.key}-${displayAttributeValue(attribute)}`}
                  className="rounded-full border border-border/70 px-3 py-1 text-xs uppercase tracking-[0.18em] text-muted-foreground"
                >
                  {displayAttributeValue(attribute)}
                </span>
              ))}
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

            <p className="mt-5 text-sm font-medium text-foreground">
              {t.oxpMaterialLine}
            </p>

            <div className="mt-5 flex flex-wrap gap-2">
              {[t.recoveredShellBadge, t.nzDeliveryBadge, t.hospitalityReadyBadge]
                .concat(product.sample_request_enabled ? [t.sampleAvailableBadge] : [])
                .map((badge) => (
                  <span
                    key={badge}
                    className="rounded-full border border-border/70 bg-background px-3 py-1 text-xs uppercase tracking-[0.16em] text-muted-foreground"
                  >
                    {badge}
                  </span>
                ))}
            </div>

            <div className="mt-8 rounded-[1.75rem] border border-border/60 bg-background p-6">
              <div className="flex flex-wrap items-start justify-between gap-6">
                <div>
                  <div className="flex flex-wrap items-end gap-3">
                    <p className="text-3xl font-medium text-foreground">
                      {formatProductPrice(selectedProduct, locale)}
                    </p>
                    {selectedProduct.compare_at_price_amount ? (
                      <p className="pb-1 text-base text-muted-foreground line-through">
                        {formatCurrencyAmount(
                          selectedProduct.compare_at_price_amount,
                          locale,
                          selectedProduct.currency ?? "NZD",
                        )}
                      </p>
                    ) : null}
                  </div>
                  <div className="mt-3 flex flex-wrap items-center gap-3">
                    <ProductAvailabilityBadge
                      product={selectedProduct}
                      label={getLocalizedStockStatusLabel(
                        selectedProduct,
                        messages.store.stockStatus,
                        t.availabilityLabel,
                      )}
                    />
                    <span className="text-sm text-muted-foreground">
                      {getProductAvailabilitySummary(selectedProduct, t.defaultAvailability)}
                    </span>
                  </div>
                  {selectedProduct.sku ? (
                    <p className="mt-2 text-xs uppercase tracking-[0.18em] text-muted-foreground">
                      {t.productCodeLabel} {selectedProduct.sku}
                    </p>
                  ) : null}
                </div>

                {selectedProduct.stock_quantity !== null &&
                selectedProduct.stock_status !== "sold_out" ? (
                  <div className="rounded-2xl border border-border/60 px-4 py-3 text-right">
                    <p className="text-xs uppercase tracking-[0.18em] text-muted-foreground">
                      {t.batchStock}
                    </p>
                    <p className="mt-2 text-xl font-medium text-foreground">
                      {selectedProduct.stock_quantity}
                    </p>
                  </div>
                ) : null}
              </div>

              {hasVariantSelector ? (
                <div className="mt-6 border-t border-border/60 pt-5">
                  <p className="text-xs uppercase tracking-[0.18em] text-muted-foreground">
                    {t.optionsLabel}
                  </p>
                  <div className="mt-3 flex flex-wrap gap-2">
                    {activeVariants.map((variant) => {
                      const isSelected = selectedVariant?.id === variant.id
                      const label = variantOptionSummary(variant)

                      return (
                        <button
                          key={variant.id}
                          type="button"
                          className={`rounded-full border px-4 py-2 text-sm transition-colors ${
                            isSelected
                              ? "border-foreground bg-foreground text-background"
                              : "border-border/70 bg-background text-foreground hover:border-foreground/50"
                          }`}
                          onClick={() => {
                            setSelectedVariantId(variant.id)
                          }}
                        >
                          {label}
                        </button>
                      )
                    })}
                  </div>
                </div>
              ) : null}

              <div className="mt-6 flex flex-wrap gap-3">
                {selectedProduct.can_add_to_cart ? (
                  <div className="flex items-center rounded-full border border-border/70">
                    <button
                      type="button"
                      className="px-4 py-2 text-foreground transition-colors hover:bg-muted disabled:pointer-events-none disabled:opacity-45"
                      onClick={() =>
                        setQuantity((currentValue) => Math.max(1, currentValue - 1))
                      }
                      disabled={quantity <= 1}
                      aria-disabled={quantity <= 1}
                    >
                      -
                    </button>
                    <span className="min-w-10 text-center text-sm font-medium">
                      {quantity}
                    </span>
                    <button
                      type="button"
                      className="px-4 py-2 text-foreground transition-colors hover:bg-muted disabled:pointer-events-none disabled:opacity-45"
                      onClick={() =>
                        setQuantity((currentValue) =>
                          Math.min(maxQuantity, currentValue + 1),
                        )
                      }
                      disabled={quantity >= maxQuantity}
                      aria-disabled={quantity >= maxQuantity}
                      title={
                        quantity >= maxQuantity
                          ? formatQuantityCountMessage(
                              messages.cartQuantity.onlyAvailable,
                              maxQuantity,
                            )
                          : undefined
                      }
                    >
                      +
                    </button>
                  </div>
                ) : null}

                {selectedProduct.can_add_to_cart ? (
                  <Button
                    type="button"
                    onClick={() => {
                      void handleAddToCart()
                    }}
                  >
                    {t.addToCart}
                  </Button>
                ) : (
                  <Button asChild>
                    <Link href={getProductInquiryHref(locale, product)}>
                      {supportsProjectEnquiry(product) ? t.bulkEnquiry : t.requestUpdate}
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

              {addError ? (
                <div className="mt-4 rounded-2xl bg-destructive/10 px-4 py-3 text-sm text-destructive" role="alert">
                  {addError}
                </div>
              ) : null}

              {!selectedProduct.can_add_to_cart ? (
                <div className="mt-5 rounded-2xl border border-dashed border-border/70 bg-card p-4 text-sm leading-relaxed text-muted-foreground">
                  {selectedProduct.stock_status === "sold_out"
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

        {atAGlance.length > 0 ? (
          <section className="rounded-[2rem] border border-border/60 bg-card p-8 lg:p-10">
            <div className="flex flex-wrap items-end justify-between gap-6">
              <div>
                <p className="text-sm uppercase tracking-[0.2em] text-primary">
                  {t.glanceEyebrow}
                </p>
                <h2 className="mt-3 font-serif text-3xl text-foreground">
                  {t.glanceTitle}
                </h2>
              </div>
              <p className="max-w-xl text-sm leading-relaxed text-muted-foreground">
                {t.glanceDescription}
              </p>
            </div>

            <div className="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
              {atAGlance.map((item) => (
                <article
                  key={item.label}
                  className="rounded-3xl border border-border/60 bg-background p-5"
                >
                  <p className="text-xs uppercase tracking-[0.18em] text-muted-foreground">
                    {item.label}
                  </p>
                  <p className="mt-3 text-base font-medium text-foreground">
                    {item.value}
                  </p>
                </article>
              ))}
            </div>
          </section>
        ) : null}

        <section className="rounded-[2rem] border border-border/60 bg-card p-8 lg:p-10">
          <div className="grid gap-8 lg:grid-cols-[0.85fr_1.15fr]">
            <div>
              <p className="text-sm uppercase tracking-[0.2em] text-primary">
                {t.whyOxpEyebrow}
              </p>
              <h2 className="mt-3 font-serif text-3xl text-foreground">
                {t.whyOxpTitle}
              </h2>
              <p className="mt-4 text-sm leading-relaxed text-muted-foreground">
                {t.whyOxpDescription}
              </p>
            </div>
            <div className="grid gap-4 sm:grid-cols-3">
              {sellingPoints.slice(0, 3).map((point) => (
                <article
                  key={point}
                  className="rounded-3xl border border-border/60 bg-background p-5"
                >
                  <p className="text-sm leading-relaxed text-foreground">{point}</p>
                </article>
              ))}
            </div>
          </div>
        </section>

        <section className="rounded-[2rem] border border-border/60 bg-card p-8 lg:p-10">
          <div className="grid gap-6 lg:grid-cols-[0.8fr_1.2fr]">
            <div>
              <p className="text-sm uppercase tracking-[0.2em] text-primary">
                {t.bestForEyebrow}
              </p>
              <h2 className="mt-3 font-serif text-3xl text-foreground">
                {t.bestForTitle}
              </h2>
            </div>
            <div className="flex flex-wrap gap-3">
              {applicationLabels.map((useCase) => (
                <span
                  key={useCase}
                  className="rounded-full border border-border/70 bg-background px-4 py-2 text-sm text-foreground"
                >
                  {useCase}
                </span>
              ))}
            </div>
          </div>
        </section>

        <div className="grid gap-6 lg:grid-cols-[1.05fr_0.95fr]">
          {product.specifications?.length ? (
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

              <div className="mt-8">
                <ProductSpecificationGrid
                  specifications={product.specifications ?? []}
                />
              </div>
            </section>
          ) : null}

          <section className="grid gap-6">
            <CertificationsAtAGlance
              certifications={product.certifications ?? []}
              eyebrow={certificationMessages.eyebrow}
              title={certificationMessages.title}
              description={certificationMessages.productDescription}
              variant="product"
              verifiedLabel={certificationMessages.verifiedLabel}
              emptyMessage={t.certificationsOnRequest}
              statusLabels={certificationMessages.statusLabels}
              issuerLabel={certificationMessages.issuerLabel}
              testedAtLabel={certificationMessages.testedAtLabel}
              downloadLabel={certificationMessages.downloadLabel}
            />

            {product.material_benefits?.length ? (
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
            ) : null}

            {product.care_instructions?.length ? (
              <article className="rounded-[2rem] border border-border/60 bg-card p-8">
                <p className="text-sm uppercase tracking-[0.2em] text-primary">
                  {t.care}
                </p>
                <div className="mt-6 space-y-3">
                  {(product.care_instructions ?? []).map((instruction) => (
                    <p
                      key={instruction}
                      className="text-sm leading-relaxed text-muted-foreground"
                    >
                      {instruction}
                    </p>
                  ))}
                </div>
              </article>
            ) : null}
          </section>
        </div>

        <TechnicalDownloadsSection
          content={messages.materialProof.technicalDownloads}
          downloads={product.technical_downloads ?? []}
        />

        {supportCards.length > 0 ? (
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
              <div
                className={`grid gap-4 ${
                  supportCards.length > 1 ? "md:grid-cols-2 xl:grid-cols-3" : ""
                }`}
              >
                {supportCards.map((card) => (
                  <div
                    key={card.title}
                    className="rounded-3xl border border-border/60 bg-background p-5"
                  >
                    <p className="text-sm font-medium text-foreground">{card.title}</p>
                    <p className="mt-3 text-sm leading-relaxed text-muted-foreground">
                      {card.description}
                    </p>
                    <Button asChild variant="ghost" className="mt-5 px-0 text-primary">
                      <Link href={card.href}>{card.label}</Link>
                    </Button>
                  </div>
                ))}
              </div>
            </div>
          </section>
        ) : null}

        <section className="grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
          <article className="rounded-[2rem] border border-border/60 bg-card p-8 lg:p-10">
            <p className="text-sm uppercase tracking-[0.2em] text-primary">
              {t.reassuranceEyebrow}
            </p>
            <h2 className="mt-3 font-serif text-3xl text-foreground">
              {t.reassuranceTitle}
            </h2>
            <div className="mt-6 space-y-4">
              {reassuranceItems.map((item) => (
                <div key={item} className="flex gap-3">
                  <span className="mt-1 size-2 shrink-0 rounded-full bg-primary" />
                  <p className="text-sm leading-relaxed text-muted-foreground">
                    {item}
                  </p>
                </div>
              ))}
            </div>
          </article>

          <article className="rounded-[2rem] border border-border/60 bg-card p-8 lg:p-10">
            <p className="text-sm uppercase tracking-[0.2em] text-primary">
              {t.faqEyebrow}
            </p>
            <h2 className="mt-3 font-serif text-3xl text-foreground">
              {t.faqTitle}
            </h2>
            <div className="mt-6 divide-y divide-border/60">
              {faqs.map((faq) => (
                <details key={faq.question} className="group py-4 first:pt-0 last:pb-0">
                  <summary className="cursor-pointer list-none text-sm font-medium text-foreground">
                    {faq.question}
                  </summary>
                  <p className="mt-3 text-sm leading-relaxed text-muted-foreground">
                    {faq.answer}
                  </p>
                </details>
              ))}
            </div>
          </article>
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
