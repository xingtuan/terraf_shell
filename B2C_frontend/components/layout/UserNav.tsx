"use client"

import { useState } from "react"
import { useRouter } from "next/navigation"

import { CommunityAuthPanel } from "@/components/community/community-auth-panel"
import { CommunityUserAvatar } from "@/components/community/CommunityUserAvatar"
import { Button } from "@/components/ui/button"
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogTitle,
} from "@/components/ui/dialog"
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
  const session = useAuthSession()
  const authCopy = getMessages(locale).community.auth
  const [isAuthOpen, setIsAuthOpen] = useState(false)

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
              <p className="text-xs text-muted-foreground">
                @{session.user.username}
              </p>
            </div>
          </button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
          <DropdownMenuItem
            onSelect={() => {
              router.push(
                getLocalizedHref(
                  locale,
                  `community/u/${session.user?.username ?? ""}`,
                ),
              )
            }}
          >
            My Account
          </DropdownMenuItem>
          <DropdownMenuItem
            onSelect={() => {
              router.push(getLocalizedHref(locale, "store/orders"))
            }}
          >
            My Orders
          </DropdownMenuItem>
          <DropdownMenuItem
            onSelect={() => {
              void session.logout()
            }}
          >
            Sign Out
          </DropdownMenuItem>
        </DropdownMenuContent>
      </DropdownMenu>
    )
  }

  return (
    <>
      <Button type="button" variant="outline" onClick={() => setIsAuthOpen(true)}>
        Sign In
      </Button>

      <Dialog open={isAuthOpen} onOpenChange={setIsAuthOpen}>
        <DialogContent className="max-w-2xl border-none bg-transparent p-0 shadow-none">
          <DialogTitle className="sr-only">Sign In</DialogTitle>
          <DialogDescription className="sr-only">
            Sign in with the shared Shellfin account used across community and store flows.
          </DialogDescription>
          <CommunityAuthPanel
            copy={authCopy}
            user={session.user}
            isReady={session.isReady}
            isLoadingUser={session.isLoadingUser}
            context="community"
            onSuccess={() => setIsAuthOpen(false)}
            onLogin={session.login}
            onRegister={session.register}
            onLogout={session.logout}
            onRefresh={session.refreshUser}
          />
        </DialogContent>
      </Dialog>
    </>
  )
}
