import type { SiteMessages } from "@/lib/i18n"

type StoreTrustPanelProps = {
  content: SiteMessages["home"]["credibility"]
}

export function StoreTrustPanel({ content }: StoreTrustPanelProps) {
  return (
    <section className="rounded-[2rem] border border-border/60 bg-card p-8 lg:p-10">
      <div className="max-w-3xl">
        <p className="text-sm uppercase tracking-[0.2em] text-primary">
          {content.eyebrow}
        </p>
        <h3 className="mt-3 font-serif text-3xl text-foreground">
          {content.title}
        </h3>
      </div>

      <div className="mt-8 grid gap-5 lg:grid-cols-[0.9fr_1.1fr]">
        <div className="rounded-3xl border border-border/60 bg-background p-6">
          <div className="mt-5 space-y-4">
            {content.benefits.map((benefit) => (
              <div key={benefit} className="flex gap-3">
                <span className="mt-1 size-2 shrink-0 rounded-full bg-primary" />
                <p className="text-sm leading-relaxed text-foreground">{benefit}</p>
              </div>
            ))}
          </div>
        </div>

        <div className="grid gap-4 sm:grid-cols-2">
          {content.features.map((feature) => (
            <article
              key={feature.title}
              className="rounded-3xl border border-border/60 bg-background p-6"
            >
              <h4 className="font-serif text-xl text-foreground">{feature.title}</h4>
              <p className="mt-3 text-sm leading-relaxed text-muted-foreground">
                {feature.description}
              </p>
            </article>
          ))}
        </div>
      </div>
    </section>
  )
}
