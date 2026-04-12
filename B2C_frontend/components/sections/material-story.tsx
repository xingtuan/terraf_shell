"use client"

import Image from "next/image"

import type { SiteMessages } from "@/lib/i18n"
import { useSectionInView } from "@/hooks/use-section-in-view"

type MaterialStorySectionProps = {
  content: SiteMessages["home"]["materialStory"]
}

const processImages = [
  "/images/process-collected.jpg",
  "/images/process-refined.jpg",
  "/images/process-recrafted.jpg",
  "/images/application-tableware.jpg",
]

export function MaterialStorySection({
  content,
}: MaterialStorySectionProps) {
  const { sectionRef, isVisible } = useSectionInView<HTMLElement>(0.18)

  return (
    <section ref={sectionRef} className="bg-background py-24 lg:py-32">
      <div className="mx-auto max-w-7xl px-6 lg:px-8">
        <div className="mx-auto mb-16 max-w-3xl text-center lg:mb-24">
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

        <div className="relative">
          <div className="hidden lg:absolute lg:left-0 lg:right-0 lg:top-[200px] lg:block lg:h-px lg:bg-border" />

          <div className="grid grid-cols-1 gap-12 md:grid-cols-2 xl:grid-cols-4 xl:gap-8">
            {content.steps.map((step, index) => (
              <div
                key={step.number}
                className={`relative transition-all duration-700 ${
                  isVisible ? "translate-y-0 opacity-100" : "translate-y-8 opacity-0"
                }`}
                style={{ transitionDelay: `${200 + index * 120}ms` }}
              >
                <div className="group relative mb-8 aspect-[4/3] overflow-hidden rounded-2xl">
                  <Image
                    src={processImages[index] ?? processImages[0]}
                    alt={step.title}
                    fill
                    className="object-cover transition-transform duration-700 group-hover:scale-105"
                  />
                  <div className="absolute inset-0 bg-foreground/8 transition-opacity duration-300 group-hover:opacity-0" />
                </div>

                <div className="relative mb-6">
                  <div className="xl:absolute xl:left-1/2 xl:-top-[calc(100%+2rem)] xl:-translate-x-1/2">
                    <div className="inline-flex h-12 w-12 items-center justify-center rounded-full bg-primary text-sm font-medium text-primary-foreground">
                      {step.number}
                    </div>
                  </div>
                </div>

                <h3 className="mb-4 font-serif text-2xl text-foreground">
                  {step.title}
                </h3>
                <p className="leading-relaxed text-muted-foreground">
                  {step.description}
                </p>
              </div>
            ))}
          </div>
        </div>
      </div>
    </section>
  )
}
