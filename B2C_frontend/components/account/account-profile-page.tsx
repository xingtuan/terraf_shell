"use client"

import { Loader2 } from "lucide-react"
import { useEffect, useRef, useState } from "react"
import Link from "next/link"

import { CommunityUserAvatar } from "@/components/community/CommunityUserAvatar"
import { Button } from "@/components/ui/button"
import { Checkbox } from "@/components/ui/checkbox"
import { Input } from "@/components/ui/input"
import { Textarea } from "@/components/ui/textarea"
import { getAccountCopy } from "@/lib/account-copy"
import { ApiError, getErrorMessage } from "@/lib/api/client"
import { uploadMedia } from "@/lib/api/media"
import { getUserProfile, updateProfile } from "@/lib/api/users"
import { getLocalizedHref, getMessages, type Locale } from "@/lib/i18n"
import type { UserProfile } from "@/lib/types"
import { useAuthSession } from "@/hooks/use-auth-session"
import {
  AccountPageHeader,
  AccountPanel,
} from "@/components/account/account-ui"
import { formatAccountMonthYear } from "@/components/account/account-utils"

type AccountProfilePageProps = {
  locale: Locale
}

type ProfileFormValues = {
  avatar_url: string | null
  avatar_path: string | null
  name: string
  username: string
  email: string
  bio: string
  location: string
  region: string
  school_or_company: string
  website: string
  portfolio_url: string
  open_to_collab: boolean
}

type FieldErrors = Partial<
  Record<
    | "avatar"
    | "name"
    | "username"
    | "email"
    | "bio"
    | "website"
    | "portfolio_url",
    string
  >
>

const USERNAME_PATTERN = /^[a-z0-9_]+$/

function createInitialForm(profile: UserProfile | null): ProfileFormValues {
  return {
    avatar_url: profile?.avatar_url ?? null,
    avatar_path: null,
    name: profile?.name ?? "",
    username: profile?.username ?? "",
    email: profile?.email ?? "",
    bio: profile?.bio ?? profile?.profile?.bio ?? "",
    location: profile?.profile?.location ?? "",
    region: profile?.profile?.region ?? "",
    school_or_company: profile?.profile?.school_or_company ?? "",
    website: profile?.profile?.website ?? "",
    portfolio_url: profile?.profile?.portfolio_url ?? "",
    open_to_collab: Boolean(profile?.profile?.open_to_collab),
  }
}

function isValidUrl(value: string) {
  try {
    new URL(value)
    return true
  } catch {
    return false
  }
}

