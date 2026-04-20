import { notFound } from "next/navigation"

import { ArticleDetailContent } from "@/components/articles/article-detail"
import { PageIntro } from "@/components/page-intro"
import { FinalCtaSection } from "@/components/sections/final-cta"
import { getArticle } from "@/lib/api/articles"
import { getServerApiBaseUrl } from "@/lib/api/server-base-url"
import { getLocalizedHref, getMessages, isValidLocale } from "@/lib/i18n"

type ArticleDetailPageProps = {
  params: Promise<{ locale: string; slug: string }>
}

export default async function ArticleDetailPage({
  params,
}: ArticleDetailPageProps) {
  const resolvedParams = await params

  if (!isValidLocale(resolvedParams.locale)) {
    notFound()
  }

  const locale = resolvedParams.locale
  const apiBaseUrl = await getServerApiBaseUrl()
  const messages = getMessages(locale)

  let article = null

  try {
    article = await getArticle(resolvedParams.slug, {
      baseUrl: apiBaseUrl,
      locale,
    })
  } catch {
    article = null
  }

  if (!article) {
    notFound()
  }

  return (
    <>
      <PageIntro
        eyebrow={article.category || "Article"}
        title={article.title}
        description={
          article.excerpt ||
          "Read the full backend-driven article and follow Shellfin material updates."
        }
        primaryAction={{
          label: "Back to articles",
          href: getLocalizedHref(locale, "articles"),
        }}
        secondaryAction={{
          label: messages.header.contact,
          href: getLocalizedHref(locale, "contact"),
        }}
      />
      <ArticleDetailContent article={article} locale={locale} />
      <FinalCtaSection locale={locale} content={messages.home.finalCta} />
    </>
  )
}
