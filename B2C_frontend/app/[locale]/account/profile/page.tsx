"use client"

import { use, useEffect, useState } from "react"

import { AuthGate } from "@/components/auth/AuthGate"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Textarea } from "@/components/ui/textarea"
import { updateProfile } from "@/lib/api/auth"
import { getErrorMessage } from "@/lib/api/client"
import { getLocalizedHref, isValidLocale, type Locale } from "@/lib/i18n"
import { useAuthSession } from "@/hooks/use-auth-session"

type AccountProfilePageProps = {
  params: Promise<{ locale: string }>
}

function ProfileScreen({ locale }: { locale: Locale }) {
  const session = useAuthSession()
  const [name, setName] = useState(session.user?.name ?? "")
  const [bio, setBio] = useState(session.user?.profile?.bio ?? "")
  const [message, setMessage] = useState<string | null>(null)
  const [error, setError] = useState<string | null>(null)
  const [isSaving, setIsSaving] = useState(false)

  useEffect(() => {
    setName(session.user?.name ?? "")
    setBio(session.user?.profile?.bio ?? "")
  }, [session.user?.name, session.user?.profile?.bio])

  async function handleSubmit() {
    if (!session.token) {
      return
    }

    setIsSaving(true)
    setError(null)
    setMessage(null)

    try {
      await updateProfile(
        {
          name,
          bio,
        },
        session.token,
      )
      await session.refreshUser()
      setMessage("Profile updated successfully.")
    } catch (nextError) {
      setError(getErrorMessage(nextError))
    } finally {
      setIsSaving(false)
    }
  }

  return (
    <div className="mx-auto max-w-3xl px-6 py-16 lg:px-8">
      <div className="rounded-[2rem] border border-border/60 bg-card p-8">
        <p className="text-sm uppercase tracking-[0.2em] text-primary">Profile</p>
        <h1 className="mt-3 font-serif text-4xl text-foreground">Edit Profile</h1>

        <div className="mt-8 space-y-5">
          <label className="space-y-2">
            <span className="text-sm text-foreground">Display Name</span>
            <Input value={name} onChange={(event) => setName(event.target.value)} />
          </label>

          <label className="space-y-2">
            <span className="text-sm text-foreground">Bio</span>
            <Textarea
              value={bio}
              onChange={(event) => setBio(event.target.value)}
              rows={6}
            />
          </label>

          {message ? (
            <div className="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
              {message}
            </div>
          ) : null}

          {error ? (
            <div className="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
              {error}
            </div>
          ) : null}

          <div className="flex justify-end">
            <Button type="button" disabled={isSaving} onClick={() => void handleSubmit()}>
              {isSaving ? "Saving..." : "Save Profile"}
            </Button>
          </div>
        </div>
      </div>
    </div>
  )
}

export default function AccountProfilePage({ params }: AccountProfilePageProps) {
  const resolvedParams = use(params)
  const locale = isValidLocale(resolvedParams.locale) ? resolvedParams.locale : "en"
  const profileHref = getLocalizedHref(locale, "account/profile")

  return (
    <AuthGate locale={locale} redirectAfterLogin={profileHref}>
      <ProfileScreen locale={locale} />
    </AuthGate>
  )
}
