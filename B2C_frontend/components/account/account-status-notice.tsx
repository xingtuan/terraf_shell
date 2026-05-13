"use client"

import Link from "next/link"
import { Ban, CircleCheck, TriangleAlert } from "lucide-react"

import { getAccountCopy } from "@/lib/account-copy"
import { getLocalizedHref, type Locale } from "@/lib/i18n"
import type { CommunityUser } from "@/lib/types"
import { cn } from "@/lib/utils"

type AccountStatusNoticeProps = {
  user: CommunityUser | null
  locale: Locale
  compact?: boolean
}

type AccountStatusUser = Pick<
  CommunityUser,
  | "account_status"
  | "is_banned"
  | "is_restricted"
  | "participation_restriction_reason"
>

function normalizedStatus(user?: AccountStatusUser | null) {
  return user?.account_status?.toLowerCase() ?? null
}

export function isAccountBanned(user?: AccountStatusUser | null) {
  return Boolean(user?.is_banned) || normalizedStatus(user) === "banned"
}

export function isAccountRestricted(user?: AccountStatusUser | null) {
  return Boolean(user?.is_restricted) || normalizedStatus(user) === "restricted"
}

function getRestrictionReason(user: AccountStatusUser) {
  const reason = user.participation_restriction_reason?.trim()

  return reason ? reason : null
}

export function AccountStatusNotice({
  user,
  locale,
  compact = false,
}: AccountStatusNoticeProps) {
  if (!user) {
    return null
  }

  const copy = getAccountCopy(locale).accountStatus
  const banned = isAccountBanned(user)
  const restricted = !banned && isAccountRestricted(user)

  if (!banned && !restricted && !compact) {
    return null
  }

  const state = banned ? "banned" : restricted ? "restricted" : "active"
  const title =
    state === "banned"
      ? copy.bannedTitle
      : state === "restricted"
        ? copy.restrictedTitle
        : copy.activeTitle
  const body =
    state === "banned"
      ? copy.bannedBody
      : state === "restricted"
        ? copy.restrictedBody
        : copy.activeBody
  const compactBody =
    state === "active"
      ? copy.goodStanding
      : state === "restricted"
        ? copy.communityUnavailable
        : copy.bannedBody
  const reason = state === "active" ? null : getRestrictionReason(user)
  const Icon =
    state === "banned" ? Ban : state === "restricted" ? TriangleAlert : CircleCheck
  const contactHref = getLocalizedHref(locale, "contact")

  const toneClasses = {
    active: "border-emerald-200 bg-emerald-50 text-emerald-900",
    restricted: "border-amber-200 bg-amber-50 text-amber-950",
    banned: "border-red-200 bg-red-50 text-red-950",
  }[state]
  const iconClasses = {
    active: "text-emerald-700",
    restricted: "text-amber-700",
    banned: "text-red-700",
  }[state]
  const mutedClasses = {
    active: "text-emerald-800/85",
    restricted: "text-amber-900/85",
    banned: "text-red-900/85",
  }[state]

  if (compact) {
    return (
      <div
        role={state === "active" ? "status" : "alert"}
        className={cn(
          "flex items-start gap-3 rounded-[1rem] border px-4 py-3 text-sm",
          toneClasses,
        )}
      >
        <Icon className={cn("mt-0.5 size-4 shrink-0", iconClasses)} />
        <div className="min-w-0">
          <p className="font-medium">{title}</p>
          <p className={cn("mt-1 leading-relaxed", mutedClasses)}>{compactBody}</p>
        </div>
      </div>
    )
  }

  return (
    <section
      role="alert"
      className={cn(
        "rounded-[1.25rem] border px-5 py-4 text-sm shadow-sm",
        toneClasses,
      )}
    >
      <div className="flex items-start gap-3">
        <Icon className={cn("mt-0.5 size-5 shrink-0", iconClasses)} />
        <div className="min-w-0">
          <h2 className="font-medium">{title}</h2>
          <p className={cn("mt-2 leading-relaxed", mutedClasses)}>{body}</p>
          {reason ? (
            <p className={cn("mt-3 font-medium leading-relaxed", mutedClasses)}>
              {copy.restrictedReason.replace("{reason}", reason)}
            </p>
          ) : null}
          <Link
            href={contactHref}
            className="mt-4 inline-flex text-sm font-medium underline-offset-4 hover:underline"
          >
            {copy.contactSupport}
          </Link>
        </div>
      </div>
    </section>
  )
}
