import { notFound } from "next/navigation"

import { ApiError } from "@/lib/api/client"
import { getProduct } from "@/lib/api/products"
import { getServerApiBaseUrl } from "@/lib/api/server-base-url"
import { getLocalizedHref, getMessages, isValidLocale } from "@/lib/i18n"
import { PageIntro } from "@/components/page-intro"
import { FinalCtaSection } from "@/components/sections/final-cta"
import { StoreFaq } from "@/components/store/store-faq"
import { ProductDetailContent } from "@/components/store/product-detail-content"
import type { Product } from "@/lib/types"

type ProductDetailPageProps = {
  params: Promise<{ locale: string; slug: string }>
}

export default async function ProductDetailPage({
  params,
}: ProductDetailPageProps) {
  const resolvedParams = await params

  if (!isValidLocale(resolvedParams.locale)) {
    notFound()
  }

  const locale = resolvedParams.locale
  const apiBaseUrl = await getServerApiBaseUrl()
  const messages = getMessages(locale)
  const t = messages.productDetail

  let product: Product | null = null
  let hasError = false

  try {
    product = await getProduct(resolvedParams.slug, {
      baseUrl: apiBaseUrl,
      locale,
    })
  } catch (error) {
    if (error instanceof ApiError && error.status === 404) {
      notFound()
    }

    hasError = true
  }

  if (hasError || !product) {
    return (
      <>
        <PageIntro
          eyebrow={t.pageFallbackEyebrow}
          title={t.pageErrorTitle}
          description={t.pageErrorDescription}
          primaryAction={{
            label: t.backToStore,
            href: getLocalizedHref(locale, "store"),
          }}
          secondaryAction={{
            label: messages.header.contact,
            href: getLocalizedHref(locale, "contact"),
          }}
        />
        <FinalCtaSection locale={locale} content={messages.home.finalCta} />
      </>
    )
  }

  const productUrl = getLocalizedHref(locale, `store/${product.slug}`)
  const productJsonLd = {
    "@context": "https://schema.org",
    "@type": "Product",
    name: product.name,
    description:
      product.seo?.description ??
      product.short_description ??
      product.subtitle ??
      undefined,
    sku: product.sku ?? undefined,
    brand: {
      "@type": "Brand",
      name: "Terrafin OXP",
    },
    image: (product.gallery_images ?? [])
      .map((image) => image.media_url)
      .filter(Boolean),
    offers: {
      "@type": "Offer",
      price: product.price_usd,
      priceCurrency: product.currency ?? "NZD",
      availability: product.can_add_to_cart
        ? "https://schema.org/InStock"
        : "https://schema.org/OutOfStock",
      url: productUrl,
      itemCondition: "https://schema.org/NewCondition",
      shippingDetails: {
        "@type": "OfferShippingDetails",
        shippingDestination: {
          "@type": "DefinedRegion",
          addressCountry: "NZ",
        },
      },
    },
  }

  return (
    <>
      <script
        type="application/ld+json"
        dangerouslySetInnerHTML={{
          __html: JSON.stringify(productJsonLd).replace(/</g, "\\u003c"),
        }}
      />
      <ProductDetailContent locale={locale} product={product} />
      <div className="mx-auto max-w-7xl px-6 pb-10 lg:px-8">
        <StoreFaq content={messages.storePage.faq} />
      </div>
      <FinalCtaSection locale={locale} content={messages.home.finalCta} />
    </>
  )
}
