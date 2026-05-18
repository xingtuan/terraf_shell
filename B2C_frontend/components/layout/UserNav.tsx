"use client"

import { useRouter, usePathname } from "next/navigation"

import { CommunityUserAvatar } from "@/components/community/CommunityUserAvatar"
import { Button } from "@/components/ui/button"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import { getLocalizedHref, getMessages, type Locale } from "@/lib/i18n"
import { useAuthSession } from "@/hooks/use-auth-session"

type UserNavProps = {
  locale: Locale
}

export function UserNav({ locale }: UserNavProps) {
  const router = useRouter()
  const pathname = usePathname()
  const session = useAuthSession()
  const messages = getMessages(locale)
  const t = messages.userNav

  if (session.user) {
    return (
      <DropdownMenu>
        <DropdownMenuTrigger asChild>
          <button
            type="button"
            className="flex items-center gap-3 rounded-full border border-border/60 bg-card px-3 py-2 text-left transition-colors hover:bg-muted"
          >
            <CommunityUserAvatar
              user={session.user}
              className="size-9 border border-border/60"
              sizes="36px"
            />
            <div className="hidden sm:block">
              <p className="text-sm font-medium text-foreground">
                {session.user.name}
              </p>
            </div>
          </button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
          <DropdownMenuItem
            onSelect={() => {
              router.push(getLocalizedHref(locale, "account"))
            }}
          >
            {t.myAccount}
          </DropdownMenuItem>
          <DropdownMenuItem
            onSelect={() => {
              router.push(getLocalizedHref(locale, "account/orders"))
            }}
          >
            {t.myOrders}
          </DropdownMenuItem>
          <DropdownMenuItem
            onSelect={() => {
              void session.logout()
            }}
          >
            {t.signOut}
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    )
  }

  const loginHref = `${getLocalizedHref(locale, "auth/login")}${pathname && !pathname.includes("/auth/") ? `?next=${encodeURIComponent(pathname)}` : ""}`

  return (
    <Button asChild variant="outline">
      <a href={loginHref}>{t.signIn}</a>
    </Button>
  )
}
