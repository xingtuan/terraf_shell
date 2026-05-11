import type { SiteMessages } from "@/lib/i18n"

type LegalPageContent = SiteMessages["legal"]["privacy"]

type LegalPageProps = {
  content: LegalPageContent
}

export function LegalPage({ content }: LegalPageProps) {
  return (
    <article className="mx-auto max-w-4xl px-6 py-16 lg:px-8 lg:py-20">
      <header className="border-b border-border/60 pb-10">
        <p className="text-sm uppercase tracking-[0.2em] text-primary">
          {content.eyebrow}
        </p>
        <h1 className="mt-4 font-serif text-4xl leading-tight text-foreground md:text-5xl">
          {content.title}
        </h1>
        <p className="mt-5 max-w-3xl text-base leading-7 text-muted-foreground">
          {content.description}
        </p>
        <p className="mt-6 text-sm font-medium text-foreground">
          {content.lastUpdatedLabel}: {content.lastUpdated}
        </p>
      </header>

      <div className="space-y-10 pt-10">
        {content.sections.map((section) => (
          <section key={section.title}>
            <h2 className="font-serif text-2xl text-foreground">
              {section.title}
            </h2>
            <div className="mt-4 space-y-4 text-sm leading-7 text-muted-foreground">
              {section.paragraphs.map((paragraph) => (
                <p key={paragraph}>{paragraph}</p>
              ))}
            </div>
          </section>
        ))}
      </div>
    </article>
  )
}
