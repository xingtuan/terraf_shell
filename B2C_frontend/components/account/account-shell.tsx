"use client"

import type { ComponentType, ReactNode } from "react"
import { useEffect, useState } from "react"
import Link from "next/link"
import { usePathname, useRouter } from "next/navigation"
import {
  LayoutDashboard,
  MapPinHouse,
  MessageSquareText,
  Package,
  ShoppingBag,
  UserRound,
} from "lucide-react"

import {
  AccountStatusNotice,
  isAccountBanned,
  isAccountRestricted,
} from "@/components/account/account-status-notice"
import { CommunityUserAvatar } from "@/components/community/CommunityUserAvatar"
import { Button } from "@/components/ui/button"
import { getAccountCopy } from "@/lib/account-copy"
import { getLocalizedHref, type Locale } from "@/lib/i18n"
import { cn } from "@/lib/utils"
import { useAuthSession } from "@/hooks/use-auth-session"

type AccountShellProps = {
  children: ReactNode
  locale: Locale
}

type NavItem = {
  href: string
  icon: ComponentType<{ className?: string }>
  label: string
}

function isActivePath(pathname: string, href: string) {
  if (href.endsWith("/account")) {
    return pathname === href
  }

  return pathname === href || pathname.startsWith(`${href}/`)
}

function AccountShellContent({ children, locale }: AccountShellProps) {
  const router = useRouter()
  const session = useAuthSession()
  const pathname = usePathname()
  const copy = getAccountCopy(locale)
  const [isSigningOut, setIsSigningOut] = useState(false)

  useEffect(() => {
    if (!session.isReady) return

    if (!session.token) {
      const loginHref = `${getLocalizedHref(locale, "auth/login")}?next=${encodeURIComponent(pathname)}`
      router.replace(loginHref)
      return
    }

    void session.refreshUser().catch(() => null)
  }, [session.isReady, session.token, session.refreshUser, router, locale, pathname])

  if (!session.isReady) {
    return (
      <div className="mx-auto max-w-7xl px-6 py-24 lg:px-8">
        <div className="rounded-3xl border border-border/60 bg-card p-8 text-sm text-muted-foreground">
          {getAccountCopy(locale).overview.loading}
        </div>
      </div>
    )
  }

  if (!session.user) {
    return null
  }

  const accountHomeHref = getLocalizedHref(locale, "account")
  const items: NavItem[] = [
    {
      href: accountHomeHref,
      icon: LayoutDashboard,
      label: copy.nav.overview,
    },
    {
      href: getLocalizedHref(locale, "account/orders"),
      icon: Package,
      label: copy.nav.orders,
    },
    {
      href: getLocalizedHref(locale, "account/addresses"),
      icon: MapPinHouse,
      label: copy.nav.addresses,
    },
    {
      href: getLocalizedHref(locale, "account/profile"),
      icon: UserRound,
      label: copy.nav.profile,
    },
    {
      href: getLocalizedHref(locale, "account/community"),
      icon: MessageSquareText,
      label: copy.nav.community,
    },
    {
      href: getLocalizedHref(locale, "account/store"),
      icon: ShoppingBag,
      label: copy.nav.store,
    },
  ]

  const publicProfileHref = getLocalizedHref(
    locale,
    `community/u/${session.user.username}`,
  )
  const showShellStatusNotice =
    pathname !== accountHomeHref &&
    (isAccountBanned(session.user) || isAccountRestricted(session.user))

  return (
    <div className="bg-[radial-gradient(circle_at_top_left,_rgba(201,244,226,0.7),_transparent_32%),radial-gradient(circle_at_top_right,_rgba(248,228,198,0.55),_transparent_28%)]">
      <div className="mx-auto max-w-7xl px-6 py-10 lg:px-8">
        <section className="overflow-hidden rounded-[2rem] border border-border/60 bg-card/95 p-6 shadow-sm sm:p-8">
          <div className="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
            <div className="max-w-3xl">
              <p className="text-sm uppercase tracking-[0.2em] text-primary">
                {copy.shell.eyebrow}
              </p>
              <h1 className="mt-3 font-serif text-4xl text-foreground">
                {copy.shell.title}
              </h1>
              <p className="mt-4 text-sm leading-relaxed text-muted-foreground">
                {copy.shell.description}
              </p>
            </div>

            <div className="flex flex-col gap-4 rounded-[1.5rem] border border-border/60 bg-background/80 p-4 sm:flex-row sm:items-center">
              <CommunityUserAvatar
                user={session.user}
                className="size-16 border border-border/60"
                fallbackClassName="text-lg"
                sizes="64px"
              />
              <div className="min-w-0">
                <p className="text-sm uppercase tracking-[0.18em] text-primary">
                  {copy.shell.privateWorkspace}
                </p>
                <p className="mt-2 text-xl text-foreground">{session.user.name}</p>
                <p className="mt-1 text-sm text-muted-foreground">
                  {copy.shell.signedInAs.replace(
                    "{email}",
                    session.user.email ?? "OXP account",
                  )}
                </p>
              </div>
              <div className="flex flex-wrap gap-2 sm:flex-col sm:self-start">
                <Button asChild variant="outline">
                  <Link href={publicProfileHref}>{copy.shell.publicProfile}</Link>
                </Button>
                <Button
                  type="button"
                  variant="ghost"
                  disabled={isSigningOut}
                  onClick={() => {
                    setIsSigningOut(true)
                    void session
                      .logout()
                      .then(() => router.push(getLocalizedHref(locale)))
                      .finally(() => setIsSigningOut(false))
                  }}
                >
                  {isSigningOut ? copy.settings.signingOut : copy.settings.signOut}
                </Button>
              </div>
            </div>
          </div>
        </section>

        <div className="mt-8 grid gap-8 lg:grid-cols-[17rem_minmax(0,1fr)]">
          <aside className="lg:sticky lg:top-28 lg:self-start">
            <nav className="rounded-[2rem] border border-border/60 bg-card p-4 shadow-sm">
              <div className="flex gap-2 overflow-x-auto pb-1 lg:flex-col lg:overflow-visible">
                {items.map((item) => {
                  const active = isActivePath(pathname, item.href)
                  const Icon = item.icon

                  return (
                    <Link
                      key={item.href}
                      href={item.href}
                      className={cn(
                        "flex min-w-fit items-center gap-3 rounded-2xl px-4 py-3 text-sm transition-colors",
                        active
                          ? "bg-primary text-primary-foreground"
                          : "text-muted-foreground hover:bg-muted hover:text-foreground",
                      )}
                    >
                      <Icon className="size-4" />
                      <span>{item.label}</span>
                    </Link>
                  )
                })}
              </div>
            </nav>
          </aside>

          <div className="space-y-6">
            {showShellStatusNotice ? (
              <AccountStatusNotice user={session.user} locale={locale} />
            ) : null}
            {children}
          </div>
        </div>
      </div>
    </div>
  )
}

export function AccountShell({ children, locale }: AccountShellProps) {
  return <AccountShellContent locale={locale}>{children}</AccountShellContent>
}
