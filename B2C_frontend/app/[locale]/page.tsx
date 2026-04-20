import { ContentBlockSection } from "@/components/sections/content-block"
import { ContentHeroSection } from "@/components/sections/content-hero"
import { getPageContent } from "@/lib/api/content"
import { getServerApiBaseUrl } from "@/lib/api/server-base-url"
import { getLocalizedHref } from "@/lib/i18n"
import { resolveCmsHref } from "@/lib/page-content"
import { resolveLocale } from "@/lib/resolve-locale"

type HomePageProps = {
  params: Promise<{ locale: string }>
}

export default async function LocaleHomePage({ params }: HomePageProps) {
  const locale = await resolveLocale(params)
  const apiBaseUrl = await getServerApiBaseUrl()
  const content = await getPageContent("home", locale, { baseUrl: apiBaseUrl })

  return (
    <>
      <ContentHeroSection
        title={content.hero?.title}
        subtitle={content.hero?.subtitle}
        ctaLabel={content.hero?.cta_label}
        ctaHref={resolveCmsHref(
          locale,
          content.hero?.cta_url,
          getLocalizedHref(locale, "store"),
        )}
      />
      <ContentBlockSection
        title={content.intro?.title}
        body={content.intro?.body}
      />
      <ContentBlockSection
        title={content.sustainability?.title}
        body={content.sustainability?.body}
      />
    </>
  )
}
