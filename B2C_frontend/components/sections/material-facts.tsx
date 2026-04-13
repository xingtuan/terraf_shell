"use client"

import Link from "next/link"
import { ArrowRight, BadgeCheck, Leaf, Scale, Shield } from "lucide-react"

import { Button } from "@/components/ui/button"
import type { Locale, SiteMessages } from "@/lib/i18n"
import { getLocalizedHref } from "@/lib/i18n"
import type { MaterialSpec } from "@/lib/types"
import { useSectionInView } from "@/hooks/use-section-in-view"

type MaterialFactsSectionProps = {
  locale: Locale
  content: SiteMessages["home"]["materialFacts"]
  specs: MaterialSpec[]
  sheetHref?: string
}

const specIcons = {
  feather: Scale,
  shield: Shield,
  leaf: Leaf,
  badge: BadgeCheck,
} as const

export function MaterialFactsSection({
  locale,
  content,
  specs,
  sheetHref = `${getLocalizedHref(locale, "b2b")}#inquiry`,
}: MaterialFactsSectionProps) {
  const { sectionRef, isVisible } = useSectionInView<HTMLElement>(0.2)

  return (
    <section ref={sectionRef} className="bg-background py-24 lg:py-32">
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

        <div className="grid grid-cols-1 gap-12 lg:grid-cols-2 lg:gap-16">
          <div className="grid grid-cols-1 gap-6 sm:grid-cols-2">
            {specs.map((spec, index) => {
              const Icon = specIcons[spec.icon]

              return (
                <div
                  key={spec.id}
                  className={`group rounded-2xl border border-border/50 bg-card p-6 transition-all duration-500 hover:border-primary/30 ${
                    isVisible ? "translate-y-0 opacity-100" : "translate-y-8 opacity-0"
                  }`}
                  style={{ transitionDelay: `${200 + index * 100}ms` }}
                >
                  <div className="mb-4 flex items-center gap-3">
                    <div className="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10 transition-colors group-hover:bg-primary/20">
                      <Icon className="h-5 w-5 text-primary" />
                    </div>
                    <span className="text-sm uppercase tracking-wide text-muted-foreground">
                      {spec.label}
                    </span>
                  </div>
                  <p className="mb-2 font-serif text-2xl text-foreground">
                    {spec.value}
                  </p>
                  <p className="text-sm text-muted-foreground">
                    {spec.detail}
                  </p>
                </div>
              )
            })}
          </div>

          <div
            className={`space-y-6 transition-all duration-700 delay-500 ${
              isVisible ? "translate-y-0 opacity-100" : "translate-y-8 opacity-0"
            }`}
          >
            <div className="rounded-2xl border border-border/50 bg-card p-8">
              <div className="mb-6 flex items-start gap-4">
                <div className="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-primary/10">
                  <BadgeCheck className="h-6 w-6 text-primary" />
                </div>
                <div>
                  <h3 className="mb-1 font-medium text-foreground">
                    {content.sheetTitle}
                  </h3>
                  <p className="text-sm text-muted-foreground">
                    {content.sheetDescription}
                  </p>
                </div>
              </div>
              <Button asChild variant="outline" className="w-full group">
                <Link href={sheetHref}>
                  {content.sheetCta}
                  <ArrowRight className="ml-2 h-4 w-4 transition-transform group-hover:translate-x-1" />
                </Link>
              </Button>
            </div>

            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
              {content.infoCards.map((card) => (
                <div key={card.label} className="rounded-2xl bg-secondary/50 p-6">
                  <p className="mb-2 text-sm text-muted-foreground">
                    {card.label}
                  </p>
                  <p className="text-foreground">{card.value}</p>
                </div>
              ))}
            </div>

            <p className="text-sm leading-relaxed text-muted-foreground">
              {content.note}
            </p>
          </div>
        </div>
      </div>
    </section>
  )
}
