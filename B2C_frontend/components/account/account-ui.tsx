import type { ReactNode } from "react"

import { cn } from "@/lib/utils"

type AccountPageHeaderProps = {
  eyebrow: string
  title: string
  description?: string
  actions?: ReactNode
}

export function AccountPageHeader({
  eyebrow,
  title,
  description,
  actions,
}: AccountPageHeaderProps) {
  return (
    <div className="flex flex-col gap-5 border-b border-border/60 pb-6 lg:flex-row lg:items-end lg:justify-between">
      <div className="max-w-3xl">
        <p className="text-sm uppercase tracking-[0.2em] text-primary">{eyebrow}</p>
        <h1 className="mt-3 font-serif text-4xl text-foreground">{title}</h1>
        {description ? (
          <p className="mt-4 text-sm leading-relaxed text-muted-foreground">
            {description}
          </p>
        ) : null}
      </div>

      {actions ? <div className="flex flex-wrap gap-3">{actions}</div> : null}
    </div>
  )
}

export function AccountPanel({
  className,
  ...props
}: React.ComponentProps<"section">) {
  return (
    <section
      className={cn(
        "rounded-[2rem] border border-border/60 bg-card p-6 shadow-sm sm:p-8",
        className,
      )}
      {...props}
    />
  )
}

type AccountStatCardProps = {
  label: string
  value: ReactNode
  detail?: ReactNode
  className?: string
}

export function AccountStatCard({
  label,
  value,
  detail,
  className,
}: AccountStatCardProps) {
  return (
    <div
      className={cn(
        "rounded-[1.5rem] border border-border/50 bg-background/80 p-5",
        className,
      )}
    >
      <p className="text-sm text-muted-foreground">{label}</p>
      <div className="mt-3 text-3xl text-foreground">{value}</div>
      {detail ? (
        <p className="mt-2 text-sm leading-relaxed text-muted-foreground">{detail}</p>
      ) : null}
    </div>
  )
}

type AccountEmptyStateProps = {
  title: string
  description: string
  action?: ReactNode
  className?: string
}

export function AccountEmptyState({
  title,
  description,
  action,
  className,
}: AccountEmptyStateProps) {
  return (
    <div
      className={cn(
        "rounded-[1.5rem] border border-dashed border-border/60 bg-background/60 p-6 text-center",
        className,
      )}
    >
      <h2 className="font-serif text-2xl text-foreground">{title}</h2>
      <p className="mt-3 text-sm leading-relaxed text-muted-foreground">
        {description}
      </p>
      {action ? <div className="mt-5 flex justify-center">{action}</div> : null}
    </div>
  )
}
