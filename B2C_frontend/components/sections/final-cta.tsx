"use client"

import Link from "next/link"

import { Button } from "@/components/ui/button"
import {
  getLocalizedHref,
  type Locale,
  type SiteMessages,
} from "@/lib/i18n"
import { useSectionInView } from "@/hooks/use-section-in-view"

type FinalCtaSectionProps = {
  locale: Locale
  content: SiteMessages["home"]["finalCta"]
}

export function FinalCtaSection({ locale, content }: FinalCtaSectionProps) {
  const { sectionRef, isVisible } = useSectionInView<HTMLElement>(0.3)

  return (
    <section ref={sectionRef} className="bg-primary py-24 lg:py-32">
      <div className="mx-auto max-w-7xl px-6 lg:px-8">
        <div className="mx-auto max-w-3xl text-center">
          <h2
            className={`mb-6 font-serif text-3xl leading-tight text-primary-foreground transition-all duration-700 md:text-4xl lg:text-5xl ${
              isVisible ? "translate-y-0 opacity-100" : "translate-y-4 opacity-0"
            }`}
          >
            {content.title}
          </h2>

          <p
            className={`mb-10 text-lg text-primary-foreground/80 transition-all duration-700 delay-100 ${
              isVisible ? "translate-y-0 opacity-100" : "translate-y-4 opacity-0"
            }`}
          >
            {content.description}
          </p>

          <div
            className={`flex flex-col justify-center gap-4 transition-all duration-700 delay-200 sm:flex-row ${
              isVisible ? "translate-y-0 opacity-100" : "translate-y-4 opacity-0"
            }`}
          >
            <Button asChild size="lg" className="bg-primary-foreground px-8 text-base text-primary hover:bg-primary-foreground/90">
              <Link href={`${getLocalizedHref(locale, "b2b")}#inquiry`}>
                {content.primaryCta}
              </Link>
            </Button>
            <Button asChild size="lg" variant="outline" className="border-primary-foreground/30 px-8 text-base text-primary-foreground hover:bg-primary-foreground/10">
              <Link href={getLocalizedHref(locale, "store")}>
                {content.secondaryCta}
              </Link>
            </Button>
          </div>
        </div>
      </div>
    </section>
  )
}
