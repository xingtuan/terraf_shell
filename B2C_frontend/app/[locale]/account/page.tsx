"use client"

import { use, useEffect } from "react"
import { useRouter } from "next/navigation"

import { AuthGate } from "@/components/auth/AuthGate"
import { getLocalizedHref, isValidLocale, type Locale } from "@/lib/i18n"
import { useAuthSession } from "@/hooks/use-auth-session"

type AccountPageProps = {
  params: Promise<{ locale: string }>
}

function AccountRedirectScreen({ locale }: { locale: Locale }) {
  const router = useRouter()
  const session = useAuthSession()

  useEffect(() => {
    if (!session.user?.username) {
      return
    }

    router.replace(getLocalizedHref(locale, `community/u/${session.user.username}`))
  }, [locale, router, session.user?.username])

  return (
    <div className="mx-auto max-w-3xl px-6 py-24 lg:px-8">
      <div className="rounded-3xl border border-border/60 bg-card p-8 text-sm text-muted-foreground">
        Redirecting to your profile...
      </div>
    </div>
  )
}

export default function AccountPage({ params }: AccountPageProps) {
  const resolvedParams = use(params)
  const locale = isValidLocale(resolvedParams.locale) ? resolvedParams.locale : "en"
  const accountHref = getLocalizedHref(locale, "account")

  return (
    <AuthGate locale={locale} redirectAfterLogin={accountHref}>
      <AccountRedirectScreen locale={locale} />
    </AuthGate>
  )
}
