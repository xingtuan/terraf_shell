import {
  BadgeCheck,
  Droplets,
  Feather,
  Hand,
  Leaf,
  Shield,
  Wind,
} from "lucide-react"

import type { MaterialProperty } from "@/lib/types/content"

type MaterialPropertiesGridProps = {
  title?: string | null
  subtitle?: string | null
  properties: MaterialProperty[]
}

const propertyIcons = {
  feather: Feather,
  shield: Shield,
  droplets: Droplets,
  leaf: Leaf,
  hand: Hand,
  wind: Wind,
} as const

export function MaterialPropertiesGrid({
  title,
  subtitle,
  properties,
}: MaterialPropertiesGridProps) {
  if (!title && !subtitle && properties.length === 0) {
    return null
  }

  return (
    <section className="bg-card py-20 lg:py-24">
      <div className="mx-auto max-w-7xl px-6 lg:px-8">
        <div className="mb-14 max-w-3xl">
          {subtitle ? (
            <p className="mb-4 text-sm uppercase tracking-[0.2em] text-primary">
              {subtitle}
            </p>
          ) : null}
          {title ? (
            <h2 className="font-serif text-3xl leading-tight text-foreground md:text-4xl lg:text-5xl">
              {title}
            </h2>
          ) : null}
        </div>

        <div className="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-3">
          {properties.map((property) => {
            const Icon = property.icon
              ? propertyIcons[property.icon as keyof typeof propertyIcons] ?? BadgeCheck
              : BadgeCheck

            return (
              <article
                key={property.id}
                className="rounded-3xl border border-border/60 bg-background p-7"
              >
                <div className="mb-5 flex items-center gap-3">
                  <div className="flex h-11 w-11 items-center justify-center rounded-full bg-primary/10">
                    <Icon className="h-5 w-5 text-primary" />
                  </div>
                  <span className="text-sm uppercase tracking-[0.16em] text-muted-foreground">
                    {property.label}
                  </span>
                </div>
                <p className="font-serif text-2xl text-foreground">
                  {property.value}
                </p>
                <p className="mt-3 text-sm leading-relaxed text-muted-foreground">
                  {property.comparison}
                </p>
              </article>
            )
          })}
        </div>
      </div>
    </section>
  )
}
