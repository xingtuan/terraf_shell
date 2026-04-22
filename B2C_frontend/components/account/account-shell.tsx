"use client"

import type { ComponentType, ReactNode } from "react"
import Link from "next/link"
import { usePathname } from "next/navigation"
import {
  LayoutDashboard,
  MapPinHouse,
  MessageSquareText,
  Package,
  Settings,
  ShoppingBag,
  UserRound,
} from "lucide-react"

import { AuthGate } from "@/components/auth/AuthGate"
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
  const session = useAuthSession()
  const pathname = usePathname()
  const copy = getAccountCopy(locale)

  if (!session.user) {
    return null
  }

  const items: NavItem[] = [
    {
      href: getLocalizedHref(locale, "account"),
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
    {
      href: getLocalizedHref(locale, "account/settings"),
      icon: Settings,
      label: copy.nav.settings,
    },
  ]

  const publicProfileHref = getLocalizedHref(
    locale,
    `community/u/${session.user.username}`,
  )

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
                  @{session.user.username}
                </p>
                <p className="mt-1 text-sm text-muted-foreground">
                  {copy.shell.signedInAs.replace(
                    "{email}",
                    session.user.email ?? "Shellfin account",
                  )}
                </p>
              </div>
              <Button asChild variant="outline" className="sm:self-start">
                <Link href={publicProfileHref}>{copy.shell.publicProfile}</Link>
              </Button>
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

          <div className="space-y-6">{children}</div>
        </div>
      </div>
    </div>
  )
}

export function AccountShell({ children, locale }: AccountShellProps) {
  const pathname = usePathname()

  return (
    <AuthGate
      locale={locale}
      redirectAfterLogin={pathname || getLocalizedHref(locale, "account")}
    >
      <AccountShellContent locale={locale}>{children}</AccountShellContent>
    </AuthGate>
  )
}
