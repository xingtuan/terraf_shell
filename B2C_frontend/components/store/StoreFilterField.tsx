import type { ReactNode } from "react"

type StoreFilterFieldProps = {
  label: string
  children: ReactNode
}

export function StoreFilterField({
  label,
  children,
}: StoreFilterFieldProps) {
  return (
    <label className="block space-y-2">
      <span className="text-sm text-foreground">{label}</span>
      {children}
    </label>
  )
}
