"use client"

import type { SiteMessages } from "@/lib/i18n"
import { useSectionInView } from "@/hooks/use-section-in-view"

type OpenSourceLegacySectionProps = {
  content: SiteMessages["home"]["openSourceLegacy"]
}

export function OpenSourceLegacySection({ content }: OpenSourceLegacySectionProps) {
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
            className={`mb-6 font-serif text-3xl leading-tight text-foreground transition-all duration-700 delay-100 md:text-4xl lg:text-5xl ${
              isVisible ? "translate-y-0 opacity-100" : "translate-y-4 opacity-0"
            }`}
          >
            {content.title}
          </h2>
          <p
            className={`text-lg leading-relaxed text-muted-foreground transition-all duration-700 delay-200 ${
              isVisible ? "translate-y-0 opacity-100" : "translate-y-4 opacity-0"
            }`}
          >
            {content.intro}
          </p>
        </div>

        <div className="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:gap-10">
          {content.authors.map((entry, index) => (
            <div
              key={entry.author}
              className={`rounded-2xl bg-background p-8 transition-all duration-700 ${
                isVisible ? "translate-y-0 opacity-100" : "translate-y-8 opacity-0"
              }`}
              style={{ transitionDelay: `${300 + index * 100}ms` }}
            >
              <div className="mb-4 flex items-center gap-3">
                <div className="h-px w-8 bg-primary" />
                <span className="text-xs uppercase tracking-widest text-muted-foreground">
                  {entry.timeframe}
                </span>
              </div>
              <h3 className="mb-2 font-serif text-2xl text-foreground">
                {entry.author}
              </h3>
              <p className="mb-4 text-sm font-medium uppercase tracking-wide text-primary">
                {entry.sourceCode}
              </p>
              <p className="leading-relaxed text-muted-foreground">
                {entry.legacy}
              </p>
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}
