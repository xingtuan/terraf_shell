"use client"

import type { SiteMessages } from "@/lib/i18n"
import { useSectionInView } from "@/hooks/use-section-in-view"

type BusinessPillarsSectionProps = {
  content: SiteMessages["home"]["businessPillars"]
}

export function BusinessPillarsSection({ content }: BusinessPillarsSectionProps) {
  const { sectionRef, isVisible } = useSectionInView<HTMLElement>(0.2)

  return (
    <section ref={sectionRef} className="bg-background py-24 lg:py-32">
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

        <div className="grid grid-cols-1 gap-8 md:grid-cols-3 lg:gap-12">
          {content.pillars.map((pillar, index) => (
            <div
              key={pillar.name}
              className={`flex flex-col rounded-2xl border border-border/50 bg-card p-8 transition-all duration-700 ${
                isVisible ? "translate-y-0 opacity-100" : "translate-y-8 opacity-0"
              }`}
              style={{ transitionDelay: `${200 + index * 150}ms` }}
            >
              <div className="mb-6 h-px w-12 bg-primary" />
              <h3 className="mb-3 text-lg font-medium text-foreground">
                {pillar.name}
              </h3>
              <p className="mb-6 font-serif text-2xl leading-snug text-primary">
                {pillar.formula}
              </p>
              <p className="leading-relaxed text-muted-foreground">
                {pillar.description}
              </p>
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}
