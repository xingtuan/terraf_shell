import type { ProductSpecification } from "@/lib/types"

type ProductSpecificationGridProps = {
  specifications: ProductSpecification[]
}

export function ProductSpecificationGrid({
  specifications,
}: ProductSpecificationGridProps) {
  const specificationGroups = specifications.reduce<
    Array<{ title: string | null; items: ProductSpecification[] }>
  >((groups, specification) => {
    const normalizedGroup = specification.group?.trim() || null
    const existingGroup = groups.find((group) => group.title === normalizedGroup)

    if (existingGroup) {
      existingGroup.items.push(specification)
      return groups
    }

    groups.push({
      title: normalizedGroup,
      items: [specification],
    })

    return groups
  }, [])

  if (specificationGroups.length === 0) {
    return null
  }

  return (
    <div className="space-y-6">
      {specificationGroups.map((group) => (
        <div key={group.title ?? 'general'} className="space-y-3">
          {group.title ? (
            <p className="text-xs uppercase tracking-[0.18em] text-muted-foreground">
              {group.title}
            </p>
          ) : null}
          <div className="grid gap-4 sm:grid-cols-2">
            {group.items.map((specification) => (
              <article
                key={`${specification.key}-${specification.label}`}
                className="rounded-3xl border border-border/60 bg-background p-5"
              >
                <p className="text-xs uppercase tracking-[0.18em] text-muted-foreground">
                  {specification.label}
                </p>
                <p className="mt-3 text-lg font-medium text-foreground">
                  {specification.value}
                  {specification.unit ? ` ${specification.unit}` : ""}
                </p>
              </article>
            ))}
          </div>
        </div>
      ))}
    </div>
  )
}
