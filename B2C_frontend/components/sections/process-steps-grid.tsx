import type { ProcessStep } from "@/lib/types/content"

type ProcessStepsGridProps = {
  title?: string | null
  subtitle?: string | null
  steps: ProcessStep[]
}

export function ProcessStepsGrid({
  title,
  subtitle,
  steps,
}: ProcessStepsGridProps) {
  if (!title && !subtitle && steps.length === 0) {
    return null
  }

  return (
    <section className="bg-background py-20 lg:py-24">
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

        <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
          {steps.map((step) => (
            <article
              key={step.id}
              className="rounded-3xl border border-border/60 bg-card p-7"
            >
              <p className="text-sm uppercase tracking-[0.2em] text-primary">
                {String(step.step_number).padStart(2, "0")}
              </p>
              <h3 className="mt-4 font-serif text-2xl text-foreground">
                {step.title}
              </h3>
              <p className="mt-4 leading-relaxed text-muted-foreground">
                {step.body}
              </p>
            </article>
          ))}
        </div>
      </div>
    </section>
  )
}
