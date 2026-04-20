import { Suspense } from "react"

import { B2BInquiryFormSection } from "@/components/sections/b2b-inquiry-form"
import { ContentBlockSection } from "@/components/sections/content-block"
import { ContentHeroSection } from "@/components/sections/content-hero"
import { ContentPointsSection } from "@/components/sections/content-points"
import { getPageContent } from "@/lib/api/content"
import { getServerApiBaseUrl } from "@/lib/api/server-base-url"
import { getLocalizedHref, getMessages } from "@/lib/i18n"
import { resolveCmsHref } from "@/lib/page-content"
import { resolveLocale } from "@/lib/resolve-locale"

type B2BPageProps = {
  params: Promise<{ locale: string }>
}

export default async function B2BPage({ params }: B2BPageProps) {
  const locale = await resolveLocale(params)
  const apiBaseUrl = await getServerApiBaseUrl()
  const messages = getMessages(locale)
  const content = await getPageContent("b2b", locale, { baseUrl: apiBaseUrl })
  const points = Array.isArray(content.advantages?.metadata?.points)
    ? content.advantages.metadata.points.filter(
        (point): point is string => typeof point === "string" && point.trim().length > 0,
      )
    : []

  return (
    <>
      <ContentHeroSection
        title={content.hero?.title}
        subtitle={content.hero?.subtitle}
      />
      <ContentBlockSection
        title={content.intro?.title}
        body={content.intro?.body}
      />
      <ContentPointsSection
        title={content.advantages?.title}
        body={content.advantages?.body}
        points={points}
      />
      <Suspense fallback={null}>
        <B2BInquiryFormSection
          locale={locale}
          content={messages.b2bPage.form}
          sourcePage="b2b"
          defaultLeadType="inquiry"
        />
      </Suspense>
      <ContentBlockSection
        title={content.cta?.title}
        subtitle={content.cta?.subtitle}
        ctaLabel={content.cta?.cta_label}
        ctaHref={resolveCmsHref(
          locale,
          content.cta?.cta_url,
          `${getLocalizedHref(locale, "b2b")}#inquiry`,
        )}
        align="center"
      />
    </>
  )
}
