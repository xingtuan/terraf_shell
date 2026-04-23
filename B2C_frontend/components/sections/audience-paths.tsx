"use client"

import Link from "next/link"
import { ArrowRight } from "lucide-react"

import { Button } from "@/components/ui/button"
import { getLocalizedHref, type Locale, type SiteMessages } from "@/lib/i18n"
import { useSectionInView } from "@/hooks/use-section-in-view"

type AudiencePathsSectionProps = {
  locale: Locale
  content: SiteMessages["home"]["audiencePaths"]
}

export function AudiencePathsSection({ locale, content }: AudiencePathsSectionProps) {
  const { sectionRef, isVisible } = useSectionInView<HTMLElement>(0.2)

  return (
    <section ref={sectionRef} className="bg-card py-24 lg:py-32">
      <div className="mx-auto max-w-7xl px-6 lg:px-8">
        <div className="mb-16 max-w-3xl lg:mb-20">
          <p
            className={`mb-4 text-sm uppercase tracking-[0.2em] text-primary transition-all duration-700 ${
              isVisible ? "translate-y-0 opacity-100" : "translate-y-4 opacity-0"
            }`}
          >
            {content.eyebrow}
          </p>
          <h2
            className={`font-serif text-3xl leading-tight text-foreground transition-all duration-700 delay-100 md:text-4xl lg:text-5xl ${
              isVisible ? "translate-y-0 opacity-100" : "translate-y-4 opacity-0"
            }`}
          >
            {content.title}
          </h2>
        </div>

        <div className="grid grid-cols-1 gap-8 lg:grid-cols-3 lg:gap-10">
          {content.cards.map((card, index) => (
            <div
              key={card.label}
              className={`flex flex-col rounded-2xl border border-border/50 bg-background p-8 transition-all duration-700 hover:border-primary/30 ${
                isVisible ? "translate-y-0 opacity-100" : "translate-y-8 opacity-0"
              }`}
              style={{ transitionDelay: `${200 + index * 100}ms` }}
            >
              <div className="mb-6 h-px w-12 bg-primary" />
              <p className="mb-2 text-xs uppercase tracking-[0.18em] text-primary">
                {card.label}
              </p>
              <h3 className="mb-4 text-xl font-medium text-foreground">
                {card.title}
              </h3>
              <p className="mb-8 flex-1 leading-relaxed text-muted-foreground">
                {card.description}
              </p>
              <Button asChild variant="outline" className="group w-full">
                <Link href={getLocalizedHref(locale, card.href)}>
                  {card.cta}
                  <ArrowRight className="ml-2 h-4 w-4 transition-transform group-hover:translate-x-1" />
                </Link>
              </Button>
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}
