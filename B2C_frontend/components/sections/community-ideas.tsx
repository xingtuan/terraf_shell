import Image from "next/image"
import Link from "next/link"

import { Button } from "@/components/ui/button"
import { getLocalizedHref, type Locale } from "@/lib/i18n"
import type { CommunityIdeasContent } from "@/lib/page-content"
import type { CommunityIdea } from "@/lib/types"

type CommunityIdeasSectionProps = {
  locale: Locale
  content: CommunityIdeasContent
  ideas: CommunityIdea[]
}

export function CommunityIdeasSection({
  locale,
  content,
  ideas,
}: CommunityIdeasSectionProps) {
  return (
    <section className="bg-background py-24 lg:py-28">
      <div className="mx-auto max-w-7xl px-6 lg:px-8">
        <div className="mb-14 max-w-3xl">
          <p className="mb-4 text-sm uppercase tracking-[0.2em] text-primary">
            {content.eyebrow}
          </p>
          <h2 className="mb-5 font-serif text-3xl leading-tight text-foreground md:text-4xl lg:text-5xl">
            {content.title}
          </h2>
          <p className="text-lg leading-relaxed text-muted-foreground">
            {content.description}
          </p>
        </div>

        <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
          {ideas.map((idea) => (
            <article
              key={idea.id}
              className="overflow-hidden rounded-3xl border border-border/60 bg-card"
            >
              <div className="relative aspect-[4/3]">
                <Image
                  src={idea.image}
                  alt={idea.title}
                  fill
                  className="object-cover"
                />
              </div>
              <div className="p-7">
                <div className="mb-4 flex flex-wrap gap-2">
                  {idea.tags.map((tag) => (
                    <span
                      key={tag}
                      className="rounded-full border border-border/70 px-3 py-1 text-xs text-muted-foreground"
                    >
                      {tag}
                    </span>
                  ))}
                </div>

                <h3 className="mb-3 font-serif text-2xl text-foreground">
                  {idea.title}
                </h3>
                <p className="mb-6 leading-relaxed text-muted-foreground">
                  {idea.summary}
                </p>

                <div className="grid grid-cols-1 gap-4 border-t border-border/70 pt-5 text-sm">
                  <div>
                    <p className="text-xs uppercase tracking-[0.18em] text-muted-foreground">
                      {content.focusLabel}
                    </p>
                    <p className="mt-1 text-foreground">{idea.focus}</p>
                  </div>
                  <div>
                    <p className="text-xs uppercase tracking-[0.18em] text-muted-foreground">
                      {content.stageLabel}
                    </p>
                    <p className="mt-1 text-foreground">{idea.stage}</p>
                  </div>
                  <div>
                    <p className="text-xs uppercase tracking-[0.18em] text-muted-foreground">
                      {content.supportLabel}
                    </p>
                    <p className="mt-1 text-foreground">{idea.supportType}</p>
                  </div>
                </div>

                {(idea.ctaUrl || idea.fundingUrl) ? (
                  <div className="mt-5 flex flex-wrap gap-3 border-t border-border/70 pt-5">
                    {idea.ctaUrl ? (
                      <Link
                        href={idea.ctaUrl}
                        className="inline-flex items-center text-sm font-medium text-primary hover:underline"
                      >
                        {idea.ctaLabel ?? content.ctaPrimary}
                      </Link>
                    ) : null}
                    {idea.fundingUrl ? (
                      <Link
                        href={idea.fundingUrl}
                        className="inline-flex items-center text-sm font-medium text-muted-foreground hover:underline"
                      >
                        {idea.fundingLabel ?? content.ctaSecondary}
                      </Link>
                    ) : null}
                  </div>
                ) : null}
              </div>
            </article>
          ))}
        </div>

        <div className="mt-12 flex flex-col gap-4 sm:flex-row">
          <Button asChild size="lg">
            <Link href={content.ctaPrimaryHref ?? getLocalizedHref(locale, "community/new")}>
              {content.ctaPrimary}
            </Link>
          </Button>
          <Button asChild size="lg" variant="outline">
            <Link href={content.ctaSecondaryHref ?? getLocalizedHref(locale, "contact")}>
              {content.ctaSecondary}
            </Link>
          </Button>
        </div>
      </div>
    </section>
  )
}
