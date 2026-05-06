import {
  BadgeCheck,
  CalendarDays,
  Download,
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
  emptyMessage?: string
  statusLabels?: Record<string, string>
  issuerLabel?: string
  testedAtLabel?: string
  downloadLabel?: string
}

type NormalizedCertificationCard = {
  id: string
  key?: string
  title: string
  result?: string
  unit?: string
  status: string
  verified?: boolean | null
  description?: string
  issuer?: string
  testedAt?: string
  documentUrl?: string
}

const certificationIcons: Record<string, LucideIcon> = {
  absorption: Droplets,
  water_absorption: Droplets,
  toxicity: ShieldCheck,
  acid: FlaskConical,
  fire: Flame,
  thermal_process: Flame,
  otr: Wind,
  compressive_stability: ShieldCheck,
  surface_safety: FlaskConical,
  material_origin: BadgeCheck,
  demo_disclaimer: BadgeCheck,
}

function cleanText(value: unknown): string | undefined {
  if (typeof value === "number" && Number.isFinite(value)) {
    return String(value)
  }

  return typeof value === "string" && value.trim().length > 0
    ? value.trim()
    : undefined
}

function normalizeCertification(
  certification: CertificationCardInput | null | undefined,
  index: number,
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
          status: "pending",
        }
      : null
  }

  if (typeof certification !== "object") {
    return null
  }

  const key = cleanText(certification.key)
  const label = cleanText(certification.label)
  const name = cleanText(certification.name)
  const value = cleanText(certification.value)
  const result = cleanText(certification.result)
  const unit = cleanText(certification.unit)
  const status = cleanText(certification.status)?.toLowerCase() ?? "pending"
  const verified =
    typeof certification.verified === "boolean" ? certification.verified : null
  const description = cleanText(certification.description)
  const issuer = cleanText(certification.issuer)
  const testedAt = cleanText(certification.tested_at)
  const documentUrl = cleanText(certification.document_url)
  const title = name ?? label ?? key ?? value ?? result

  return title
    ? {
        id: `certification-object-${index}-${key ?? label ?? value}`,
        key,
        title,
        result: result ?? (value && value !== title ? value : undefined),
        unit,
        status,
        verified,
        description,
        issuer,
        testedAt,
        documentUrl,
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
  emptyMessage = "Certification data is being prepared. Please contact us for the latest material information.",
  statusLabels = {},
  issuerLabel = "Issuer / lab",
  testedAtLabel = "Test date",
  downloadLabel = "Download document",
}: CertificationsAtAGlanceProps) {
  const cards = (certifications ?? [])
    .map((certification, index) =>
      normalizeCertification(certification, index),
    )
    .filter((card): card is NormalizedCertificationCard => Boolean(card))

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

        {cards.length === 0 ? (
          <div className="rounded-3xl border border-dashed border-border/70 bg-background/80 p-8">
            <p className="max-w-3xl text-base leading-relaxed text-muted-foreground">
              {emptyMessage}
            </p>
          </div>
        ) : (
          <div
            className={cn(
              "grid grid-cols-1 gap-4 md:grid-cols-2 lg:gap-5",
              cards.length === 5 ? "lg:grid-cols-5" : "lg:grid-cols-3",
            )}
          >
            {cards.map((card) => {
              const Icon = getCertificationIcon(card.key)
              const statusKey =
                card.status === "demo" || card.verified === false
                  ? "demo"
                  : card.status
              const statusLabel =
                card.verified === true
                  ? verifiedLabel
                  : statusLabels[statusKey] ??
                    statusLabels[statusKey.replace(/-/g, "_")] ??
                    statusKey
              const result = [card.result, card.unit].filter(Boolean).join(" ")

              return (
                <article
                  key={card.id}
                  className="group flex min-h-56 flex-col rounded-2xl border border-border/60 bg-background/80 p-5 shadow-sm shadow-foreground/[0.02] transition-colors hover:border-primary/35 md:p-6"
                >
                  <div className="mb-5 flex items-center justify-between gap-4">
                    <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary transition-colors group-hover:bg-primary/15">
                      <Icon className="h-5 w-5" />
                    </div>
                    <span className="rounded-full border border-border/60 bg-card px-3 py-1 text-[11px] uppercase tracking-[0.16em] text-muted-foreground">
                      {statusLabel}
                    </span>
                  </div>
                  <h3 className="text-sm font-medium leading-snug text-foreground [overflow-wrap:anywhere]">
                    {card.title}
                  </h3>
                  {result ? (
                    <p className="mt-4 font-serif text-2xl leading-tight text-primary [overflow-wrap:anywhere]">
                      {result}
                    </p>
                  ) : null}
                  {card.description ? (
                    <p className="mt-4 text-sm leading-relaxed text-muted-foreground [overflow-wrap:anywhere]">
                      {card.description}
                    </p>
                  ) : null}
                  <div className="mt-auto space-y-2 pt-5 text-xs text-muted-foreground">
                    {card.issuer ? (
                      <p>
                        {issuerLabel}: {card.issuer}
                      </p>
                    ) : null}
                    {card.testedAt ? (
                      <p className="flex items-center gap-2">
                        <CalendarDays className="h-3.5 w-3.5" />
                        {testedAtLabel}: {card.testedAt}
                      </p>
                    ) : null}
                    {card.documentUrl ? (
                      <a
                        href={card.documentUrl}
                        className="inline-flex items-center gap-2 text-primary"
                      >
                        <Download className="h-3.5 w-3.5" />
                        {downloadLabel}
                      </a>
                    ) : null}
                  </div>
                </article>
              )
            })}
          </div>
        )}
      </div>
    </section>
  )
}
