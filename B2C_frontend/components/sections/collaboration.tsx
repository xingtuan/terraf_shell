"use client"

import Link from "next/link"
import { ArrowRight, Boxes, FlaskConical, Package } from "lucide-react"

import { Button } from "@/components/ui/button"
import {
  getLocalizedHref,
  type Locale,
  type SiteMessages,
} from "@/lib/i18n"
import { useSectionInView } from "@/hooks/use-section-in-view"

type CollaborationSectionProps = {
  locale: Locale
  content: SiteMessages["home"]["collaboration"]
}

const collaborationIcons = [Package, FlaskConical, Boxes]

export function CollaborationSection({
  locale,
  content,
}: CollaborationSectionProps) {
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

        <div className="mb-16 grid grid-cols-1 gap-6 lg:grid-cols-3 lg:gap-8">
          {content.cards.map((card, index) => {
            const Icon = collaborationIcons[index] ?? Package

            return (
              <div
                key={card.title}
                className={`group flex flex-col rounded-2xl border border-border/50 bg-background p-8 transition-all duration-500 hover:border-primary/30 ${
                  isVisible ? "translate-y-0 opacity-100" : "translate-y-8 opacity-0"
                }`}
                style={{ transitionDelay: `${200 + index * 100}ms` }}
              >
                <div className="mb-6 flex h-14 w-14 items-center justify-center rounded-2xl bg-primary/10 transition-colors group-hover:bg-primary/20">
                  <Icon className="h-7 w-7 text-primary" />
                </div>

                <h3 className="mb-2 font-serif text-2xl text-foreground">
                  {card.title}
                </h3>
                <p className="mb-4 text-sm text-primary">{card.forWhom}</p>
                <p className="mb-8 flex-grow leading-relaxed text-muted-foreground">
                  {card.description}
                </p>

                <Button asChild variant="ghost" className="w-full justify-between">
                  <Link href={`${getLocalizedHref(locale, "b2b")}#inquiry`}>
                    {card.cta}
                    <ArrowRight className="h-4 w-4" />
                  </Link>
                </Button>
              </div>
            )
          })}
        </div>

        <div
          className={`rounded-2xl border border-border/50 bg-background p-8 transition-all duration-700 delay-500 lg:p-10 ${
            isVisible ? "translate-y-0 opacity-100" : "translate-y-8 opacity-0"
          }`}
        >
          <p className="mb-6 text-center text-sm text-muted-foreground">
            {content.processTitle}
          </p>
          <div className="flex flex-col items-center justify-center gap-4 md:flex-row md:gap-0">
            {content.steps.map((step, index) => (
              <div key={step} className="flex items-center gap-4">
                <div className="flex items-center gap-4">
                  <div className="flex h-10 w-10 items-center justify-center rounded-full bg-primary text-sm font-medium text-primary-foreground">
                    {String(index + 1).padStart(2, "0")}
                  </div>
                  <span className="text-foreground">{step}</span>
                </div>
                {index < content.steps.length - 1 && (
                  <div className="mx-4 hidden h-px w-16 bg-border md:block" />
                )}
              </div>
            ))}
          </div>
        </div>
      </div>
    </section>
  )
}
