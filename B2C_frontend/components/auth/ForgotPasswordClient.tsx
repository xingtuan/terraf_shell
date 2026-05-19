"use client"

import { useState, useTransition } from "react"
import Link from "next/link"
import { ArrowLeft, Mail } from "lucide-react"

import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { forgotPassword, type ForgotPasswordPayload } from "@/lib/api/auth"
import { getErrorMessage, getFieldErrors } from "@/lib/api/client"
import { getLocalizedHref, type Locale, type SiteMessages } from "@/lib/i18n"

type ForgotPasswordCopy = SiteMessages["community"]["auth"]["forgotPassword"]

type ForgotPasswordClientProps = {
  locale: Locale
  copy: ForgotPasswordCopy
}

const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

function FieldError({ message }: { message?: string }) {
  if (!message) return null

  return <p className="mt-1 text-xs text-destructive">{message}</p>
}

export function ForgotPasswordClient({
  locale,
  copy,
}: ForgotPasswordClientProps) {
  const [isPending, startTransition] = useTransition()
  const [isSubmitted, setIsSubmitted] = useState(false)
  const [message, setMessage] = useState<string | null>(null)
  const [fieldErrors, setFieldErrors] = useState<Record<string, string> | null>(
    null,
  )

  function clearErrors() {
    setMessage(null)
    setFieldErrors(null)
  }

  if (isSubmitted) {
    return (
      <div className="rounded-lg border border-border/60 bg-card p-7">
        <div className="flex size-11 items-center justify-center rounded-md bg-primary/10 text-primary">
          <Mail className="size-5" aria-hidden="true" />
        </div>
        <h2 className="mt-5 font-serif text-3xl text-foreground">
          {copy.successTitle}
        </h2>
        <p className="mt-3 text-sm leading-relaxed text-muted-foreground">
          {copy.successDescription}
        </p>
        <div className="mt-7 flex flex-wrap gap-3">
          <Button asChild>
            <Link href={getLocalizedHref(locale, "auth/login")}>
              {copy.backToLogin}
            </Link>
          </Button>
          <Button asChild variant="outline">
            <Link href={getLocalizedHref(locale)}>
              <ArrowLeft className="size-4" aria-hidden="true" />
              {copy.backHome}
            </Link>
          </Button>
        </div>
      </div>
    )
  }

  return (
    <div className="rounded-lg border border-border/60 bg-card p-7">
      {message ? (
        <div className="mb-5 rounded-md bg-destructive/10 px-4 py-3 text-sm text-destructive">
          {message}
        </div>
      ) : null}

      <form
        className="space-y-5"
        onSubmit={(event) => {
          event.preventDefault()
          clearErrors()

          const formData = new FormData(event.currentTarget)
          const payload: ForgotPasswordPayload = {
            email: String(formData.get("email") ?? "").trim(),
          }

          if (!payload.email) {
            setFieldErrors({ email: copy.validation.emailRequired })
            return
          }

          if (!emailPattern.test(payload.email)) {
            setFieldErrors({ email: copy.validation.emailInvalid })
            return
          }

          startTransition(() => {
            void forgotPassword(payload)
              .then(() => {
                setIsSubmitted(true)
              })
              .catch((error) => {
                const fields = getFieldErrors(error)

                if (fields) {
                  setFieldErrors(fields)
                  return
                }

                setMessage(getErrorMessage(error))
              })
          })
        }}
      >
        <div className="space-y-1">
          <label className="space-y-2">
            <span className="text-sm text-foreground">{copy.emailLabel}</span>
            <Input
              name="email"
              type="email"
              placeholder={copy.emailPlaceholder}
              autoComplete="email"
              aria-invalid={!!fieldErrors?.email}
              required
            />
          </label>
          <FieldError message={fieldErrors?.email} />
        </div>

        <div className="flex flex-col gap-3 pt-1 sm:flex-row sm:items-center sm:justify-between">
          <Button asChild variant="ghost" className="w-fit px-0">
            <Link href={getLocalizedHref(locale, "auth/login")}>
              <ArrowLeft className="size-4" aria-hidden="true" />
              {copy.rememberPassword}
            </Link>
          </Button>
          <Button type="submit" disabled={isPending}>
            <Mail className="size-4" aria-hidden="true" />
            {isPending ? copy.submitPending : copy.submit}
          </Button>
        </div>
      </form>
    </div>
  )
}
