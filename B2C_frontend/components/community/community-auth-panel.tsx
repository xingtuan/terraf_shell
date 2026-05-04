"use client"

import { useState, useTransition } from "react"
import { useRouter } from "next/navigation"

import type { LoginPayload, RegisterPayload } from "@/lib/api/auth"
import { getErrorMessage, getFieldErrors } from "@/lib/api/client"
import type { CommunityUser } from "@/lib/types"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"

export type CommunityAuthCopy = {
  title: string
  description: string
  loginTab: string
  registerTab: string
  loadingSession: string
  currentUser: string
  signedInAs: string
  logout: string
  refreshProfile: string
  email: string
  emailHelper: string
  password: string
  name: string
  confirmPassword: string
  loginSubmit: string
  loginSubmitPending: string
  registerSubmit: string
  registerSubmitPending: string
  guestHint: string
  refreshSuccess: string
  emailPlaceholder: string
  passwordPlaceholder: string
  namePlaceholder: string
  signInToContinue: string
  loadingAccount: string
  verificationNotice?: string
  resendVerification?: string
}

type CommunityAuthPanelProps = {
  copy: CommunityAuthCopy
  user: CommunityUser | null
  isReady: boolean
  isLoadingUser: boolean
  onLogin: (payload: LoginPayload) => Promise<CommunityUser>
  onRegister: (payload: RegisterPayload) => Promise<CommunityUser>
  onLogout: () => Promise<void>
  onRefresh: () => Promise<CommunityUser | null>
  redirectAfterLogin?: string
  context?: "community" | "store"
  onSuccess?: () => void
}

function FieldError({ message }: { message?: string }) {
  if (!message) return null
  return <p className="mt-1 text-xs text-destructive">{message}</p>
}