export function AccountProfilePage({ locale }: AccountProfilePageProps) {
  const session = useAuthSession()
  const copy = getAccountCopy(locale)
  const communityProfileMessages = getMessages(locale).community.profile
  const fileInputRef = useRef<HTMLInputElement | null>(null)
  const [profile, setProfile] = useState<UserProfile | null>(null)
  const [form, setForm] = useState<ProfileFormValues>(createInitialForm(null))
  const [loading, setLoading] = useState(true)
  const [isSaving, setIsSaving] = useState(false)
  const [isUploadingAvatar, setIsUploadingAvatar] = useState(false)
  const [message, setMessage] = useState<string | null>(null)
  const [formError, setFormError] = useState<string | null>(null)
  const [errors, setErrors] = useState<FieldErrors>({})

  useEffect(() => {
    if (!session.token || !session.user?.username) {
      return
    }

    setLoading(true)
    setFormError(null)

    void getUserProfile(session.user.username, session.token)
      .then((nextProfile) => {
        setProfile(nextProfile)
        setForm(createInitialForm(nextProfile))
      })
      .catch((loadError) => {
        setFormError(getErrorMessage(loadError))
      })
      .finally(() => {
        setLoading(false)
      })
  }, [session.token, session.user?.username])

  function validate() {
    const nextErrors: FieldErrors = {}
    const trimmedName = form.name.trim()
    const normalizedUsername = form.username.trim().toLowerCase()
    const trimmedBio = form.bio.trim()
    const trimmedEmail = form.email.trim()
    const trimmedWebsite = form.website.trim()
    const trimmedPortfolio = form.portfolio_url.trim()

    if (!trimmedName) {
      nextErrors.name = communityProfileMessages.nameRequired
    } else if (trimmedName.length > 60) {
      nextErrors.name = communityProfileMessages.nameMax
    }

    if (!normalizedUsername) {
      nextErrors.username = communityProfileMessages.usernameRequired
    } else if (normalizedUsername.length > 30) {
      nextErrors.username = communityProfileMessages.usernameMax
    } else if (!USERNAME_PATTERN.test(normalizedUsername)) {
      nextErrors.username = communityProfileMessages.usernameInvalid
    }

    if (trimmedBio.length > 200) {
      nextErrors.bio = communityProfileMessages.bioMax
    }

    if (trimmedEmail && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(trimmedEmail)) {
      nextErrors.email = copy.profile.emailInvalid
    }

    if (trimmedWebsite && !isValidUrl(trimmedWebsite)) {
      nextErrors.website = copy.profile.urlInvalid
    }

    if (trimmedPortfolio && !isValidUrl(trimmedPortfolio)) {
      nextErrors.portfolio_url = copy.profile.urlInvalid
    }

    setErrors(nextErrors)

    return Object.keys(nextErrors).length === 0
  }

  async function handleSubmit() {
    if (!session.token || !validate()) {
      return
    }

    setIsSaving(true)
    setFormError(null)
    setMessage(null)

    try {
      const updatedProfile = await updateProfile(
        {
          avatar_url: form.avatar_url,
          avatar_path: form.avatar_path,
          name: form.name.trim(),
          username: form.username.trim().toLowerCase(),
          email: form.email.trim(),
          bio: form.bio.trim(),
          location: form.location.trim(),
          region: form.region.trim(),
          school_or_company: form.school_or_company.trim(),
          website: form.website.trim(),
          portfolio_url: form.portfolio_url.trim(),
          open_to_collab: form.open_to_collab,
        },
        session.token,
      )

      setProfile(updatedProfile)
      setForm(createInitialForm(updatedProfile))
      setMessage(copy.profile.success)
      void session.refreshUser().catch(() => null)
    } catch (submitError) {
      if (submitError instanceof ApiError) {
        setErrors({
          avatar:
            submitError.errors?.avatar?.[0] ??
            submitError.errors?.avatar_path?.[0] ??
            submitError.errors?.avatar_url?.[0],
          name: submitError.errors?.name?.[0],
          username: submitError.errors?.username?.[0],
          email: submitError.errors?.email?.[0],
          bio: submitError.errors?.bio?.[0],
          website: submitError.errors?.website?.[0],
          portfolio_url: submitError.errors?.portfolio_url?.[0],
        })
      }

      setFormError(getErrorMessage(submitError))
    } finally {
      setIsSaving(false)
    }
  }

  const previewProfileHref = getLocalizedHref(
    locale,
    `community/u/${form.username.trim().toLowerCase() || session.user?.username || ""}`,
  )

  return (
    <AccountPanel>
      <AccountPageHeader
        eyebrow={copy.profile.eyebrow}
        title={copy.profile.title}
        description={copy.profile.description}
        actions={
          <Button asChild variant="outline">
            <Link href={previewProfileHref}>{copy.profile.viewPublicProfile}</Link>
          </Button>
        }
      />

      {message ? (
        <div className="mt-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
          {message}
        </div>
      ) : null}

      {formError ? (
        <div className="mt-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
          {formError}
        </div>
      ) : null}

      {loading ? (
        <div className="mt-8 rounded-[1.5rem] border border-border/60 bg-background/70 p-6 text-sm text-muted-foreground">
          {copy.profile.loading}
        </div>
      ) : (
        <div className="mt-8 grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
          <div className="space-y-6">
            <AccountPanel className="bg-background/70 p-6">
              <div className="flex flex-col gap-5 sm:flex-row sm:items-center">
                <div className="relative">
                  <CommunityUserAvatar
                    src={form.avatar_url}
                    name={form.name || form.username || profile?.name}
                    className="size-24 border border-border/60"
                    fallbackClassName="text-lg"
                    sizes="96px"
                  />
                  {isUploadingAvatar ? (
                    <div className="absolute inset-0 flex items-center justify-center rounded-full bg-background/80">
                      <Loader2 className="size-5 animate-spin text-foreground" />
                    </div>
                  ) : null}
                </div>
                <div className="space-y-2">
                  <p className="text-sm uppercase tracking-[0.18em] text-primary">
                    {copy.profile.basicTitle}
                  </p>
                  <p className="text-sm text-muted-foreground">
                    {copy.profile.avatarHint}
                  </p>
                  <Button
                    type="button"
                    variant="outline"
                    onClick={() => fileInputRef.current?.click()}
                    disabled={isUploadingAvatar}
                  >
                    {isUploadingAvatar
                      ? communityProfileMessages.uploadingPhoto
                      : communityProfileMessages.changePhoto}
                  </Button>
                  <input
                    ref={fileInputRef}
                    type="file"
                    accept="image/*"
                    className="hidden"
                    onChange={(event) => {
                      const file = event.target.files?.[0]

                      if (!file) {
                        return
                      }

                      setIsUploadingAvatar(true)
                      setErrors((currentErrors) => ({
                        ...currentErrors,
                        avatar: undefined,
                      }))
                      setFormError(null)

                      void uploadMedia(file, "avatars")
                        .then((uploaded) => {
                          setForm((currentValue) => ({
                            ...currentValue,
                            avatar_url: uploaded.url,
                            avatar_path: uploaded.path,
                          }))
                        })
                        .catch((uploadError) => {
                          setErrors((currentErrors) => ({
                            ...currentErrors,
                            avatar: getErrorMessage(uploadError),
                          }))
                        })
                        .finally(() => {
                          setIsUploadingAvatar(false)

                          if (fileInputRef.current) {
                            fileInputRef.current.value = ""
                          }
                        })
                    }}
                  />
                  {errors.avatar ? (
                    <p className="text-sm text-destructive">{errors.avatar}</p>
                  ) : null}
                </div>
              </div>

              <div className="mt-8 grid gap-4">
                <div className="space-y-2">
                  <label className="text-sm font-medium text-foreground">
                    {communityProfileMessages.nameLabel}
                  </label>
                  <Input
                    value={form.name}
                    maxLength={60}
                    onChange={(event) => {
                      setForm((currentValue) => ({
                        ...currentValue,
                        name: event.target.value,
                      }))
                      setErrors((currentErrors) => ({
                        ...currentErrors,
                        name: undefined,
                      }))
                    }}
                    placeholder={communityProfileMessages.namePlaceholder}
                  />
                  {errors.name ? (
                    <p className="text-sm text-destructive">{errors.name}</p>
                  ) : null}
                </div>

                <div className="space-y-2">
                  <label className="text-sm font-medium text-foreground">
                    {communityProfileMessages.usernameLabel}
                  </label>
                  <Input
                    value={form.username}
                    maxLength={30}
                    onChange={(event) => {
                      setForm((currentValue) => ({
                        ...currentValue,
                        username: event.target.value.toLowerCase(),
                      }))
                      setErrors((currentErrors) => ({
                        ...currentErrors,
                        username: undefined,
                      }))
                    }}
                    placeholder={communityProfileMessages.usernamePlaceholder}
                  />
                  <p className="text-xs text-muted-foreground">
                    {communityProfileMessages.usernameHint}
                  </p>
                  {errors.username ? (
                    <p className="text-sm text-destructive">{errors.username}</p>
                  ) : null}
                </div>

                <div className="space-y-2">
                  <label className="text-sm font-medium text-foreground">
                    {communityProfileMessages.bioLabel}
                  </label>
                  <Textarea
                    value={form.bio}
                    rows={4}
                    maxLength={200}
                    onChange={(event) => {
                      setForm((currentValue) => ({
                        ...currentValue,
                        bio: event.target.value,
                      }))
                      setErrors((currentErrors) => ({
                        ...currentErrors,
                        bio: undefined,
                      }))
                    }}
                    placeholder={communityProfileMessages.bioPlaceholder}
                  />
                  <div className="flex items-center justify-between gap-3 text-xs text-muted-foreground">
                    <span>{communityProfileMessages.bioHint}</span>
                    <span>{form.bio.length} / 200</span>
                  </div>
                  {errors.bio ? (
                    <p className="text-sm text-destructive">{errors.bio}</p>
                  ) : null}
                </div>
              </div>
            </AccountPanel>

            <AccountPanel className="bg-background/70 p-6">
              <p className="text-sm uppercase tracking-[0.18em] text-primary">
                {copy.profile.professionalTitle}
              </p>
              <h2 className="mt-3 font-serif text-3xl text-foreground">
                {copy.profile.professionalTitle}
              </h2>
              <p className="mt-3 text-sm leading-relaxed text-muted-foreground">
                {copy.profile.professionalDescription}
              </p>

              <div className="mt-8 grid gap-4 md:grid-cols-2">
                <div className="space-y-2">
                  <label className="text-sm font-medium text-foreground">
                    {copy.profile.locationLabel}
                  </label>
                  <Input
                    value={form.location}
                    onChange={(event) =>
                      setForm((currentValue) => ({
                        ...currentValue,
                        location: event.target.value,
                      }))
                    }
                    placeholder={copy.profile.locationPlaceholder}
                  />
                </div>
                <div className="space-y-2">
                  <label className="text-sm font-medium text-foreground">
                    {copy.profile.regionLabel}
                  </label>
                  <Input
                    value={form.region}
                    onChange={(event) =>
                      setForm((currentValue) => ({
                        ...currentValue,
                        region: event.target.value,
                      }))
                    }
                    placeholder={copy.profile.regionPlaceholder}
                  />
                </div>
                <div className="space-y-2">
                  <label className="text-sm font-medium text-foreground">
                    {copy.profile.organizationLabel}
                  </label>
                  <Input
                    value={form.school_or_company}
                    onChange={(event) =>
                      setForm((currentValue) => ({
                        ...currentValue,
                        school_or_company: event.target.value,
                      }))
                    }
                    placeholder={copy.profile.organizationPlaceholder}
                  />
                </div>
                <div className="space-y-2">
                  <label className="text-sm font-medium text-foreground">
                    {copy.profile.websiteLabel}
                  </label>
                  <Input
                    value={form.website}
                    onChange={(event) => {
                      setForm((currentValue) => ({
                        ...currentValue,
                        website: event.target.value,
                      }))
                      setErrors((currentErrors) => ({
                        ...currentErrors,
                        website: undefined,
                      }))
                    }}
                    placeholder={copy.profile.websitePlaceholder}
                  />
                  {errors.website ? (
                    <p className="text-sm text-destructive">{errors.website}</p>
                  ) : null}
                </div>
                <div className="space-y-2 md:col-span-2">
                  <label className="text-sm font-medium text-foreground">
                    {copy.profile.portfolioLabel}
                  </label>
                  <Input
                    value={form.portfolio_url}
                    onChange={(event) => {
                      setForm((currentValue) => ({
                        ...currentValue,
                        portfolio_url: event.target.value,
                      }))
                      setErrors((currentErrors) => ({
                        ...currentErrors,
                        portfolio_url: undefined,
                      }))
                    }}
                    placeholder={copy.profile.portfolioPlaceholder}
                  />
                  {errors.portfolio_url ? (
                    <p className="text-sm text-destructive">
                      {errors.portfolio_url}
                    </p>
                  ) : null}
                </div>
              </div>

              <label className="mt-6 flex items-start gap-3 rounded-[1.25rem] border border-border/60 bg-card px-4 py-4 text-sm text-foreground">
                <Checkbox
                  checked={form.open_to_collab}
                  onCheckedChange={(checked) =>
                    setForm((currentValue) => ({
                      ...currentValue,
                      open_to_collab: Boolean(checked),
                    }))
                  }
                />
                <span>
                  <span className="block font-medium">
                    {copy.profile.collaborationLabel}
                  </span>
                  <span className="mt-1 block text-muted-foreground">
                    {copy.profile.collaborationHint}
                  </span>
                </span>
              </label>
            </AccountPanel>

            <AccountPanel className="bg-background/70 p-6">
              <p className="text-sm uppercase tracking-[0.18em] text-primary">
                {copy.profile.identityTitle}
              </p>
              <h2 className="mt-3 font-serif text-3xl text-foreground">
                {copy.profile.identityTitle}
              </h2>
              <p className="mt-3 text-sm leading-relaxed text-muted-foreground">
                {copy.profile.identityDescription}
              </p>

              <div className="mt-8 space-y-2">
                <label className="text-sm font-medium text-foreground">
                  {copy.profile.emailLabel}
                </label>
                <Input
                  value={form.email}
                  onChange={(event) => {
                    setForm((currentValue) => ({
                      ...currentValue,
                      email: event.target.value,
                    }))
                    setErrors((currentErrors) => ({
                      ...currentErrors,
                      email: undefined,
                    }))
                  }}
                  placeholder={copy.profile.emailPlaceholder}
                />
                {errors.email ? (
                  <p className="text-sm text-destructive">{errors.email}</p>
                ) : null}
              </div>
            </AccountPanel>
          </div>

          <div className="space-y-6">
            <AccountPanel className="bg-background/70 p-6">
              <p className="text-sm uppercase tracking-[0.18em] text-primary">
                {copy.profile.publicPreviewTitle}
              </p>
              <h2 className="mt-3 font-serif text-3xl text-foreground">
                {copy.profile.publicPreviewTitle}
              </h2>
              <p className="mt-3 text-sm leading-relaxed text-muted-foreground">
                {copy.profile.publicPreviewDescription}
              </p>

              <div className="mt-6 rounded-[1.5rem] border border-border/60 bg-card p-6">
                <div className="flex items-center gap-4">
                  <CommunityUserAvatar
                    src={form.avatar_url}
                    name={form.name || form.username || profile?.name}
                    className="size-16 border border-border/60"
                    fallbackClassName="text-base"
                    sizes="64px"
                  />
                  <div>
                    <p className="text-lg text-foreground">{form.name || "—"}</p>
                    <p className="text-sm text-muted-foreground">
                      @{form.username || "username"}
                    </p>
                  </div>
                </div>

                {form.bio ? (
                  <p className="mt-4 text-sm leading-relaxed text-muted-foreground">
                    {form.bio}
                  </p>
                ) : null}

                <div className="mt-6 flex flex-wrap gap-2 text-xs">
                  {form.location ? (
                    <span className="rounded-full bg-background px-3 py-1 text-muted-foreground">
                      {form.location}
                    </span>
                  ) : null}
                  {form.region ? (
                    <span className="rounded-full bg-background px-3 py-1 text-muted-foreground">
                      {form.region}
                    </span>
                  ) : null}
                  {form.school_or_company ? (
                    <span className="rounded-full bg-background px-3 py-1 text-muted-foreground">
                      {form.school_or_company}
                    </span>
                  ) : null}
                  {form.open_to_collab ? (
                    <span className="rounded-full bg-primary/10 px-3 py-1 text-primary">
                      {copy.profile.collaborationLabel}
                    </span>
                  ) : null}
                </div>
              </div>
            </AccountPanel>

            <AccountPanel className="bg-background/70 p-6">
              <div className="space-y-4 text-sm">
                <div className="flex items-center justify-between gap-4 border-b border-border/60 pb-3">
                  <span className="text-muted-foreground">{copy.profile.roleLabel}</span>
                  <span className="text-right text-foreground">
                    {session.user?.role ?? copy.settings.defaultRole}
                  </span>
                </div>
                <div className="flex items-center justify-between gap-4 border-b border-border/60 pb-3">
                  <span className="text-muted-foreground">
                    {copy.profile.statusLabel}
                  </span>
                  <span className="text-right text-foreground">
                    {session.user?.account_status ?? copy.settings.activeStatus}
                  </span>
                </div>
                <div className="flex items-center justify-between gap-4 border-b border-border/60 pb-3">
                  <span className="text-muted-foreground">
                    {copy.profile.memberSinceLabel}
                  </span>
                  <span className="text-right text-foreground">
                    {formatAccountMonthYear(
                      locale,
                      profile?.joined_at ?? profile?.created_at ?? session.user?.created_at,
                    ) ?? "—"}
                  </span>
                </div>
                <div className="flex items-center justify-between gap-4">
                  <span className="text-muted-foreground">
                    {copy.profile.emailLabel}
                  </span>
                  <span className="text-right text-foreground">
                    {form.email || "—"}
                  </span>
                </div>
              </div>

              <div className="mt-8 flex flex-wrap gap-3">
                <Button type="button" onClick={() => void handleSubmit()} disabled={isSaving}>
                  {isSaving ? copy.profile.saving : copy.profile.save}
                </Button>
                <Button asChild variant="outline">
                  <Link href={previewProfileHref}>{copy.profile.viewPublicProfile}</Link>
                </Button>
              </div>
            </AccountPanel>
          </div>
        </div>
      )}
    </AccountPanel>
  )
}
