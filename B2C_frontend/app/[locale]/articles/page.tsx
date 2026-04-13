import { PageIntro } from "@/components/page-intro"
import { ArticleFeedSection } from "@/components/sections/article-feed"
import { FinalCtaSection } from "@/components/sections/final-cta"
import { listArticles } from "@/lib/api/articles"
import { getLocalizedHref, getMessages } from "@/lib/i18n"
import { resolveLocale } from "@/lib/resolve-locale"
import type { ArticleSummary } from "@/lib/types"

type ArticlesPageProps = {
  params: Promise<{ locale: string }>
}

export default async function ArticlesPage({ params }: ArticlesPageProps) {
  const locale = await resolveLocale(params)
  const messages = getMessages(locale)

  let articles: ArticleSummary[] = []

  try {
    const response = await listArticles({ per_page: 12 })
    articles = response.items
  } catch {
    articles = []
  }

  return (
    <>
      <PageIntro
        eyebrow="Journal"
        title="Articles, lab notes, and material updates."
        description="This editorial layer is now connected to the backend article CMS while preserving the existing Shellfin visual language."
        primaryAction={{
          label: "Latest updates",
          href: `${getLocalizedHref(locale, "articles")}#articles`,
        }}
        secondaryAction={{
          label: messages.header.contact,
          href: getLocalizedHref(locale, "contact"),
        }}
      />
      <ArticleFeedSection
        locale={locale}
        eyebrow="Published Articles"
        title="Backend-driven editorial content for the material platform."
        description="The cards below are powered by `/api/articles`, with graceful empty-state handling when no published records are available."
        articles={articles}
      />
      <FinalCtaSection locale={locale} content={messages.home.finalCta} />
    </>
  )
}
