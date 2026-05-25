"use client"

import { useEffect, useEffectEvent, useState } from "react"
import { Bell, Flag } from "lucide-react"
import { useRouter } from "next/navigation"

import {
  getNotifications,
  markAllNotificationsRead,
  markNotificationRead,
} from "@/lib/api/notifications"
import { getErrorMessage } from "@/lib/api/client"
import { formatCommunityDate, getCommunityUserName } from "@/lib/community-ui"
import { type Locale, type SiteMessages } from "@/lib/i18n"
import {
  getNotificationBody,
  getNotificationExcerpt,
  getNotificationTitle,
  isSystemAnnouncement,
} from "@/lib/notification-display"
import { resolveNotificationHref } from "@/lib/notification-href"
import type { UserNotification } from "@/lib/types"
import { Button } from "@/components/ui/button"
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from "@/components/ui/dialog"
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover"

type NotificationBellProps = {
  locale: Locale
  token?: string | null
  messages: SiteMessages["community"]["notifications"]
}

function extractQuotedText(value?: string | null) {
  if (!value) {
    return null
  }

  const match = value.match(/"([^"]+)"/)

  return match?.[1] ?? null
}

function formatNotificationMessage(
  notification: UserNotification,
  messages: SiteMessages["community"]["notifications"],
) {
  const actor = getCommunityUserName(notification.actor)
  const postTitle =
    (notification.target && "title" in notification.target
      ? notification.target.title
      : null) ?? extractQuotedText(notification.body)

  switch (notification.type) {
    case "comment":
      return postTitle
        ? messages.comment
            .replace("{actor}", actor)
            .replace("{post_title}", postTitle)
        : notification.body ?? messages.fallback.replace("{actor}", actor)
    case "reply":
      return messages.reply.replace("{actor}", actor)
    case "like":
      return postTitle
        ? messages.like
            .replace("{actor}", actor)
            .replace("{post_title}", postTitle)
        : messages.likeNoTitle.replace("{actor}", actor)
    case "follow":
      return messages.follow.replace("{actor}", actor)
    case "favorite":
      return postTitle
        ? messages.favorite
            .replace("{actor}", actor)
            .replace("{post_title}", postTitle)
        : messages.favoriteNoTitle.replace("{actor}", actor)
    case "report_received":
      return messages.reportReceived
    case "report_reviewed":
      return typeof notification.data?.public_note === "string"
        ? notification.data.public_note
        : messages.reportReviewed
    case "report_resolved":
      return typeof notification.data?.public_note === "string"
        ? notification.data.public_note
        : messages.reportResolved
    case "report_dismissed":
      return typeof notification.data?.public_note === "string"
        ? notification.data.public_note
        : messages.reportDismissed
    default:
      return notification.body ?? messages.announcement
  }
}

function notificationIcon(type: string) {
  if (type.startsWith("report_")) {
    return Flag
  }

  return null
}

