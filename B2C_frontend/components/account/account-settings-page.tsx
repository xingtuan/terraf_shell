"use client"

import { useEffect, useState } from "react"
import Link from "next/link"
import { useRouter } from "next/navigation"

import { Button } from "@/components/ui/button"
import { getAccountCopy } from "@/lib/account-copy"
import { getNotifications } from "@/lib/api/notifications"
import { getErrorMessage } from "@/lib/api/client"
import { getLocalizedHref, getMessages, type Locale } from "@/lib/i18n"
import { useAuthSession } from "@/hooks/use-auth-session"
import {
  AccountPageHeader,
  AccountPanel,
} from "@/components/account/account-ui"
import { formatAccountMonthYear } from "@/components/account/account-utils"

type AccountSettingsPageProps = {
  locale: Locale
}

function getAccountStatusLabel(
  accountStatus: string | null | undefined,
  isRestricted?: boolean,
  isBanned?: boolean,
  fallback = "Active",
) {
  if (isBanned) {
    return "Banned"
  }

  if (isRestricted) {
    return "Restricted"
  }

  return accountStatus || fallback
}

export function AccountSettingsPage({ locale }: AccountSettingsPageProps) {
  const router = useRouter()
  const session = useAuthSession()
  const copy = getAccountCopy(locale)
  const communityProfileMessages = getMessages(locale).community.profile
  const [unreadNotifications, setUnreadNotifications] = useState(0)
  const [error, setError] = useState<string | null>(null)
  const [isLoadingNotifications, setIsLoadingNotifications] = useState(true)
  const [isSigningOut, setIsSigningOut] = useState(false)

  useEffect(() => {
    if (!session.token) {
      return
    }

    setIsLoadingNotifications(true)
    setError(null)

    void getNotifications({ per_page: 1 }, session.token)
      .then((response) => {
        setUnreadNotifications(response.meta.unread_count ?? 0)
      })
      .catch((loadError) => {
        setError(getErrorMessage(loadError))
      })
      .finally(() => {
        setIsLoadingNotifications(false)
      })
  }, [session.token])

  const user = session.user

  if (!user) {
    return null
  }

  const publicProfileHref = getLocalizedHref(locale, `community/u/${user.username}`)
  const accountStatus = getAccountStatusLabel(
    user.account_status,
    user.is_restricted,
    user.is_banned,
    copy.settings.activeStatus,
  )

  return (
    <AccountPanel>
      <AccountPageHeader
        eyebrow={copy.settings.eyebrow}
        title={copy.settings.title}
        description={copy.settings.description}
        actions={
          <>
            <Button asChild variant="outline">
              <Link href={publicProfileHref}>{copy.shell.publicProfile}</Link>
            </Button>
            <Button asChild>
              <Link href={getLocalizedHref(locale, "account/profile")}>
                {copy.settings.manageProfile}
              </Link>
            </Button>
          </>
        }
      />

      {error ? (
        <div className="mt-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
          {error}
        </div>
      ) : null}

      <div className="mt-8 grid gap-6 xl:grid-cols-2">
        <AccountPanel className="bg-background/70 p-6">
          <p className="text-sm uppercase tracking-[0.18em] text-primary">
            {copy.settings.detailsTitle}
          </p>
          <h2 className="mt-3 font-serif text-3xl text-foreground">
            {copy.settings.detailsTitle}
          </h2>

          <div className="mt-6 space-y-4 text-sm">
            <div className="flex items-center justify-between gap-4 border-b border-border/60 pb-3">
              <span className="text-muted-foreground">{copy.profile.emailLabel}</span>
              <span className="text-right text-foreground">
                {user.email ?? "Not set"}
              </span>
            </div>
            <div className="flex items-center justify-between gap-4 border-b border-border/60 pb-3">
              <span className="text-muted-foreground">
                {communityProfileMessages.usernameLabel}
              </span>
              <span className="text-right text-foreground">@{user.username}</span>
            </div>
            <div className="flex items-center justify-between gap-4 border-b border-border/60 pb-3">
              <span className="text-muted-foreground">{copy.profile.roleLabel}</span>
              <span className="text-right text-foreground">
                {user.role ?? copy.settings.defaultRole}
              </span>
            </div>
            <div className="flex items-center justify-between gap-4 border-b border-border/60 pb-3">
              <span className="text-muted-foreground">{copy.profile.statusLabel}</span>
              <span className="text-right text-foreground">{accountStatus}</span>
            </div>
            <div className="flex items-center justify-between gap-4 border-b border-border/60 pb-3">
              <span className="text-muted-foreground">
                {copy.profile.memberSinceLabel}
              </span>
              <span className="text-right text-foreground">
                {formatAccountMonthYear(locale, user.created_at) ?? "—"}
              </span>
            </div>
            <div className="flex items-center justify-between gap-4">
              <span className="text-muted-foreground">
                {copy.profile.emailVerificationLabel}
              </span>
              <span className="text-right text-foreground">
                {user.email_verified
                  ? copy.profile.emailVerifiedLabel
                  : copy.profile.emailNotVerifiedLabel}
              </span>
            </div>
          </div>
        </AccountPanel>

        <AccountPanel className="bg-background/70 p-6">
          <p className="text-sm uppercase tracking-[0.18em] text-primary">
            {copy.settings.notificationsTitle}
          </p>
          <h2 className="mt-3 font-serif text-3xl text-foreground">
            {copy.settings.notificationsTitle}
          </h2>
          <p className="mt-4 text-sm leading-relaxed text-muted-foreground">
            {copy.settings.notificationsDescription}
          </p>
          <div className="mt-6 rounded-[1.5rem] border border-border/60 bg-card p-5">
            <p className="text-3xl text-foreground">
              {isLoadingNotifications ? "…" : unreadNotifications}
            </p>
            <p className="mt-2 text-sm text-muted-foreground">
              {unreadNotifications > 0
                ? copy.settings.unreadCount.replace(
                    "{count}",
                    String(unreadNotifications),
                  )
                : copy.settings.noUnread}
            </p>
          </div>
          <div className="mt-6">
            <Button asChild variant="outline">
              <Link href={getLocalizedHref(locale, "community")}>
                {copy.overview.browseCommunity}
              </Link>
            </Button>
          </div>
        </AccountPanel>

        <AccountPanel className="bg-background/70 p-6">
          <p className="text-sm uppercase tracking-[0.18em] text-primary">
            {copy.settings.securityTitle}
          </p>
          <h2 className="mt-3 font-serif text-3xl text-foreground">
            {copy.settings.securityTitle}
          </h2>
          <p className="mt-4 text-sm leading-relaxed text-muted-foreground">
            {copy.settings.securityDescription}
          </p>
        </AccountPanel>

        <AccountPanel className="bg-background/70 p-6">
          <p className="text-sm uppercase tracking-[0.18em] text-primary">
            {copy.settings.sessionTitle}
          </p>
          <h2 className="mt-3 font-serif text-3xl text-foreground">
            {copy.settings.sessionTitle}
          </h2>
          <p className="mt-4 text-sm leading-relaxed text-muted-foreground">
            {copy.settings.sessionDescription}
          </p>
          <div className="mt-6 flex flex-wrap gap-3">
            <Button asChild variant="outline">
              <Link href={getLocalizedHref(locale, "account/orders")}>
                {copy.settings.viewOrders}
              </Link>
            </Button>
            <Button asChild variant="outline">
              <Link href={getLocalizedHref(locale, "account/profile")}>
                {copy.settings.manageProfile}
              </Link>
            </Button>
            <Button
              type="button"
              variant="destructive"
              disabled={isSigningOut}
              onClick={() => {
                setIsSigningOut(true)

                void session
                  .logout()
                  .then(() => {
                    router.push(getLocalizedHref(locale))
                  })
                  .finally(() => {
                    setIsSigningOut(false)
                  })
              }}
            >
              {isSigningOut ? copy.settings.signingOut : copy.settings.signOut}
            </Button>
          </div>
        </AccountPanel>
      </div>
    </AccountPanel>
  )
}
