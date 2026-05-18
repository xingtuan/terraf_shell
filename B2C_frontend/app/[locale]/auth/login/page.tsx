import { Suspense } from "react"

import { FinalCtaSection } from "@/components/sections/final-cta"
import { AuthPageClient } from "@/components/auth/AuthPageClient"
import { getMessages } from "@/lib/i18n"
import { resolveLocale } from "@/lib/resolve-locale"

type AuthLoginPageProps = {
  params: Promise<{ locale: string }>
}

export default async function AuthLoginPage({ params }: AuthLoginPageProps) {
  const locale = await resolveLocale(params)
  const messages = getMessages(locale)
  const authCopy = messages.community.auth

  return (
    <>
      <div className="mx-auto max-w-2xl px-6 py-24 lg:px-8">
        <div className="mb-10 text-center">
          <p className="text-sm uppercase tracking-[0.2em] text-primary">
            {authCopy.loginTab}
          </p>
          <h1 className="mt-3 font-serif text-4xl text-foreground">
            {authCopy.title}
          </h1>
          <p className="mt-4 text-sm leading-relaxed text-muted-foreground">
            {authCopy.description}
          </p>
        </div>
        <Suspense
          fallback={
            <div className="rounded-3xl border border-border/60 bg-card p-8 text-sm text-muted-foreground">
              {authCopy.loadingAccount}
            </div>
          }
        >
          <AuthPageClient locale={locale} defaultMode="login" />
        </Suspense>
      </div>
      <FinalCtaSection locale={locale} content={messages.home.finalCta} />
    </>
  )
}
