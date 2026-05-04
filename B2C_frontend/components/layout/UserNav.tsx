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
  const messages = getMessages(locale)
  const authCopy = messages.community.auth
  const t = messages.userNav
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

  return (
    <>
      <Button type="button" variant="outline" onClick={() => setIsAuthOpen(true)}>
        {t.signIn}
      </Button>

      <Dialog open={isAuthOpen} onOpenChange={setIsAuthOpen}>
        <DialogContent className="max-w-2xl border-none bg-transparent p-0 shadow-none">
          <DialogTitle className="sr-only">{t.dialogTitle}</DialogTitle>
          <DialogDescription className="sr-only">
            {t.dialogDescription}
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
