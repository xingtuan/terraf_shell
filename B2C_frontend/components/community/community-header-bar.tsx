"use client"

import { Suspense, useState } from "react"
import { useRouter } from "next/navigation"

import { CommunityAuthPanel } from "@/components/community/community-auth-panel"
import { CommunitySearch } from "@/components/community/CommunitySearch"
import { CreatePostPanel } from "@/components/community/CreatePostPanel"
import { NotificationBell } from "@/components/community/NotificationBell"
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
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
import { dispatchCommunityPostsRefresh } from "@/lib/community-events"
import { getCommunityUserInitials } from "@/lib/community-ui"
import { getLocalizedHref, type Locale, type SiteMessages } from "@/lib/i18n"
import type { SiteSection } from "@/lib/types/content"
import { useAuthSession } from "@/hooks/use-auth-session"

type CommunityHeaderBarProps = {
  locale: Locale
  messages: SiteMessages["community"]
  heroSection?: SiteSection | null
}

export function CommunityHeaderBar({
  locale,
  messages,
  heroSection,
}: CommunityHeaderBarProps) {
  const router = useRouter()
  const session = useAuthSession()
  const currentUser = session.user
  const [isCreateOpen, setIsCreateOpen] = useState(false)
  const [isAuthOpen, setIsAuthOpen] = useState(false)

  return (
    <>
      <section className="sticky top-20 z-40 border-b border-border/60 bg-background/95 backdrop-blur-md">
        <div className="mx-auto flex max-w-7xl flex-col gap-4 px-6 py-4 lg:px-8">
          <div className="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            <div>
              <p className="text-sm uppercase tracking-[0.2em] text-primary">
                {heroSection?.title ?? messages.layout.title}
              </p>
              <p className="mt-2 max-w-2xl text-sm text-muted-foreground">
                {heroSection?.subtitle ?? messages.layout.description}
              </p>
            </div>

            <div className="flex flex-col gap-3 sm:flex-row sm:items-center">
              <Suspense
                fallback={<div className="h-10 w-full sm:max-w-md" aria-hidden="true" />}
              >
                <CommunitySearch locale={locale} messages={messages.search} />
              </Suspense>
              <Button
                type="button"
                onClick={() => {
                  if (currentUser) {
                    setIsCreateOpen(true)
                    return
                  }

                  setIsAuthOpen(true)
                }}
              >
                {messages.layout.shareButton}
              </Button>

              {currentUser ? (
                <div className="flex items-center gap-3">
                  <NotificationBell
                    locale={locale}
                    token={session.token}
                    messages={messages.notifications}
                  />
                  <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                      <button
                        type="button"
                        className="flex items-center gap-3 rounded-full border border-border/60 bg-card px-3 py-2 text-left transition-colors hover:bg-muted"
                      >
                        <Avatar className="size-9 border border-border/60">
                          <AvatarImage src={currentUser.avatar_url ?? undefined} />
                          <AvatarFallback>
                            {getCommunityUserInitials(currentUser)}
                          </AvatarFallback>
                        </Avatar>
                        <div className="hidden sm:block">
                          <p className="text-sm font-medium text-foreground">
                            {currentUser.name}
                          </p>
                          <p className="text-xs text-muted-foreground">
                            @{currentUser.username}
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
                              `community/profile/${currentUser.username}`,
                            ),
                          )
                        }}
                      >
                        {messages.layout.viewProfile}
                      </DropdownMenuItem>
                      <DropdownMenuItem
                        onSelect={() => {
                          void session.logout()
                        }}
                      >
                        {messages.layout.logout}
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </div>
              ) : (
                <Button
                  type="button"
                  variant="outline"
                  onClick={() => setIsAuthOpen(true)}
                >
                  {messages.layout.signIn}
                </Button>
              )}
            </div>
          </div>
        </div>
      </section>

      <CreatePostPanel
        locale={locale}
        messages={messages}
        token={session.token}
        open={isCreateOpen}
        onOpenChange={setIsCreateOpen}
        onSuccess={() => {
          dispatchCommunityPostsRefresh()
        }}
      />

      <Dialog open={isAuthOpen} onOpenChange={setIsAuthOpen}>
        <DialogContent className="max-w-2xl border-none bg-transparent p-0 shadow-none">
          <DialogTitle className="sr-only">{messages.layout.signIn}</DialogTitle>
          <DialogDescription className="sr-only">
            {messages.auth.description}
          </DialogDescription>
          <CommunityAuthPanel
            copy={messages.auth}
            user={session.user}
            isReady={session.isReady}
            isLoadingUser={session.isLoadingUser}
            onLogin={async (payload) => {
              const user = await session.login(payload)
              setIsAuthOpen(false)
              return user
            }}
            onRegister={async (payload) => {
              const user = await session.register(payload)
              setIsAuthOpen(false)
              return user
            }}
            onLogout={session.logout}
            onRefresh={session.refreshUser}
          />
        </DialogContent>
      </Dialog>
    </>
  )
}
