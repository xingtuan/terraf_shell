import type { ReactNode } from "react"

import { AccountShell } from "@/components/account/account-shell"
import { resolveLocale } from "@/lib/resolve-locale"

type AccountLayoutProps = {
  children: ReactNode
  params: Promise<{ locale: string }>
}

export default async function AccountLayout({
  children,
  params,
}: AccountLayoutProps) {
  const locale = await resolveLocale(params)

  return <AccountShell locale={locale}>{children}</AccountShell>
}