export function NotificationBell({
  locale,
  token,
  messages,
}: NotificationBellProps) {
  const router = useRouter()
  const [open, setOpen] = useState(false)
  const [notifications, setNotifications] = useState<UserNotification[]>([])
  const [unreadCount, setUnreadCount] = useState(0)
  const [isLoading, setIsLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const [selectedAnnouncement, setSelectedAnnouncement] =
    useState<UserNotification | null>(null)

  const loadNotifications = useEffectEvent(async () => {
    if (!token) {
      setNotifications([])
      setUnreadCount(0)
      return
    }

    setIsLoading(true)
    setError(null)

    try {
      const response = await getNotifications({ per_page: 10 }, token)
      setNotifications(response.items)
      setUnreadCount(response.meta.unread_count ?? 0)
    } catch (loadError) {
      setError(getErrorMessage(loadError))
    } finally {
      setIsLoading(false)
    }
  })

  useEffect(() => {
    if (!token) {
      return
    }

    void loadNotifications()

    const intervalId = window.setInterval(() => {
      void loadNotifications()
    }, 60_000)

    return () => {
      window.clearInterval(intervalId)
    }
  }, [token])

  if (!token) {
    return null
  }

  const markReadThen = (
    notification: UserNotification,
    afterMark: (updatedNotification: UserNotification) => void,
  ) => {
    if (!notification.is_read && token) {
      void markNotificationRead(notification.id, token)
        .then((updatedNotification) => {
          setNotifications((currentNotifications) =>
            currentNotifications.map((currentNotification) =>
              currentNotification.id === notification.id
                ? updatedNotification
                : currentNotification,
            ),
          )
          setUnreadCount((currentCount) => Math.max(0, currentCount - 1))
          afterMark(updatedNotification)
        })
        .catch(() => {
          afterMark(notification)
        })

      return
    }

    afterMark(notification)
  }

  const navigateToNotification = (notification: UserNotification) => {
    setOpen(false)
    router.push(resolveNotificationHref(locale, notification))
  }

  return (
    <>
      <Popover open={open} onOpenChange={setOpen}>
        <PopoverTrigger asChild>
          <button
            type="button"
            className="relative rounded-full border border-border/60 bg-background p-2 text-foreground transition-colors hover:bg-muted"
            aria-label={messages.title}
          >
            <Bell className="size-5" />
            {unreadCount > 0 ? (
              <span className="absolute -right-1 -top-1 flex min-h-5 min-w-5 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-semibold text-white">
                {unreadCount > 9 ? "9+" : unreadCount}
              </span>
            ) : null}
          </button>
        </PopoverTrigger>
        <PopoverContent align="end" className="w-[24rem] p-0">
          <div className="flex items-center justify-between border-b border-border/60 px-4 py-3">
            <div>
              <p className="text-sm font-semibold text-foreground">
                {messages.title}
              </p>
              <p className="text-xs text-muted-foreground">
                {messages.unread.replace("{count}", String(unreadCount))}
              </p>
            </div>
            <Button
              type="button"
              variant="ghost"
              size="sm"
              disabled={unreadCount === 0}
              onClick={() => {
                if (!token) {
                  return
                }

                void markAllNotificationsRead(token)
                  .then(() => {
                    setNotifications((currentNotifications) =>
                      currentNotifications.map((notification) => ({
                        ...notification,
                        is_read: true,
                      })),
                    )
                    setUnreadCount(0)
                  })
                  .catch((markError) => {
                    setError(getErrorMessage(markError))
                  })
              }}
            >
              {messages.markAllRead}
            </Button>
          </div>

          {error ? (
            <div className="border-b border-border/60 px-4 py-3 text-sm text-destructive">
              {error}
            </div>
          ) : null}

          <div className="max-h-[28rem] overflow-y-auto p-2">
            {isLoading ? (
              <div className="px-3 py-4 text-sm text-muted-foreground">
                {messages.loading}
              </div>
            ) : notifications.length === 0 ? (
              <div className="px-3 py-4 text-sm text-muted-foreground">
                {messages.empty}
              </div>
            ) : (
              notifications.map((notification) => {
                const announcement = isSystemAnnouncement(notification)
                const excerpt = announcement
                  ? getNotificationExcerpt(notification)
                  : null

                return (
                  <button
                    key={notification.id}
                    type="button"
                    className="flex w-full items-start gap-3 rounded-2xl px-3 py-3 text-left transition-colors hover:bg-muted"
                    onClick={() => {
                      if (announcement) {
                        markReadThen(notification, (updatedNotification) => {
                          setSelectedAnnouncement(updatedNotification)
                          setOpen(false)
                        })
                        return
                      }

                      markReadThen(notification, navigateToNotification)
                    }}
                  >
                    {(() => {
                      const Icon = notificationIcon(notification.type)

                      return Icon ? (
                        <span className="mt-0.5 rounded-full bg-muted p-2 text-muted-foreground">
                          <Icon className="size-4" />
                        </span>
                      ) : null
                    })()}
                    <span
                      className={`mt-1 size-2 rounded-full ${
                        notification.is_read ? "bg-transparent" : "bg-red-500"
                      }`}
                    />
                    <div className="min-w-0 flex-1">
                      {announcement ? (
                        <>
                          <p className="text-sm font-medium text-foreground">
                            {getNotificationTitle(
                              notification,
                              messages.announcement,
                            )}
                          </p>
                          {excerpt ? (
                            <p className="mt-1 line-clamp-2 text-sm text-muted-foreground">
                              {excerpt}
                            </p>
                          ) : null}
                        </>
                      ) : (
                        <p className="text-sm text-foreground">
                          {formatNotificationMessage(notification, messages)}
                        </p>
                      )}
                      <p className="mt-1 text-xs text-muted-foreground">
                        {messages.open}
                      </p>
                    </div>
                  </button>
                )
              })
            )}
          </div>
        </PopoverContent>
      </Popover>

      <Dialog
        open={selectedAnnouncement !== null}
        onOpenChange={(dialogOpen) => {
          if (!dialogOpen) {
            setSelectedAnnouncement(null)
          }
        }}
      >
        <DialogContent className="max-h-[calc(100vh-2rem)] overflow-y-auto sm:max-w-xl">
          {selectedAnnouncement ? (
            (() => {
              const body = getNotificationBody(selectedAnnouncement)
              const createdAt = formatCommunityDate(
                locale,
                selectedAnnouncement.created_at,
              )
              const href = selectedAnnouncement.action_url
                ? resolveNotificationHref(locale, selectedAnnouncement)
                : null

              return (
                <>
                  <DialogHeader>
                    <DialogTitle>
                      {getNotificationTitle(
                        selectedAnnouncement,
                        messages.announcement,
                      )}
                    </DialogTitle>
                    <DialogDescription asChild>
                      <div className="space-y-4">
                        {body ? (
                          <p className="max-h-[50vh] overflow-y-auto whitespace-pre-line text-sm leading-relaxed text-muted-foreground">
                            {body}
                          </p>
                        ) : null}
                        {createdAt ? (
                          <p className="text-xs text-muted-foreground">
                            {createdAt}
                          </p>
                        ) : null}
                      </div>
                    </DialogDescription>
                  </DialogHeader>
                  {href ? (
                    <DialogFooter>
                      <Button
                        type="button"
                        onClick={() => {
                          setSelectedAnnouncement(null)
                          router.push(href)
                        }}
                      >
                        {messages.open}
                      </Button>
                    </DialogFooter>
                  ) : null}
                </>
              )
            })()
          ) : null}
        </DialogContent>
      </Dialog>
    </>
  )
}
