"use client"

import type { ReactNode } from "react"

import { CommunityAuthPanel } from "@/components/community/community-auth-panel"
import { getMessages, type Locale } from "@/lib/i18n"
import { useAuthSession } from "@/hooks/use-auth-session"

type AuthGateProps = {
  children: ReactNode
  locale: Locale
  redirectAfterLogin: string
  context?: "community" | "store"
}

export function AuthGate({
  children,
  locale,
  redirectAfterLogin,
  context = "store",
}: AuthGateProps) {
  const session = useAuthSession()
  const authCopy = getMessages(locale).community.auth

  if (!session.isReady) {
    return (
      <div className="mx-auto max-w-3xl px-6 py-24 lg:px-8">
        <div className="rounded-3xl border border-border/60 bg-card p-8 text-sm text-muted-foreground">
          Loading your Shellfin account...
        </div>
      </div>
    )
  }

  if (!session.user) {
    return (
      <div className="mx-auto max-w-3xl px-6 py-24 lg:px-8">
        <CommunityAuthPanel
          copy={authCopy}
          user={session.user}
          isReady={session.isReady}
          isLoadingUser={session.isLoadingUser}
          context={context}
          redirectAfterLogin={redirectAfterLogin}
          onLogin={session.login}
          onRegister={session.register}
          onLogout={session.logout}
          onRefresh={session.refreshUser}
        />
      </div>
    )
  }

  return <>{children}</>
}