export function CommunityAuthPanel({
  copy,
  user,
  isReady,
  isLoadingUser,
  onLogin,
  onRegister,
  onLogout,
  onRefresh,
  redirectAfterLogin,
  context = "community",
  onSuccess,
}: CommunityAuthPanelProps) {
  const router = useRouter()
  const [mode, setMode] = useState<"login" | "register">("login")
  const [message, setMessage] = useState<string | null>(null)
  const [fieldErrors, setFieldErrors] = useState<Record<string, string> | null>(null)
  const [showVerificationBanner, setShowVerificationBanner] = useState(false)
  const [isPending, startTransition] = useTransition()
  const headline = context === "store" ? copy.signInToContinue : copy.title

  function clearErrors() {
    setMessage(null)
    setFieldErrors(null)
  }

  if (!isReady) {
    return (
      <div className="rounded-3xl border border-border/60 bg-card p-7">
        <p className="text-sm text-muted-foreground">{copy.loadingSession}</p>
      </div>
    )
  }

  if (user) {
    return (
      <div className="rounded-3xl border border-border/60 bg-card p-7">
        <p className="text-sm uppercase tracking-[0.18em] text-primary">
          {copy.currentUser}
        </p>
        <h3 className="mt-4 font-serif text-2xl text-foreground">
          {user.name}
        </h3>
        <p className="mt-1 text-sm text-muted-foreground">
          {copy.signedInAs}
        </p>
        <div className="mt-6 space-y-2 text-sm text-muted-foreground">
          {user.email ? <p>{user.email}</p> : null}
          {user.profile?.location ? <p>{user.profile.location}</p> : null}
          <p>{copy.description}</p>
        </div>

        {showVerificationBanner && copy.verificationNotice ? (
          <div className="mt-5 rounded-2xl bg-amber-50 px-4 py-3 text-sm text-amber-900 dark:bg-amber-900/20 dark:text-amber-200">
            {copy.verificationNotice}
          </div>
        ) : null}

        {message ? (
          <div className="mt-5 rounded-2xl bg-primary/8 px-4 py-3 text-sm text-foreground">
            {message}
          </div>
        ) : null}

        <div className="mt-6 flex flex-wrap gap-3">
          <Button
            type="button"
            variant="outline"
            disabled={isPending || isLoadingUser}
            onClick={() => {
              clearErrors()
              startTransition(() => {
                void onRefresh()
                  .then(() => {
                    setMessage(copy.refreshSuccess)
                  })
                  .catch((error) => {
                    setMessage(getErrorMessage(error))
                  })
              })
            }}
          >
            {copy.refreshProfile}
          </Button>
          <Button
            type="button"
            variant="ghost"
            disabled={isPending}
            onClick={() => {
              clearErrors()
              startTransition(() => {
                void onLogout().catch((error) => {
                  setMessage(getErrorMessage(error))
                })
              })
            }}
          >
            {copy.logout}
          </Button>
        </div>
      </div>
    )
  }

  return (
    <div className="rounded-3xl border border-border/60 bg-card p-7">
      <div className="flex items-center justify-between gap-4">
        <div>
          <p className="text-sm uppercase tracking-[0.18em] text-primary">
            {headline}
          </p>
          <p className="mt-3 text-sm leading-relaxed text-muted-foreground">
            {copy.description}
          </p>
        </div>
        <div className="flex rounded-full border border-border/60 p-1">
          <button
            type="button"
            className={`rounded-full px-4 py-2 text-sm transition-colors ${
              mode === "login"
                ? "bg-foreground text-background"
                : "text-muted-foreground"
            }`}
            onClick={() => {
              setMode("login")
              clearErrors()
            }}
          >
            {copy.loginTab}
          </button>
          <button
            type="button"
            className={`rounded-full px-4 py-2 text-sm transition-colors ${
              mode === "register"
                ? "bg-foreground text-background"
                : "text-muted-foreground"
            }`}
            onClick={() => {
              setMode("register")
              clearErrors()
            }}
          >
            {copy.registerTab}
          </button>
        </div>
      </div>

      {/* Top-level (non-field) error message */}
      {message ? (
        <div className="mt-5 rounded-2xl bg-destructive/10 px-4 py-3 text-sm text-destructive">
          {message}
        </div>
      ) : null}

      {mode === "login" ? (
        <form
          className="mt-6 space-y-4"
          onSubmit={(event) => {
            event.preventDefault()
            clearErrors()

            const formData = new FormData(event.currentTarget)
            const payload: LoginPayload = {
              email: String(formData.get("email") ?? "").trim(),
              password: String(formData.get("password") ?? ""),
              device_name: "oxp-web",
            }

            startTransition(() => {
              void onLogin(payload)
                .then(() => {
                  onSuccess?.()

                  if (redirectAfterLogin) {
                    router.push(redirectAfterLogin)
                  }
                })
                .catch((error) => {
                  const fields = getFieldErrors(error)
                  if (fields) {
                    setFieldErrors(fields)
                  } else {
                    setMessage(getErrorMessage(error))
                  }
                })
            })
          }}
        >
          <div className="space-y-1">
            <label className="space-y-2">
              <span className="text-sm text-foreground">{copy.email}</span>
              <Input
                name="email"
                type="email"
                placeholder={copy.emailPlaceholder}
                aria-invalid={!!fieldErrors?.email}
                required
              />
            </label>
            <FieldError message={fieldErrors?.email} />
          </div>
          <div className="space-y-1">
            <label className="space-y-2">
              <span className="text-sm text-foreground">{copy.password}</span>
              <Input
                name="password"
                type="password"
                placeholder={copy.passwordPlaceholder}
                aria-invalid={!!fieldErrors?.password}
                required
              />
            </label>
            <FieldError message={fieldErrors?.password} />
          </div>
          <div className="flex items-center justify-between gap-4 pt-2">
            <p className="text-sm text-muted-foreground">{copy.guestHint}</p>
            <Button type="submit" disabled={isPending}>
              {isPending ? copy.loginSubmitPending : copy.loginSubmit}
            </Button>
          </div>
        </form>
      ) : (
        <form
          className="mt-6 space-y-4"
          onSubmit={(event) => {
            event.preventDefault()
            clearErrors()

            const formData = new FormData(event.currentTarget)
            const payload: RegisterPayload = {
              name: String(formData.get("name") ?? "").trim(),
              email: String(formData.get("email") ?? "").trim(),
              password: String(formData.get("password") ?? ""),
              password_confirmation: String(
                formData.get("password_confirmation") ?? "",
              ),
              device_name: "oxp-web",
            }

            startTransition(() => {
              void onRegister(payload)
                .then((newUser) => {
                  if (newUser.email_verified === false) {
                    setShowVerificationBanner(true)
                  }

                  onSuccess?.()

                  if (redirectAfterLogin) {
                    router.push(redirectAfterLogin)
                  }
                })
                .catch((error) => {
                  const fields = getFieldErrors(error)
                  if (fields) {
                    setFieldErrors(fields)
                  } else {
                    setMessage(getErrorMessage(error))
                  }
                })
            })
          }}
        >
          <div className="space-y-1">
            <label className="space-y-2">
              <span className="text-sm text-foreground">{copy.name}</span>
              <Input
                name="name"
                placeholder={copy.namePlaceholder}
                aria-invalid={!!fieldErrors?.name}
                required
              />
            </label>
            <FieldError message={fieldErrors?.name} />
          </div>
          <div className="space-y-1">
            <label className="space-y-2">
              <span className="text-sm text-foreground">{copy.email}</span>
              <Input
                name="email"
                type="email"
                placeholder={copy.emailPlaceholder}
                aria-invalid={!!fieldErrors?.email}
                required
              />
            </label>
            <FieldError message={fieldErrors?.email} />
            <p className="text-xs text-muted-foreground">{copy.emailHelper}</p>
          </div>
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div className="space-y-1">
              <label className="space-y-2">
                <span className="text-sm text-foreground">{copy.password}</span>
                <Input
                  name="password"
                  type="password"
                  placeholder={copy.passwordPlaceholder}
                  aria-invalid={!!fieldErrors?.password}
                  required
                />
              </label>
              <FieldError message={fieldErrors?.password} />
            </div>
            <div className="space-y-1">
              <label className="space-y-2">
                <span className="text-sm text-foreground">
                  {copy.confirmPassword}
                </span>
                <Input
                  name="password_confirmation"
                  type="password"
                  placeholder={copy.passwordPlaceholder}
                  aria-invalid={!!fieldErrors?.password_confirmation}
                  required
                />
              </label>
              <FieldError message={fieldErrors?.password_confirmation} />
            </div>
          </div>
          <div className="flex items-center justify-between gap-4 pt-2">
            <p className="text-sm text-muted-foreground">{copy.guestHint}</p>
            <Button type="submit" disabled={isPending}>
              {isPending ? copy.registerSubmitPending : copy.registerSubmit}
            </Button>
          </div>
        </form>
      )}
    </div>
  )
}
