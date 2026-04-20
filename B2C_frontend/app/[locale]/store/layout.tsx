import type { ReactNode } from "react"

import { CartSidebar } from "@/components/store/CartSidebar"
import { StoreNav } from "@/components/store/StoreNav"
import { resolveLocale } from "@/lib/resolve-locale"

type StoreLayoutProps = {
  children: ReactNode
  params: Promise<{ locale: string }>
}

export default async function StoreLayout({
  children,
  params,
}: StoreLayoutProps) {
  const locale = await resolveLocale(params)

  return (
    <>
      <StoreNav locale={locale} />
      <CartSidebar locale={locale} />
      {children}
    </>
  )
}
