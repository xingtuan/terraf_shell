import type { LegalPageContent } from "@/lib/api/legal-pages"

type LegalPageProps = {
  content: LegalPageContent
}

export function LegalPage({ content }: LegalPageProps) {
  const bodyHtml = renderableHtml(content.bodyHtml)
  const hasHeaderContent = Boolean(
    content.eyebrow ||
      content.title ||
      content.description ||
      content.lastUpdated ||
      content.lastUpdatedLabel,
  )

  return (
    <article className="mx-auto max-w-4xl px-6 py-16 lg:px-8 lg:py-20">
      {hasHeaderContent ? (
        <header className="border-b border-border/60 pb-10">
          {content.eyebrow ? (
            <p className="text-sm uppercase tracking-[0.2em] text-primary">
              {content.eyebrow}
            </p>
          ) : null}
          {content.title ? (
            <h1 className="mt-4 font-serif text-4xl leading-tight text-foreground md:text-5xl">
              {content.title}
            </h1>
          ) : null}
          {content.description ? (
            <p className="mt-5 max-w-3xl text-base leading-7 text-muted-foreground">
              {content.description}
            </p>
          ) : null}
          {content.lastUpdated || content.lastUpdatedLabel ? (
            <p className="mt-6 text-sm font-medium text-foreground">
              {[content.lastUpdatedLabel, content.lastUpdated]
                .filter(Boolean)
                .join(": ")}
            </p>
          ) : null}
        </header>
      ) : null}

      {bodyHtml ? (
        <div
          className="pt-10 text-sm leading-7 text-muted-foreground [&_a]:font-medium [&_a]:text-foreground [&_a]:underline [&_blockquote]:border-l [&_blockquote]:border-border [&_blockquote]:pl-4 [&_h2]:mt-10 [&_h2]:font-serif [&_h2]:text-2xl [&_h2]:text-foreground [&_h3]:mt-8 [&_h3]:text-lg [&_h3]:font-semibold [&_h3]:text-foreground [&_li]:my-2 [&_ol]:my-4 [&_ol]:list-decimal [&_ol]:pl-6 [&_p]:my-4 [&_strong]:text-foreground [&_ul]:my-4 [&_ul]:list-disc [&_ul]:pl-6"
          dangerouslySetInnerHTML={{ __html: bodyHtml }}
        />
      ) : null}
    </article>
  )
}

function renderableHtml(value?: string | null) {
  const html = value?.trim()

  if (!html) {
    return null
  }

  const text = html
    .replace(/<[^>]+>/g, "")
    .replace(/&nbsp;/gi, " ")
    .trim()

  return text ? html : null
}
