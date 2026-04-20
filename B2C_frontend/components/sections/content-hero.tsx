import Link from "next/link"

import { Button } from "@/components/ui/button"

type ContentHeroSectionProps = {
  title?: string | null
  subtitle?: string | null
  ctaLabel?: string | null
  ctaHref?: string | null
}

export function ContentHeroSection({
  title,
  subtitle,
  ctaLabel,
  ctaHref,
}: ContentHeroSectionProps) {
  if (!title && !subtitle) {
    return null
  }

  return (
    <section className="relative overflow-hidden border-b border-border/60 bg-background pt-32 pb-20 lg:pt-40 lg:pb-24">
      <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(67,108,109,0.14),transparent_36%),radial-gradient(circle_at_bottom_right,rgba(202,190,166,0.18),transparent_32%)]" />
      <div className="relative mx-auto max-w-7xl px-6 lg:px-8">
        <div className="max-w-4xl">
          {title ? (
            <h1 className="font-serif text-4xl leading-[1.08] text-foreground md:text-5xl lg:text-6xl">
              {title}
            </h1>
          ) : null}
          {subtitle ? (
            <p className="mt-7 max-w-2xl text-lg leading-relaxed text-muted-foreground">
              {subtitle}
            </p>
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
