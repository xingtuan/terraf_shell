import Link from "next/link"
import { AlertCircle, CheckCircle2, MailWarning } from "lucide-react"

import { Button } from "@/components/ui/button"
import { getLocalizedHref, getMessages } from "@/lib/i18n"
import { resolveLocale } from "@/lib/resolve-locale"

type EmailVerificationPageProps = {
  params: Promise<{ locale: string }>
  searchParams?: Promise<{
    status?: string
  }>
}

function resolveStatus(status?: string) {
  if (status === "verified" || status === "expired" || status === "invalid") {
    return status
  }

  return "invalid"
}

export default async function EmailVerificationPage({
  params,
  searchParams,
}: EmailVerificationPageProps) {
  const locale = await resolveLocale(params)
  const resolvedSearchParams = await searchParams
  const messages = getMessages(locale)
  const copy = messages.community.auth.emailVerification
  const status = resolveStatus(resolvedSearchParams?.status)
  const isVerified = status === "verified"
  const isExpired = status === "expired"
  const Icon = isVerified ? CheckCircle2 : isExpired ? MailWarning : AlertCircle
  const title = isVerified
    ? copy.verifiedTitle
    : isExpired
      ? copy.expiredTitle
      : copy.invalidTitle
  const description = isVerified
    ? copy.verifiedDescription
    : isExpired
      ? copy.expiredDescription
      : copy.invalidDescription

  return (
    <div className="mx-auto max-w-2xl px-6 py-24 lg:px-8">
      <div className="rounded-lg border border-border/60 bg-card p-8">
        <div
          className={`flex size-12 items-center justify-center rounded-md ${
            isVerified
              ? "bg-primary/10 text-primary"
              : "bg-destructive/10 text-destructive"
          }`}
        >
          <Icon className="size-6" aria-hidden="true" />
        </div>
        <p className="mt-6 text-sm uppercase tracking-[0.2em] text-primary">
          {copy.eyebrow}
        </p>
        <h1 className="mt-3 font-serif text-4xl text-foreground">
          {title}
        </h1>
        <p className="mt-4 text-sm leading-relaxed text-muted-foreground">
          {description}
        </p>
        <div className="mt-8 flex flex-wrap gap-3">
          <Button asChild>
            <Link href={getLocalizedHref(locale, "auth/login")}>
              {isVerified ? copy.signIn : copy.signInToRequest}
            </Link>
          </Button>
          <Button asChild variant="outline">
            <Link href={getLocalizedHref(locale, "contact")}>
              {copy.contactSupport}
            </Link>
          </Button>
        </div>
      </div>
    </div>
  )
}
