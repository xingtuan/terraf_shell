"use client"

import type { SiteMessages } from "@/lib/i18n"
import { useSectionInView } from "@/hooks/use-section-in-view"

type WhyItMattersSectionProps = {
  content: SiteMessages["home"]["whyItMatters"]
}

export function WhyItMattersSection({
  content,
}: WhyItMattersSectionProps) {
  const { sectionRef, isVisible } = useSectionInView<HTMLElement>(0.2)

  return (
    <section ref={sectionRef} className="bg-card py-24 lg:py-32">
      <div className="mx-auto max-w-7xl px-6 lg:px-8">
        <div className="mb-16 max-w-3xl lg:mb-24">
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

        <div className="mb-20 grid grid-cols-1 gap-8 md:grid-cols-3 lg:gap-12">
          {content.cards.map((card, index) => (
            <div
              key={card.title}
              className={`transition-all duration-700 ${
                isVisible ? "translate-y-0 opacity-100" : "translate-y-8 opacity-0"
              }`}
              style={{ transitionDelay: `${200 + index * 100}ms` }}
            >
              <div className="mb-6 h-px w-12 bg-primary" />
              <h3 className="mb-4 text-lg font-medium text-foreground">
                {card.title}
              </h3>
              <p className="leading-relaxed text-muted-foreground">
                {card.description}
              </p>
            </div>
          ))}
        </div>

        <div
          className={`rounded-2xl bg-background p-8 transition-all duration-700 delay-500 lg:p-10 ${
            isVisible ? "translate-y-0 opacity-100" : "translate-y-8 opacity-0"
          }`}
        >
          <div className="grid grid-cols-1 gap-8 md:grid-cols-3 lg:gap-12">
            {content.stats.map((stat, index) => (
              <div key={stat} className="flex items-start gap-4">
                <div className="flex h-8 w-8 flex-shrink-0 items-center justify-center rounded-full bg-primary/10">
                  <span className="text-sm font-medium text-primary">
                    {index + 1}
                  </span>
                </div>
                <p className="text-sm leading-relaxed text-muted-foreground">
                  {stat}
                </p>
              </div>
            ))}
          </div>
        </div>
      </div>
    </section>
  )
}
