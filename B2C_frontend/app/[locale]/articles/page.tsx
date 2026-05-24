import { PageIntro } from "@/components/page-intro"
import { ArticleFeedSection } from "@/components/sections/article-feed"
import { FinalCtaSection } from "@/components/sections/final-cta"
import { listArticles } from "@/lib/api/articles"
import { findPageSection, getPageSections } from "@/lib/api/page-sections"
import { getServerApiBaseUrl } from "@/lib/api/server-base-url"
import { hasPublishedCmsSection } from "@/lib/cms-section-visibility"
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
  } catch {
    articleSections = []
  }

  const articleSection = (key: string) => findPageSection(articleSections, key)
  const introSection = articleSection("intro")
  const articleFeedSection = articleSection("article_feed")
  const finalCtaSection = articleSection("final_cta")
  const intro = hasPublishedCmsSection(introSection)
    ? buildPageIntroContent(
        {
          eyebrow: messages.articleFeed.defaultEyebrow,
          title: messages.articleFeed.defaultTitle,
          description: messages.articleFeed.defaultDescription,
          primaryCta: messages.articleFeed.defaultCta,
          secondaryCta: messages.header.contact,
        },
        introSection,
        locale,
        `${getLocalizedHref(locale, "articles")}#articles`,
        getLocalizedHref(locale, "contact"),
      )
    : null
  const articleFeedPayload = articleFeedSection?.payload ?? null
  const finalCtaContent = hasPublishedCmsSection(finalCtaSection)
    ? buildFinalCtaContent(messages.home.finalCta, finalCtaSection, locale)
    : null

  return (
    <>
      {intro ? (
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
      {hasPublishedCmsSection(articleFeedSection) ? (
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
      {finalCtaContent ? (
        <FinalCtaSection locale={locale} content={finalCtaContent} />
      ) : null}
    </>
  )
}
