import {
  BadgeCheck,
  Droplets,
  Flame,
  FlaskConical,
  ShieldCheck,
  Wind,
  type LucideIcon,
} from "lucide-react"

import { cn } from "@/lib/utils"
import type { CertificationCardInput } from "@/lib/types"

type CertificationsAtAGlanceProps = {
  certifications?: Array<CertificationCardInput | null | undefined> | null
  title?: string
  eyebrow?: string
  description?: string
  variant?: "material" | "product"
  className?: string
  verifiedLabel?: string
}

type NormalizedCertificationCard = {
  id: string
  key?: string
  title: string
  value?: string
  description?: string
}

const certificationIcons: Record<string, LucideIcon> = {
  absorption: Droplets,
  toxicity: ShieldCheck,
  acid: FlaskConical,
  fire: Flame,
  otr: Wind,
}

function cleanText(value: unknown): string | undefined {
  return typeof value === "string" && value.trim().length > 0
    ? value.trim()
    : undefined
}

function normalizeCertification(
  certification: CertificationCardInput | null | undefined,
  index: number,
  verifiedLabel: string,
): NormalizedCertificationCard | null {
  if (!certification) {
    return null
  }

  if (typeof certification === "string") {
    const title = cleanText(certification)

    return title
      ? {
          id: `certification-string-${index}-${title}`,
          title,
          value: verifiedLabel,
        }
      : null
  }

  if (typeof certification !== "object") {
    return null
  }

  const key = cleanText(certification.key)
  const label = cleanText(certification.label)
  const value = cleanText(certification.value)
  const description = cleanText(certification.description)
  const title = label ?? key ?? value

  return title
    ? {
        id: `certification-object-${index}-${key ?? label ?? value}`,
        key,
        title,
        value: value && value !== title ? value : undefined,
        description,
      }
    : null
}

function getCertificationIcon(key?: string): LucideIcon {
  return key ? certificationIcons[key.toLowerCase()] ?? BadgeCheck : BadgeCheck
}

export function CertificationsAtAGlance({
  certifications,
  title,
  eyebrow,
  description,
  variant = "material",
  className,
  verifiedLabel = "Verified",
}: CertificationsAtAGlanceProps) {
  const cards = (certifications ?? [])
    .map((certification, index) =>
      normalizeCertification(certification, index, verifiedLabel),
    )
    .filter((card): card is NormalizedCertificationCard => Boolean(card))

  if (cards.length === 0) {
    return null
  }

  const isMaterialVariant = variant === "material"
  const hasHeader = Boolean(eyebrow || title || description)

  return (
    <section
      className={cn(
        isMaterialVariant
          ? "bg-card py-24 lg:py-32"
          : "rounded-[2rem] border border-border/60 bg-card p-8 lg:p-10",
        className,
      )}
    >
      <div className={cn(isMaterialVariant && "mx-auto max-w-7xl px-6 lg:px-8")}>
        {hasHeader ? (
          <div className="mb-10 flex flex-col gap-5 lg:mb-12 lg:flex-row lg:items-end lg:justify-between">
            <div className="max-w-3xl">
              {eyebrow ? (
                <p className="mb-4 text-sm uppercase tracking-[0.2em] text-primary">
                  {eyebrow}
                </p>
              ) : null}
              {title ? (
                <h2 className="font-serif text-3xl leading-tight text-foreground md:text-4xl lg:text-5xl">
                  {title}
                </h2>
              ) : null}
            </div>
            {description ? (
              <p className="max-w-xl text-base leading-relaxed text-muted-foreground">
                {description}
              </p>
            ) : null}
          </div>
        ) : null}

        <div
          className={cn(
            "grid grid-cols-1 gap-4 md:grid-cols-2 lg:gap-5",
            cards.length === 5 ? "lg:grid-cols-5" : "lg:grid-cols-3",
          )}
        >
          {cards.map((card) => {
            const Icon = getCertificationIcon(card.key)

            return (
              <article
                key={card.id}
                className="group flex min-h-48 flex-col rounded-2xl border border-border/60 bg-background/80 p-5 shadow-sm shadow-foreground/[0.02] transition-colors hover:border-primary/35 md:p-6"
              >
                <div className="mb-5 flex items-center justify-between gap-4">
                  <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary transition-colors group-hover:bg-primary/15">
                    <Icon className="h-5 w-5" />
                  </div>
                  <BadgeCheck className="h-5 w-5 shrink-0 text-primary/55" />
                </div>
                <h3 className="text-sm font-medium leading-snug text-foreground [overflow-wrap:anywhere]">
                  {card.title}
                </h3>
                {card.value ? (
                  <p className="mt-4 font-serif text-2xl leading-tight text-primary [overflow-wrap:anywhere]">
                    {card.value}
                  </p>
                ) : null}
                {card.description ? (
                  <p className="mt-4 text-sm leading-relaxed text-muted-foreground [overflow-wrap:anywhere]">
                    {card.description}
                  </p>
                ) : null}
              </article>
            )
          })}
        </div>
      </div>
    </section>
  )
}
