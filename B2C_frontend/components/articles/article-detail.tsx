import { getIntlLocale, type Locale } from "@/lib/i18n"
import type { ArticleDetail } from "@/lib/types"

type ArticleDetailProps = {
  article: ArticleDetail
  locale: Locale
}

function formatDate(locale: Locale, value?: string | null) {
  if (!value) {
    return null
  }

  return new Intl.DateTimeFormat(getIntlLocale(locale), {
    dateStyle: "medium",
  }).format(new Date(value))
}

export function ArticleDetailContent({
  article,
  locale,
}: ArticleDetailProps) {
  const formattedDate = formatDate(locale, article.published_at ?? article.created_at)

  return (
    <section className="bg-background py-24 lg:py-28">
      <div className="mx-auto max-w-4xl px-6 lg:px-8">
        <article className="overflow-hidden rounded-3xl border border-border/60 bg-card">
          {article.media_url ? (
            <div className="aspect-[16/9] w-full overflow-hidden bg-muted">
              <img
                src={article.media_url}
                alt={article.title}
                className="h-full w-full object-cover"
              />
            </div>
          ) : null}

          <div className="p-8 lg:p-10">
            <div className="flex flex-wrap items-center gap-3 text-xs uppercase tracking-[0.16em] text-primary">
              {article.category ? <span>{article.category}</span> : null}
              {article.category && formattedDate ? <span>/</span> : null}
              {formattedDate ? <span>{formattedDate}</span> : null}
            </div>

            {article.excerpt ? (
              <p className="mt-6 text-lg leading-relaxed text-muted-foreground">
                {article.excerpt}
              </p>
            ) : null}

            <div className="mt-10 whitespace-pre-wrap leading-8 text-foreground">
              {article.content}
            </div>
          </div>
        </article>
      </div>
    </section>
  )
}
