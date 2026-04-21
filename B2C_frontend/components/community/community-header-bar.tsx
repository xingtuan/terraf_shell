"use client"

import { Suspense, useState } from "react"

import { CommunityAuthPanel } from "@/components/community/community-auth-panel"
import { CommunitySearch } from "@/components/community/CommunitySearch"
import { CreatePostPanel } from "@/components/community/CreatePostPanel"
import { NotificationBell } from "@/components/community/NotificationBell"
import { Button } from "@/components/ui/button"
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogTitle,
} from "@/components/ui/dialog"
import { dispatchCommunityPostsRefresh } from "@/lib/community-events"
import { type Locale, type SiteMessages } from "@/lib/i18n"
import { useAuthSession } from "@/hooks/use-auth-session"

type CommunityHeaderBarProps = {
  locale: Locale
  messages: SiteMessages["community"]
}

export function CommunityHeaderBar({
  locale,
  messages,
}: CommunityHeaderBarProps) {
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
                {messages.layout.title}
              </p>
              <p className="mt-2 max-w-2xl text-sm text-muted-foreground">
                {messages.layout.description}
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
                <NotificationBell
                  locale={locale}
                  token={session.token}
                  messages={messages.notifications}
                />
              ) : null}
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
