"use client"

import Image from "next/image"
import { ArrowUpRight } from "lucide-react"

import type { SiteMessages } from "@/lib/i18n"
import { useSectionInView } from "@/hooks/use-section-in-view"

type ApplicationsSectionProps = {
  content: SiteMessages["home"]["applications"]
}

const applicationImages = [
  "/images/application-tableware.jpg",
  "/images/application-interior.jpg",
  "/images/application-packaging.jpg",
  "/images/application-retail.jpg",
]

export function ApplicationsSection({
  content,
}: ApplicationsSectionProps) {
  const { sectionRef, isVisible } = useSectionInView<HTMLElement>(0.1)

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

        <div className="grid grid-cols-1 gap-6 md:grid-cols-2 lg:gap-8">
          {content.items.map((item, index) => (
            <div
              key={item.title}
              className={`group relative overflow-hidden rounded-2xl bg-background transition-all duration-700 ${
                isVisible ? "translate-y-0 opacity-100" : "translate-y-8 opacity-0"
              }`}
              style={{ transitionDelay: `${200 + index * 100}ms` }}
            >
              <div className="relative aspect-[16/10] overflow-hidden">
                <Image
                  src={applicationImages[index] ?? applicationImages[0]}
                  alt={item.title}
                  fill
                  className="object-cover transition-transform duration-700 group-hover:scale-105"
                />
                <div className="absolute inset-0 bg-gradient-to-t from-foreground/65 via-foreground/18 to-transparent" />
              </div>

              <div className="absolute inset-x-0 bottom-0 p-6 lg:p-8">
                <div className="flex items-end justify-between gap-4">
                  <div>
                    <h3 className="mb-2 font-serif text-xl text-background lg:text-2xl">
                      {item.title}
                    </h3>
                    <p className="text-sm text-background/80">
                      {item.description}
                    </p>
                  </div>
                  <div className="flex h-10 w-10 flex-shrink-0 translate-y-4 items-center justify-center rounded-full bg-background/20 opacity-0 backdrop-blur-sm transition-all duration-300 group-hover:translate-y-0 group-hover:opacity-100">
                    <ArrowUpRight className="h-5 w-5 text-background" />
                  </div>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}
