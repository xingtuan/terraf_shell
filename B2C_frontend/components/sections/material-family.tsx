"use client"

import Link from "next/link"

import { getLocalizedHref, type Locale, type SiteMessages } from "@/lib/i18n"
import { useSectionInView } from "@/hooks/use-section-in-view"

type MaterialFamilySectionProps = {
  locale: Locale
  content: SiteMessages["home"]["materialFamily"]
}

export function MaterialFamilySection({ locale, content }: MaterialFamilySectionProps) {
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

        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-5">
          {content.lines.map((line, index) => {
            const isAvailable = line.status === "available"

            const card = (
              <div
                className={`rounded-2xl border p-6 transition-colors duration-300 ${
                  isAvailable
                    ? "border-primary/40 bg-card hover:border-primary"
                    : "border-border/30 bg-card/50 opacity-50"
                }`}
              >
                <p className="mb-3 font-serif text-2xl text-primary">{line.code}</p>
                <p className="mb-1 font-medium text-foreground">{line.name}</p>
                <p className="text-sm leading-relaxed text-muted-foreground">{line.source}</p>
                {!isAvailable && (
                  <p className="mt-4 text-xs uppercase tracking-widest text-muted-foreground">
                    Coming Soon
                  </p>
                )}
              </div>
            )

            return (
              <div
                key={line.code}
                className={`transition-all duration-700 ${
                  isVisible ? "translate-y-0 opacity-100" : "translate-y-8 opacity-0"
                }`}
                style={{ transitionDelay: `${200 + index * 80}ms` }}
              >
                {isAvailable ? (
                  <Link href={getLocalizedHref(locale, "material")} className="block">
                    {card}
                  </Link>
                ) : (
                  card
                )}
              </div>
            )
          })}
        </div>
      </div>
    </section>
  )
}
