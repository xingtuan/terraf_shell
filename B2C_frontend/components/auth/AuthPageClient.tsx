"use client"

import { useEffect } from "react"
import { useRouter, useSearchParams } from "next/navigation"

import { CommunityAuthPanel } from "@/components/community/community-auth-panel"
import { getLocalizedHref, getMessages, type Locale } from "@/lib/i18n"
import { useAuthSession } from "@/hooks/use-auth-session"

type AuthPageClientProps = {
  locale: Locale
  defaultMode?: "login" | "register"
}

export function AuthPageClient({ locale, defaultMode = "login" }: AuthPageClientProps) {
  const router = useRouter()
  const searchParams = useSearchParams()
  const session = useAuthSession()
  const messages = getMessages(locale)
  const authCopy = messages.community.auth

  const next = searchParams.get("next") || getLocalizedHref(locale, "account")

  useEffect(() => {
    if (session.isReady && session.user) {
      router.replace(next)
    }
  }, [session.isReady, session.user, router, next])

  if (!session.isReady || session.user) {
    return (
      <div className="rounded-3xl border border-border/60 bg-card p-8 text-sm text-muted-foreground">
        {authCopy.loadingAccount}
      </div>
    )
  }

  return (
    <CommunityAuthPanel
      copy={authCopy}
      user={null}
      isReady={session.isReady}
      isLoadingUser={session.isLoadingUser}
      context="store"
      defaultMode={defaultMode}
      redirectAfterLogin={next}
      onLogin={session.login}
      onRegister={session.register}
      onLogout={session.logout}
      onRefresh={session.refreshUser}
      forgotPasswordHref={getLocalizedHref(locale, "auth/forgot-password")}
    />
  )
}
