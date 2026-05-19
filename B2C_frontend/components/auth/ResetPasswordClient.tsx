"use client"

import { useState, useTransition } from "react"
import Link from "next/link"
import { ArrowLeft, CheckCircle2, KeyRound } from "lucide-react"

import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { resetPassword, type ResetPasswordPayload } from "@/lib/api/auth"
import { getErrorMessage, getFieldErrors } from "@/lib/api/client"
import { getLocalizedHref, type Locale, type SiteMessages } from "@/lib/i18n"

type ResetPasswordCopy = SiteMessages["community"]["auth"]["resetPassword"]

type ResetPasswordClientProps = {
  locale: Locale
  copy: ResetPasswordCopy
  token: string
  email: string
}

const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/

function FieldError({ message }: { message?: string }) {
  if (!message) return null

  return <p className="mt-1 text-xs text-destructive">{message}</p>
}

function isTokenError(message?: string) {
  return !!message && /token|invalid|expired|reset/i.test(message)
}

export function ResetPasswordClient({
  locale,
  copy,
  token,
  email,
}: ResetPasswordClientProps) {
  const [isPending, startTransition] = useTransition()
  const [isSubmitted, setIsSubmitted] = useState(false)
  const [message, setMessage] = useState<string | null>(null)
  const [fieldErrors, setFieldErrors] = useState<Record<string, string> | null>(
    null,
  )
  const isMissingLinkData = !token || !email

  function clearErrors() {
    setMessage(null)
    setFieldErrors(null)
  }

  if (isMissingLinkData) {
    return (
      <div className="rounded-lg border border-border/60 bg-card p-7">
        <div className="flex size-11 items-center justify-center rounded-md bg-destructive/10 text-destructive">
          <KeyRound className="size-5" aria-hidden="true" />
        </div>
        <h2 className="mt-5 font-serif text-3xl text-foreground">
          {copy.missingTitle}
        </h2>
        <p className="mt-3 text-sm leading-relaxed text-muted-foreground">
          {copy.missingDescription}
        </p>
        <div className="mt-7 flex flex-wrap gap-3">
          <Button asChild>
            <Link href={getLocalizedHref(locale, "auth/forgot-password")}>
              {copy.requestNewLink}
            </Link>
          </Button>
          <Button asChild variant="outline">
            <Link href={getLocalizedHref(locale, "auth/login")}>
              <ArrowLeft className="size-4" aria-hidden="true" />
              {copy.backToLogin}
            </Link>
          </Button>
        </div>
      </div>
    )
  }

  if (isSubmitted) {
    return (
      <div className="rounded-lg border border-border/60 bg-card p-7">
        <div className="flex size-11 items-center justify-center rounded-md bg-primary/10 text-primary">
          <CheckCircle2 className="size-5" aria-hidden="true" />
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
              {copy.signIn}
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
          const payload: ResetPasswordPayload = {
            token,
            email: String(formData.get("email") ?? "").trim(),
            password: String(formData.get("password") ?? ""),
            password_confirmation: String(
              formData.get("password_confirmation") ?? "",
            ),
          }

          const errors: Record<string, string> = {}

          if (!payload.email) {
            errors.email = copy.validation.emailRequired
          } else if (!emailPattern.test(payload.email)) {
            errors.email = copy.validation.emailInvalid
          }

          if (!payload.password) {
            errors.password = copy.validation.passwordRequired
          } else if (payload.password.length < 8) {
            errors.password = copy.validation.passwordMin
          }

          if (payload.password !== payload.password_confirmation) {
            errors.password_confirmation = copy.validation.passwordMismatch
          }

          if (Object.keys(errors).length > 0) {
            setFieldErrors(errors)
            return
          }

          startTransition(() => {
            void resetPassword(payload)
              .then(() => {
                setIsSubmitted(true)
              })
              .catch((error) => {
                const fields = getFieldErrors(error)

                if (isTokenError(fields?.email) || isTokenError(fields?.token)) {
                  setMessage(copy.invalidResetLink)
                  return
                }

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
              defaultValue={email}
              autoComplete="email"
              aria-invalid={!!fieldErrors?.email}
              required
            />
          </label>
          <FieldError message={fieldErrors?.email} />
        </div>

        <div className="grid gap-4 sm:grid-cols-2">
          <div className="space-y-1">
            <label className="space-y-2">
              <span className="text-sm text-foreground">
                {copy.passwordLabel}
              </span>
              <Input
                name="password"
                type="password"
                autoComplete="new-password"
                aria-invalid={!!fieldErrors?.password}
                required
              />
            </label>
            <FieldError message={fieldErrors?.password} />
          </div>
          <div className="space-y-1">
            <label className="space-y-2">
              <span className="text-sm text-foreground">
                {copy.confirmPasswordLabel}
              </span>
              <Input
                name="password_confirmation"
                type="password"
                autoComplete="new-password"
                aria-invalid={!!fieldErrors?.password_confirmation}
                required
              />
            </label>
            <FieldError message={fieldErrors?.password_confirmation} />
          </div>
        </div>

        <div className="flex flex-col gap-3 pt-1 sm:flex-row sm:items-center sm:justify-between">
          <Button asChild variant="ghost" className="w-fit px-0">
            <Link href={getLocalizedHref(locale, "auth/login")}>
              <ArrowLeft className="size-4" aria-hidden="true" />
              {copy.backToLogin}
            </Link>
          </Button>
          <Button type="submit" disabled={isPending}>
            <KeyRound className="size-4" aria-hidden="true" />
            {isPending ? copy.submitPending : copy.submit}
          </Button>
        </div>
      </form>
    </div>
  )
}
