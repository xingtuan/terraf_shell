import Link from "next/link"

import { Button } from "@/components/ui/button"

type ContentBlockSectionProps = {
  title?: string | null
  subtitle?: string | null
  body?: string | null
  ctaLabel?: string | null
  ctaHref?: string | null
  align?: "left" | "center"
}

function renderParagraphs(body?: string | null) {
  return (body ?? "")
    .split(/\n+/)
    .map((paragraph) => paragraph.trim())
    .filter(Boolean)
    .map((paragraph) => (
      <p key={paragraph} className="text-lg leading-relaxed text-muted-foreground">
        {paragraph}
      </p>
    ))
}

export function ContentBlockSection({
  title,
  subtitle,
  body,
  ctaLabel,
  ctaHref,
  align = "left",
}: ContentBlockSectionProps) {
  if (!title && !subtitle && !body && !(ctaLabel && ctaHref)) {
    return null
  }

  const isCentered = align === "center"

  return (
    <section className="bg-background py-20 lg:py-24">
      <div className="mx-auto max-w-7xl px-6 lg:px-8">
        <div className={isCentered ? "mx-auto max-w-3xl text-center" : "max-w-3xl"}>
          {subtitle ? (
            <p className="mb-4 text-sm uppercase tracking-[0.2em] text-primary">
              {subtitle}
            </p>
          ) : null}
          {title ? (
            <h2 className="font-serif text-3xl leading-tight text-foreground md:text-4xl lg:text-5xl">
              {title}
            </h2>
          ) : null}
          {body ? (
            <div className="mt-6 space-y-4">
              {renderParagraphs(body)}
            </div>
          ) : null}
          {ctaLabel && ctaHref ? (
            <div className="mt-10">
              <Button asChild size="lg" className="px-8">
                <Link href={ctaHref}>{ctaLabel}</Link>
              </Button>
            </div>
          ) : null}
        </div>
      </div>
    </section>
  )
}
