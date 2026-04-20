import type { Certification } from "@/lib/types/content"

type CertificationsGridProps = {
  title?: string | null
  subtitle?: string | null
  certifications: Certification[]
}

export function CertificationsGrid({
  title,
  subtitle,
  certifications,
}: CertificationsGridProps) {
  if (!title && !subtitle && certifications.length === 0) {
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
          {certifications.map((certification) => (
            <article
              key={certification.id}
              className="rounded-3xl border border-border/60 bg-background p-7"
              style={
                certification.badge_color
                  ? { borderTopColor: certification.badge_color, borderTopWidth: 4 }
                  : undefined
              }
            >
              <p className="text-sm uppercase tracking-[0.16em] text-muted-foreground">
                {certification.label}
              </p>
              <p className="mt-4 font-serif text-2xl text-foreground">
                {certification.value}
              </p>
              {certification.description ? (
                <p className="mt-4 text-sm leading-relaxed text-muted-foreground">
                  {certification.description}
                </p>
              ) : null}
            </article>
          ))}
        </div>
      </div>
    </section>
  )
}
