"use client"
import { Loader2 } from "lucide-react"
import { useRef, useState } from "react"
import { useParams } from "next/navigation"

import { CommunityUserAvatar } from "@/components/community/CommunityUserAvatar"
import { Button } from "@/components/ui/button"
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog"
import { Input } from "@/components/ui/input"
import { Textarea } from "@/components/ui/textarea"
import { ApiError, getErrorMessage } from "@/lib/api/client"
import { uploadMedia } from "@/lib/api/media"
import { updateProfile } from "@/lib/api/users"
import { defaultLocale, getMessages, isValidLocale } from "@/lib/i18n"
import type { UserProfile } from "@/lib/types"
import { useAuthSession } from "@/hooks/use-auth-session"

type FieldErrors = Partial<Record<"name" | "username" | "bio" | "avatar", string>>

type EditProfileModalProps = {
  user: UserProfile
  onClose: () => void
  onSave: (updated: UserProfile) => void
}

const USERNAME_PATTERN = /^[a-z0-9_]+$/

export function EditProfileModal({
  user,
  onClose,
  onSave,
}: EditProfileModalProps) {
  const params = useParams<{ locale?: string }>()
  const requestedLocale = params?.locale ?? defaultLocale
  const locale = isValidLocale(requestedLocale) ? requestedLocale : defaultLocale
  const messages = getMessages(locale).community.profile
  const session = useAuthSession()
  const fileInputRef = useRef<HTMLInputElement | null>(null)
  const [name, setName] = useState(user.name ?? "")
  const [username, setUsername] = useState(user.username ?? "")
  const [bio, setBio] = useState(user.bio ?? user.profile?.bio ?? "")
  const [avatarUrl, setAvatarUrl] = useState(user.avatar_url ?? null)
  const [avatarPath, setAvatarPath] = useState<string | null>(null)
  const [errors, setErrors] = useState<FieldErrors>({})
  const [formError, setFormError] = useState<string | null>(null)
  const [isSaving, setIsSaving] = useState(false)
  const [isUploadingAvatar, setIsUploadingAvatar] = useState(false)

  function validate() {
    const nextErrors: FieldErrors = {}
    const trimmedName = name.trim()
    const normalizedUsername = username.trim().toLowerCase()
    const trimmedBio = bio.trim()

    if (!trimmedName) {
      nextErrors.name = messages.nameRequired
    } else if (trimmedName.length > 60) {
      nextErrors.name = messages.nameMax
    }

    if (!normalizedUsername) {
      nextErrors.username = messages.usernameRequired
    } else if (normalizedUsername.length > 30) {
      nextErrors.username = messages.usernameMax
    } else if (!USERNAME_PATTERN.test(normalizedUsername)) {
      nextErrors.username = messages.usernameInvalid
    }

    if (trimmedBio.length > 200) {
      nextErrors.bio = messages.bioMax
    }

    setErrors(nextErrors)

    return Object.keys(nextErrors).length === 0
  }

  return (
    <Dialog open onOpenChange={(open) => !open && onClose()}>
      <DialogContent className="max-w-xl">
        <DialogHeader>
          <DialogTitle>{messages.editProfile}</DialogTitle>
          <DialogDescription>{messages.editProfileDescription}</DialogDescription>
        </DialogHeader>

        <form
          className="space-y-6"
          onSubmit={(event) => {
            event.preventDefault()

            if (!session.token || !validate()) {
              return
            }

            setIsSaving(true)
            setFormError(null)

            void updateProfile(
              {
                name: name.trim(),
                username: username.trim().toLowerCase(),
                bio: bio.trim(),
                avatar_url: avatarUrl,
                avatar_path: avatarPath,
              },
              session.token,
            )
              .then((updatedUser) => {
                if (!updatedUser) {
                  return
                }

                onSave(updatedUser)
                onClose()
              })
              .catch((error) => {
                if (error instanceof ApiError) {
                  setErrors({
                    name: error.errors?.name?.[0],
                    username: error.errors?.username?.[0],
                    bio: error.errors?.bio?.[0],
                    avatar:
                      error.errors?.avatar?.[0] ??
                      error.errors?.avatar_path?.[0] ??
                      error.errors?.avatar_url?.[0],
                  })
                }

                setFormError(getErrorMessage(error))
              })
              .finally(() => {
                setIsSaving(false)
              })
          }}
        >
          <div className="flex flex-col gap-4 sm:flex-row sm:items-center">
            <div className="relative">
              <CommunityUserAvatar
                src={avatarUrl}
                name={name || username || user.name}
                className="size-24 border border-border/60"
                fallbackClassName="text-lg"
                sizes="96px"
              />
              {isUploadingAvatar ? (
                <div className="absolute inset-0 flex items-center justify-center rounded-full bg-background/70">
                  <Loader2 className="size-5 animate-spin text-foreground" />
                </div>
              ) : null}
            </div>

            <div className="space-y-2">
              <Button
                type="button"
                variant="outline"
                onClick={() => fileInputRef.current?.click()}
                disabled={isUploadingAvatar}
              >
                {isUploadingAvatar ? messages.uploadingPhoto : messages.changePhoto}
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
                  setErrors((currentErrors) => ({ ...currentErrors, avatar: undefined }))
                  setFormError(null)

                  void uploadMedia(file, "avatars")
                    .then((uploaded) => {
                      setAvatarUrl(uploaded.url)
                      setAvatarPath(uploaded.path)
                    })
                    .catch((error) => {
                      setErrors((currentErrors) => ({
                        ...currentErrors,
                        avatar: getErrorMessage(error),
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

          <div className="space-y-2">
            <label className="text-sm font-medium text-foreground">
              {messages.nameLabel}
            </label>
            <Input
              value={name}
              maxLength={60}
              onChange={(event) => {
                setName(event.target.value)
                setErrors((currentErrors) => ({ ...currentErrors, name: undefined }))
              }}
              placeholder={messages.namePlaceholder}
            />
            {errors.name ? (
              <p className="text-sm text-destructive">{errors.name}</p>
            ) : null}
          </div>

          <div className="space-y-2">
            <label className="text-sm font-medium text-foreground">
              {messages.usernameLabel}
            </label>
            <Input
              value={username}
              maxLength={30}
              onChange={(event) => {
                setUsername(event.target.value.toLowerCase())
                setErrors((currentErrors) => ({
                  ...currentErrors,
                  username: undefined,
                }))
              }}
              placeholder={messages.usernamePlaceholder}
            />
            <p className="text-xs text-muted-foreground">
              {messages.usernameHint}
            </p>
            {errors.username ? (
              <p className="text-sm text-destructive">{errors.username}</p>
            ) : null}
          </div>

          <div className="space-y-2">
            <label className="text-sm font-medium text-foreground">
              {messages.bioLabel}
            </label>
            <Textarea
              value={bio}
              rows={4}
              maxLength={200}
              onChange={(event) => {
                setBio(event.target.value)
                setErrors((currentErrors) => ({ ...currentErrors, bio: undefined }))
              }}
              placeholder={messages.bioPlaceholder}
            />
            <div className="flex items-center justify-between gap-3 text-xs text-muted-foreground">
              <span>{messages.bioHint}</span>
              <span>{bio.length} / 200</span>
            </div>
            {errors.bio ? (
              <p className="text-sm text-destructive">{errors.bio}</p>
            ) : null}
          </div>

          {formError ? (
            <div className="rounded-2xl border border-destructive/30 bg-destructive/5 px-4 py-3 text-sm text-destructive">
              {formError}
            </div>
          ) : null}

          <DialogFooter>
            <Button type="button" variant="outline" onClick={onClose}>
              {messages.cancel}
            </Button>
            <Button type="submit" disabled={isSaving || isUploadingAvatar}>
              {isSaving ? messages.savingProfile : messages.saveProfile}
            </Button>
          </DialogFooter>
        </form>
      </DialogContent>
    </Dialog>
  )
}
