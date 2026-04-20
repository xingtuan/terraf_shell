type ContentPointsSectionProps = {
  title?: string | null
  body?: string | null
  points: string[]
}

export function ContentPointsSection({
  title,
  body,
  points,
}: ContentPointsSectionProps) {
  if (!title && !body && points.length === 0) {
    return null
  }

  return (
    <section className="bg-card py-20 lg:py-24">
      <div className="mx-auto max-w-7xl px-6 lg:px-8">
        <div className="grid gap-10 lg:grid-cols-[0.95fr_1.05fr] lg:gap-16">
          <div className="max-w-3xl">
            {title ? (
              <h2 className="font-serif text-3xl leading-tight text-foreground md:text-4xl lg:text-5xl">
                {title}
              </h2>
            ) : null}
            {body ? (
              <p className="mt-6 text-lg leading-relaxed text-muted-foreground">
                {body}
              </p>
            ) : null}
          </div>
          {points.length > 0 ? (
            <ul className="space-y-4">
              {points.map((point) => (
                <li
                  key={point}
                  className="rounded-2xl border border-border/60 bg-background px-5 py-4 text-base text-foreground"
                >
                  {point}
                </li>
              ))}
            </ul>
          ) : null}
        </div>
      </div>
    </section>
  )
}
