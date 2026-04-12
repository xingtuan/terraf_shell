import Link from "next/link"

import { Button } from "@/components/ui/button"

type PageIntroProps = {
  eyebrow: string
  title: string
  description: string
  primaryAction: {
    label: string
    href: string
  }
  secondaryAction: {
    label: string
    href: string
  }
}

export function PageIntro({
  eyebrow,
  title,
  description,
  primaryAction,
  secondaryAction,
}: PageIntroProps) {
  return (
    <section className="relative overflow-hidden border-b border-border/60 bg-background pt-32 pb-20 lg:pt-40 lg:pb-24">
      <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(67,108,109,0.12),transparent_36%),radial-gradient(circle_at_bottom_right,rgba(202,190,166,0.18),transparent_32%)]" />
      <div className="relative mx-auto max-w-7xl px-6 lg:px-8">
        <div className="max-w-4xl">
          <p className="mb-5 text-sm uppercase tracking-[0.24em] text-primary animate-fade-in opacity-0">
            {eyebrow}
          </p>
          <h1 className="mb-7 max-w-3xl font-serif text-4xl leading-[1.08] text-foreground animate-fade-in-up opacity-0 animation-delay-100 md:text-5xl lg:text-6xl">
            {title}
          </h1>
          <p className="max-w-2xl text-lg leading-relaxed text-muted-foreground animate-fade-in-up opacity-0 animation-delay-200">
            {description}
          </p>
          <div className="mt-10 flex flex-col gap-4 animate-fade-in-up opacity-0 animation-delay-300 sm:flex-row">
            <Button asChild size="lg" className="px-8">
              <Link href={primaryAction.href}>{primaryAction.label}</Link>
            </Button>
            <Button asChild size="lg" variant="outline" className="px-8">
              <Link href={secondaryAction.href}>{secondaryAction.label}</Link>
            </Button>
          </div>
        </div>
      </div>
    </section>
  )
}
