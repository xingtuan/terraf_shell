import { notFound } from "next/navigation"

import { ApiError } from "@/lib/api/client"
import { getProduct } from "@/lib/api/products"
import { getServerApiBaseUrl } from "@/lib/api/server-base-url"
import { getLocalizedHref, getMessages, isValidLocale } from "@/lib/i18n"
import { PageIntro } from "@/components/page-intro"
import { FinalCtaSection } from "@/components/sections/final-cta"
import { ProductDetailContent } from "@/components/store/product-detail-content"

type ProductDetailPageProps = {
  params: Promise<{ locale: string; slug: string }>
}

const categoryLabels: Record<string, string> = {
  tableware: "Tableware",
  planters: "Planters",
  wellness_interior: "Wellness & Interior",
  architectural: "Architectural",
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

  let product = null
  let hasError = false

  try {
    const response = await getProduct(resolvedParams.slug, {
      baseUrl: apiBaseUrl,
    })
    product = response.data
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
          eyebrow="Product"
          title="Product details are temporarily unavailable."
          description="The product page could not be loaded from the API. You can still browse the store or contact the team directly."
          primaryAction={{
            label: "Back to store",
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

  return (
    <>
      <PageIntro
        eyebrow={categoryLabels[product.category] || "Product"}
        title={product.name}
        description={
          "Review the Shellfin product specification and connect to the relevant lead flow."
        }
        primaryAction={{
          label: "Back to store",
          href: getLocalizedHref(locale, "store"),
        }}
        secondaryAction={{
          label: messages.header.contact,
          href: getLocalizedHref(locale, "contact"),
        }}
      />
      <ProductDetailContent
        locale={locale}
        header={messages.header}
        product={product}
      />
      <FinalCtaSection locale={locale} content={messages.home.finalCta} />
    </>
  )
}
