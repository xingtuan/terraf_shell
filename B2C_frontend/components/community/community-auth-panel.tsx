"use client"

import { useState, useTransition } from "react"
import { useRouter } from "next/navigation"

import type { LoginPayload, RegisterPayload } from "@/lib/api/auth"
import { getErrorMessage } from "@/lib/api/client"
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
  password: string
  name: string
  username: string
  confirmPassword: string
  loginSubmit: string
  registerSubmit: string
  guestHint: string
  refreshSuccess: string
  emailPlaceholder: string
  passwordPlaceholder: string
  namePlaceholder: string
  usernamePlaceholder: string
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
  const [isPending, startTransition] = useTransition()
  const headline = context === "store" ? "Sign in to continue" : copy.title

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
          {copy.signedInAs} @{user.username}
        </p>
        <div className="mt-6 space-y-2 text-sm text-muted-foreground">
          {user.email ? <p>{user.email}</p> : null}
          {user.profile?.location ? <p>{user.profile.location}</p> : null}
          <p>{copy.description}</p>
        </div>

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
              setMessage(null)
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
              setMessage(null)
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
            onClick={() => setMode("login")}
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
            onClick={() => setMode("register")}
          >
            {copy.registerTab}
          </button>
        </div>
      </div>

      {message ? (
        <div className="mt-5 rounded-2xl bg-primary/8 px-4 py-3 text-sm text-foreground">
          {message}
        </div>
      ) : null}

      {mode === "login" ? (
        <form
          className="mt-6 space-y-4"
          onSubmit={(event) => {
            event.preventDefault()
            setMessage(null)

            const formData = new FormData(event.currentTarget)
            const payload: LoginPayload = {
              email: String(formData.get("email") ?? "").trim(),
              password: String(formData.get("password") ?? ""),
              device_name: "shellfin-web",
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
                  setMessage(getErrorMessage(error))
                })
            })
          }}
        >
          <label className="space-y-2">
            <span className="text-sm text-foreground">{copy.email}</span>
            <Input
              name="email"
              type="email"
              placeholder={copy.emailPlaceholder}
              required
            />
          </label>
          <label className="space-y-2">
            <span className="text-sm text-foreground">{copy.password}</span>
            <Input
              name="password"
              type="password"
              placeholder={copy.passwordPlaceholder}
              required
            />
          </label>
          <div className="flex items-center justify-between gap-4 pt-2">
            <p className="text-sm text-muted-foreground">{copy.guestHint}</p>
            <Button type="submit" disabled={isPending}>
              {isPending ? `${copy.loginSubmit}...` : copy.loginSubmit}
            </Button>
          </div>
        </form>
      ) : (
        <form
          className="mt-6 space-y-4"
          onSubmit={(event) => {
            event.preventDefault()
            setMessage(null)

            const formData = new FormData(event.currentTarget)
            const payload: RegisterPayload = {
              name: String(formData.get("name") ?? "").trim(),
              username: String(formData.get("username") ?? "").trim(),
              email: String(formData.get("email") ?? "").trim(),
              password: String(formData.get("password") ?? ""),
              password_confirmation: String(
                formData.get("password_confirmation") ?? "",
              ),
              device_name: "shellfin-web",
            }

            startTransition(() => {
              void onRegister(payload)
                .then(() => {
                  onSuccess?.()

                  if (redirectAfterLogin) {
                    router.push(redirectAfterLogin)
                  }
                })
                .catch((error) => {
                  setMessage(getErrorMessage(error))
                })
            })
          }}
        >
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <label className="space-y-2">
              <span className="text-sm text-foreground">{copy.name}</span>
              <Input name="name" placeholder={copy.namePlaceholder} required />
            </label>
            <label className="space-y-2">
              <span className="text-sm text-foreground">{copy.username}</span>
              <Input
                name="username"
                placeholder={copy.usernamePlaceholder}
                required
              />
            </label>
          </div>
          <label className="space-y-2">
            <span className="text-sm text-foreground">{copy.email}</span>
            <Input
              name="email"
              type="email"
              placeholder={copy.emailPlaceholder}
              required
            />
          </label>
          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <label className="space-y-2">
              <span className="text-sm text-foreground">{copy.password}</span>
              <Input
                name="password"
                type="password"
                placeholder={copy.passwordPlaceholder}
                required
              />
            </label>
            <label className="space-y-2">
              <span className="text-sm text-foreground">
                {copy.confirmPassword}
              </span>
              <Input
                name="password_confirmation"
                type="password"
                placeholder={copy.passwordPlaceholder}
                required
              />
            </label>
          </div>
          <div className="flex items-center justify-between gap-4 pt-2">
            <p className="text-sm text-muted-foreground">{copy.guestHint}</p>
            <Button type="submit" disabled={isPending}>
              {isPending ? `${copy.registerSubmit}...` : copy.registerSubmit}
            </Button>
          </div>
        </form>
      )}
    </div>
  )
}
