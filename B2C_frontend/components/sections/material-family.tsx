"use client"

import Image from "next/image"
import Link from "next/link"

import { getLocalizedHref, type Locale, type SiteMessages } from "@/lib/i18n"
import { useSectionInView } from "@/hooks/use-section-in-view"

type MaterialFamilySectionProps = {
  locale: Locale
  content: SiteMessages["home"]["materialFamily"]
}

const legendDotClasses = [
  "bg-[#d73d32]",
  "bg-[#2f9d62]",
  "bg-[#7b4db8]",
]

export function MaterialFamilySection({ locale, content }: MaterialFamilySectionProps) {
  const { sectionRef, isVisible } = useSectionInView<HTMLElement>(0.2)
  const diagramSrc = locale === "ko" ? "/image/terraf_ko.jpg" : "/image/terraf_en.jpg"

  return (
    <section ref={sectionRef} className="bg-background py-24 lg:py-32">
      <div className="mx-auto max-w-7xl px-6 lg:px-8">
        <div className="mb-12 max-w-4xl lg:mb-16">
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

        <div
          className={`mb-10 grid gap-6 transition-all duration-700 delay-300 lg:mb-12 lg:grid-cols-[minmax(0,1fr)_360px] ${
            isVisible ? "translate-y-0 opacity-100" : "translate-y-8 opacity-0"
          }`}
        >
          <figure className="overflow-hidden rounded-2xl border border-border/60 bg-card shadow-sm shadow-foreground/[0.03]">
            <div className="bg-white p-3 sm:p-4">
              <div className="relative aspect-[4/3] w-full overflow-hidden rounded-xl bg-white sm:aspect-[16/10] lg:aspect-[16/9]">
                <Image
                  src={diagramSrc}
                  alt={content.diagram.alt}
                  fill
                  sizes="(min-width: 1280px) 850px, (min-width: 1024px) 65vw, 100vw"
                  className="object-contain"
                />
              </div>
            </div>
            <figcaption className="border-t border-border/60 px-5 py-4 text-sm leading-relaxed text-muted-foreground sm:px-6">
              <span className="mr-2 font-medium text-foreground">{content.diagram.title}</span>
              {content.diagram.caption}
            </figcaption>
          </figure>

          <div className="rounded-2xl border border-primary/20 bg-primary/5 p-6 lg:p-7">
            <div className="space-y-5">
              {content.legend.map((item, index) => (
                <div key={item.label} className="flex gap-4">
                  <span
                    className={`mt-1.5 h-3 w-3 shrink-0 rounded-full ${
                      legendDotClasses[index] ?? "bg-primary"
                    }`}
                    aria-hidden="true"
                  />
                  <div>
                    <p className="font-medium text-foreground">{item.label}</p>
                    <p className="mt-1 text-sm leading-relaxed text-muted-foreground">
                      {item.description}
                    </p>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>

        <div className="grid grid-cols-1 gap-6 lg:grid-cols-3">
          {content.lines.map((line, index) => {
            const isCurrent = line.status === "available"
            const isSibling = line.status === "sibling"
            const isInactive = line.status === "inactive"
            const badge =
              line.status === "available"
                ? content.badges.current
                : line.status === "sibling"
                  ? content.badges.sibling
                  : content.badges.inactive

            const card = (
              <div
                className={`flex h-full flex-col rounded-2xl border p-6 transition-colors duration-300 lg:p-7 ${
                  isCurrent
                    ? "border-primary/40 bg-card shadow-sm shadow-primary/10 hover:border-primary"
                    : isSibling
                      ? "border-primary/20 bg-card hover:border-primary/40"
                      : "border-dashed border-border/70 bg-muted/20"
                }`}
              >
                <div className="mb-5 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                  <div>
                    <p
                      className={`font-serif text-3xl ${
                        isInactive ? "text-muted-foreground" : "text-primary"
                      }`}
                    >
                      {line.code}
                    </p>
                    <p className="mt-1 font-medium text-foreground">{line.name}</p>
                  </div>
                  <span
                    className={`shrink-0 rounded-full border px-3 py-1 text-[11px] uppercase tracking-[0.14em] ${
                      isCurrent
                        ? "border-primary/30 bg-primary/10 text-primary"
                        : isSibling
                          ? "border-primary/20 bg-background text-foreground"
                          : "border-border/70 bg-background text-muted-foreground"
                    }`}
                  >
                    {badge}
                  </span>
                </div>
                <p className="text-sm font-medium text-foreground">{line.source}</p>
                <p className="mt-4 text-sm leading-relaxed text-muted-foreground">
                  {line.description}
                </p>
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
                {isCurrent ? (
                  <Link href={getLocalizedHref(locale, "material")} className="block h-full">
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
