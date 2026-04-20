"use client"

import type { ReactNode } from "react"

import { AuthSessionProvider } from "@/hooks/use-auth-session"
import { CartProvider } from "@/hooks/useCart"

type AppProvidersProps = {
  children: ReactNode
}

export function AppProviders({ children }: AppProvidersProps) {
  return (
    <AuthSessionProvider>
      <CartProvider>{children}</CartProvider>
    </AuthSessionProvider>
  )
}
