import { PageIntro } from "@/components/page-intro"
import { ArticleFeedSection } from "@/components/sections/article-feed"
import { FinalCtaSection } from "@/components/sections/final-cta"
import { listArticles } from "@/lib/api/articles"
import { findPageSection, getPageSections } from "@/lib/api/page-sections"
import { getServerApiBaseUrl } from "@/lib/api/server-base-url"
import { getLocalizedHref, getMessages } from "@/lib/i18n"
import {
  buildFinalCtaContent,
  buildPageIntroContent,
  resolveCmsHref,
  resolveLocalizedApiString,
} from "@/lib/page-content"
import { resolveLocale } from "@/lib/resolve-locale"
import type { ArticleSummary, HomeSection } from "@/lib/types"

type ArticlesPageProps = {
  params: Promise<{ locale: string }>
}

export default async function ArticlesPage({ params }: ArticlesPageProps) {
  const locale = await resolveLocale(params)
  const apiBaseUrl = await getServerApiBaseUrl()
  const messages = getMessages(locale)

  let articles: ArticleSummary[] = []
  let articleSections: HomeSection[] = []
  let sectionsLoaded = false

  try {
    const response = await listArticles(
      { per_page: 12, locale },
      { baseUrl: apiBaseUrl, locale },
    )
    articles = response.items
  } catch {
    articles = []
  }

  try {
    articleSections = await getPageSections({
      baseUrl: apiBaseUrl,
      locale,
      page: "articles",
    })
    sectionsLoaded = true
  } catch {
    articleSections = []
  }

  const shouldUseCmsVisibility = sectionsLoaded && articleSections.length > 0
  const articleSection = (key: string) => findPageSection(articleSections, key)
  const shouldRender = (key: string) =>
    !shouldUseCmsVisibility || Boolean(articleSection(key))
  const intro = buildPageIntroContent(
    {
      eyebrow: messages.articleFeed.defaultEyebrow,
      title: messages.articleFeed.defaultTitle,
      description: messages.articleFeed.defaultDescription,
      primaryCta: messages.articleFeed.defaultCta,
      secondaryCta: messages.header.contact,
    },
    shouldRender("intro") ? articleSection("intro") : null,
    locale,
    `${getLocalizedHref(locale, "articles")}#articles`,
    getLocalizedHref(locale, "contact"),
  )
  const articleFeedSection = shouldRender("article_feed")
    ? articleSection("article_feed")
    : null
  const articleFeedPayload = articleFeedSection?.payload ?? null
  const finalCtaContent = buildFinalCtaContent(
    messages.home.finalCta,
    shouldRender("final_cta") ? articleSection("final_cta") : null,
    locale,
  )

  return (
    <>
      {shouldRender("intro") ? (
        <PageIntro
          eyebrow={intro.eyebrow}
          title={intro.title}
          description={intro.description}
          primaryAction={{
            label: intro.primaryCta,
            href:
              intro.primaryHref ??
              `${getLocalizedHref(locale, "articles")}#articles`,
          }}
          secondaryAction={{
            label: intro.secondaryCta,
            href: intro.secondaryHref ?? getLocalizedHref(locale, "contact"),
          }}
        />
      ) : null}
      {shouldRender("article_feed") ? (
        <ArticleFeedSection
          locale={locale}
          eyebrow={resolveLocalizedApiString(
            articleFeedSection,
            "subtitle",
            locale,
            messages.articleFeed.defaultEyebrow,
          )}
          title={resolveLocalizedApiString(
            articleFeedSection,
            "title",
            locale,
            messages.articleFeed.defaultTitle,
          )}
          description={resolveLocalizedApiString(
            articleFeedSection,
            "content",
            locale,
            messages.articleFeed.defaultDescription,
          )}
          articles={articles}
          cta={{
            label: resolveLocalizedApiString(
              articleFeedSection,
              "cta_label",
              locale,
              messages.articleFeed.defaultCta,
            ),
            href: resolveCmsHref(
              locale,
              articleFeedSection?.cta_url,
              getLocalizedHref(locale, "articles"),
            ),
          }}
          emptyTitle={resolveLocalizedApiString(
            articleFeedPayload,
            "empty_title",
            locale,
            messages.articleFeed.emptyTitle,
          )}
          emptyDescription={resolveLocalizedApiString(
            articleFeedPayload,
            "empty_description",
            locale,
            messages.articleFeed.emptyDescription,
          )}
        />
      ) : null}
      {shouldRender("final_cta") ? (
        <FinalCtaSection locale={locale} content={finalCtaContent} />
      ) : null}
    </>
  )
}
