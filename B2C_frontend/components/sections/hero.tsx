"use client"

import Image from "next/image"
import Link from "next/link"
import { ArrowRight, Leaf, Shield, Waves } from "lucide-react"

import { Button } from "@/components/ui/button"
import { getLocalizedHref, type Locale, type SiteMessages } from "@/lib/i18n"

type HeroSectionProps = {
  locale: Locale
  content: SiteMessages["home"]["hero"]
  primaryHref?: string
  secondaryHref?: string
}

const indicatorIcons = [Leaf, Shield, Waves]

export function HeroSection({
  locale,
  content,
  primaryHref = getLocalizedHref(locale, "material"),
  secondaryHref = getLocalizedHref(locale, "b2b"),
}: HeroSectionProps) {
  return (
    <section className="relative flex min-h-screen items-center justify-center overflow-hidden">
      <div className="absolute inset-0 z-0">
        <Image
          src="/images/hero-material.jpg"
          alt={content.title}
          fill
          className="object-cover"
          priority
          quality={90}
        />
        <div className="absolute inset-0 bg-gradient-to-b from-background/25 via-background/12 to-background/85" />
      </div>

      <div className="relative z-10 mx-auto max-w-7xl px-6 py-32 lg:px-8 lg:py-40">
        <div className="max-w-3xl">
          <p className="mb-6 text-sm uppercase tracking-[0.22em] text-primary animate-fade-in opacity-0">
            {content.eyebrow}
          </p>

          <h1 className="mb-8 font-serif text-4xl leading-[1.08] text-foreground animate-fade-in-up opacity-0 animation-delay-100 md:text-5xl lg:text-6xl xl:text-7xl">
            {content.title}
          </h1>

          <p className="mb-10 max-w-2xl text-lg leading-relaxed text-muted-foreground animate-fade-in-up opacity-0 animation-delay-200 md:text-xl">
            {content.description}
          </p>

          <div className="mb-16 flex flex-col gap-4 animate-fade-in-up opacity-0 animation-delay-300 sm:flex-row">
            <Button asChild size="lg" className="px-8 py-6 text-base group">
              <Link href={primaryHref}>
                {content.primaryCta}
                <ArrowRight className="ml-2 h-4 w-4 transition-transform group-hover:translate-x-1" />
              </Link>
            </Button>
            <Button asChild size="lg" variant="outline" className="px-8 py-6 text-base">
              <Link href={secondaryHref}>
                {content.secondaryCta}
              </Link>
            </Button>
          </div>

          <div className="grid grid-cols-1 gap-6 animate-fade-in-up opacity-0 animation-delay-400 sm:grid-cols-3">
            {content.indicators.map((indicator, index) => {
              const Icon = indicatorIcons[index] ?? Leaf

              return (
                <div key={indicator} className="flex items-center gap-3">
                  <div className="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10">
                    <Icon className="h-5 w-5 text-primary" />
                  </div>
                  <span className="text-sm text-muted-foreground">
                    {indicator}
                  </span>
                </div>
              )
            })}
          </div>
        </div>
      </div>

      <div className="absolute bottom-8 left-1/2 z-10 -translate-x-1/2 animate-bounce">
        <div className="flex h-10 w-6 items-start justify-center rounded-full border-2 border-foreground/30 p-2">
          <div className="h-2 w-1 rounded-full bg-foreground/50" />
        </div>
      </div>
    </section>
  )
}
