import Link from "next/link"

import { stripHtml } from "@/lib/api/normalizers"
import { getLocalizedHref, getIntlLocale, getMessages, type Locale } from "@/lib/i18n"
import type { ArticleSummary } from "@/lib/types"
import { Button } from "@/components/ui/button"

type ArticleFeedSectionProps = {
  locale: Locale
  eyebrow: string
  title: string
  description: string
  articles: ArticleSummary[]
  id?: string
  cta?: {
    label: string
    href: string
  } | null
  emptyTitle?: string
  emptyDescription?: string
}

function formatDate(locale: Locale, value?: string | null) {
  if (!value) {
    return null
  }

  return new Intl.DateTimeFormat(getIntlLocale(locale), {
    dateStyle: "medium",
  }).format(new Date(value))
}

function getArticleExcerpt(article: ArticleSummary, fallback: string) {
  const candidate = stripHtml(article.excerpt || article.content)

  if (!candidate) {
    return fallback
  }

  if (candidate.length <= 180) {
    return candidate
  }

  return `${candidate.slice(0, 180)}...`
}

export function ArticleFeedSection({
  locale,
  eyebrow,
  title,
  description,
  articles,
  id = "articles",
  cta = null,
  emptyTitle,
  emptyDescription,
}: ArticleFeedSectionProps) {
  const t = getMessages(locale).articleFeed
  const resolvedEmptyTitle = emptyTitle ?? t.emptyTitle
  const resolvedEmptyDescription = emptyDescription ?? t.emptyDescription
  return (
    <section id={id} className="bg-card py-24 lg:py-28">
      <div className="mx-auto max-w-7xl px-6 lg:px-8">
        <div className="mb-14 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
          <div className="max-w-3xl">
            <p className="mb-4 text-sm uppercase tracking-[0.2em] text-primary">
              {eyebrow}
            </p>
            <h2 className="mb-5 font-serif text-3xl leading-tight text-foreground md:text-4xl lg:text-5xl">
              {title}
            </h2>
            <p className="text-lg leading-relaxed text-muted-foreground">
              {description}
            </p>
          </div>

          {cta ? (
            <Button asChild variant="outline" size="lg">
              <Link href={cta.href}>{cta.label}</Link>
            </Button>
          ) : null}
        </div>

        {articles.length === 0 ? (
          <div className="rounded-3xl border border-border/60 bg-background p-8">
            <h3 className="font-serif text-2xl text-foreground">{resolvedEmptyTitle}</h3>
            <p className="mt-3 max-w-2xl text-muted-foreground">
              {resolvedEmptyDescription}
            </p>
          </div>
        ) : (
          <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
            {articles.map((article) => (
              <article
                key={article.id}
                className="overflow-hidden rounded-3xl border border-border/60 bg-background"
              >
                <div className="aspect-[4/3] w-full overflow-hidden bg-muted">
                  {article.media_url ? (
                    <img
                      src={article.media_url}
                      alt={article.title}
                      className="h-full w-full object-cover"
                      loading="lazy"
                    />
                  ) : (
                    <div className="flex h-full items-end bg-[radial-gradient(circle_at_top_left,rgba(67,108,109,0.26),transparent_50%),linear-gradient(135deg,rgba(202,190,166,0.18),rgba(67,108,109,0.06))] p-6">
                      <span className="rounded-full border border-white/20 px-3 py-1 text-xs uppercase tracking-[0.18em] text-foreground/70">
                        {t.badgeLabel}
                      </span>
                    </div>
                  )}
                </div>

                <div className="p-7">
                  <div className="mb-4 flex flex-wrap items-center gap-2 text-xs uppercase tracking-[0.16em] text-primary">
                    {article.category ? <span>{article.category}</span> : null}
                    {article.category && article.published_at ? <span>/</span> : null}
                    {article.published_at ? (
                      <span>{formatDate(locale, article.published_at)}</span>
                    ) : null}
                  </div>

                  <Link
                    href={getLocalizedHref(locale, `articles/${article.slug}`)}
                    className="font-serif text-2xl leading-tight text-foreground transition-colors hover:text-primary"
                  >
                    {article.title}
                  </Link>

                  <p className="mt-4 leading-relaxed text-muted-foreground">
                    {getArticleExcerpt(article, t.fallbackExcerpt)}
                  </p>

                  <div className="mt-8">
                    <Button asChild variant="ghost" className="px-0">
                      <Link href={getLocalizedHref(locale, `articles/${article.slug}`)}>
                        {t.readArticle}
                      </Link>
                    </Button>
                  </div>
                </div>
              </article>
            ))}
          </div>
        )}
      </div>
    </section>
  )
}
