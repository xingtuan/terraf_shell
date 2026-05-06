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
  getProductAvailabilitySummary,
  getProductQuantityLimit,
  supportsProjectEnquiry,
} from "@/lib/store/product-display"
import type { Product } from "@/lib/types"
import { useCart } from "@/hooks/useCart"

type ProductDetailContentProps = {
  locale: Locale
  product: Product
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
  const maxQuantity = getProductQuantityLimit(product)
  const [quantity, setQuantity] = useState(1)

  useEffect(() => {
    setQuantity((currentQuantity) =>
      Math.min(Math.max(currentQuantity, 1), maxQuantity),
    )
  }, [maxQuantity, product.id])

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
  const useCaseLabels = product.use_case_labels?.length
    ? product.use_case_labels
    : [product.category_label || t.defaultUseCase]
  const atAGlance = [
    product.dimensions
      ? { label: t.glanceDimensions, value: product.dimensions }
      : null,
    product.weight_grams
      ? { label: t.glanceWeight, value: `${product.weight_grams} g` }
      : null,
    product.finish_label ? { label: t.glanceFinish, value: product.finish_label } : null,
    product.color_label ? { label: t.glanceColor, value: product.color_label } : null,
    useCaseLabels.length
      ? { label: t.glanceUseCase, value: useCaseLabels.join(", ") }
      : null,
    product.care_instructions?.[0]
      ? { label: t.glanceCare, value: product.care_instructions[0] }
      : null,
    product.lead_time
      ? { label: t.glanceLeadTime, value: product.lead_time }
      : null,
    product.stock_status_label
      ? { label: t.glanceStock, value: product.stock_status_label }
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
              {product.finish_label ? (
                <span className="rounded-full border border-border/70 px-3 py-1 text-xs uppercase tracking-[0.18em] text-muted-foreground">
                  {product.finish_label}
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
                    <ProductAvailabilityBadge
                      product={product}
                      fallbackLabel={t.availabilityLabel}
                    />
                    <span className="text-sm text-muted-foreground">
                      {getProductAvailabilitySummary(product, t.defaultAvailability)}
                    </span>
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
                {product.can_add_to_cart ? (
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
                        setQuantity((currentValue) =>
                          Math.min(maxQuantity, currentValue + 1),
                        )
                      }
                    >
                      +
                    </button>
                  </div>
                ) : null}

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
              {useCaseLabels.map((useCase) => (
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
