"use client"

import Link from "next/link"
import { useEffect, useState } from "react"
import { Flag } from "lucide-react"

import { Button } from "@/components/ui/button"
import { getErrorMessage } from "@/lib/api/client"
import {
  listNotifications,
  markNotificationRead,
} from "@/lib/api/notifications"
import { getMessages, type Locale } from "@/lib/i18n"
import {
  getNotificationBody,
  getNotificationExcerpt,
  getNotificationTitle,
  isSystemAnnouncement,
} from "@/lib/notification-display"
import { resolveNotificationHref } from "@/lib/notification-href"
import type { UserNotification } from "@/lib/types"

type CommunityNotificationsPanelProps = {
  locale: Locale
  token?: string | null
}

function notificationIcon(type: string) {
  if (type.startsWith("report_")) {
    return Flag
  }

  return null
}

export function CommunityNotificationsPanel({
  locale,
  token,
}: CommunityNotificationsPanelProps) {
  const siteMessages = getMessages(locale)
  const t = siteMessages.community.notifications
  const [notifications, setNotifications] = useState<UserNotification[]>([])
  const [message, setMessage] = useState<string | null>(null)
  const [isLoading, setIsLoading] = useState(false)
  const [unreadCount, setUnreadCount] = useState(0)
  const [expandedAnnouncementIds, setExpandedAnnouncementIds] = useState<
    Set<number>
  >(() => new Set())

  useEffect(() => {
    if (!token) {
      setNotifications([])
      setUnreadCount(0)
      setExpandedAnnouncementIds(new Set())
      return
    }

    const activeToken = token

    let isCancelled = false

    async function loadNotifications() {
      setIsLoading(true)
      setMessage(null)

      try {
        const response = await listNotifications(activeToken, { per_page: 5 })

        if (isCancelled) {
          return
        }

        setNotifications(response.items)
        setUnreadCount(response.meta.unread_count ?? 0)
      } catch (error) {
        if (!isCancelled) {
          setMessage(getErrorMessage(error))
        }
      } finally {
        if (!isCancelled) {
          setIsLoading(false)
        }
      }
    }

    void loadNotifications()

    return () => {
      isCancelled = true
    }
  }, [token])

  if (!token) {
    return null
  }

  return (
    <div className="rounded-3xl border border-border/60 bg-card p-7">
      <div className="flex items-start justify-between gap-4">
        <div>
          <p className="text-sm uppercase tracking-[0.18em] text-primary">
            {t.title}
          </p>
          <p className="mt-3 text-sm text-muted-foreground">
            {t.unread.replace("{count}", String(unreadCount))}
          </p>
        </div>
        <Button
          type="button"
          variant="ghost"
          size="sm"
          disabled={isLoading}
          onClick={() => {
            setNotifications([])
            setUnreadCount(0)
            setMessage(null)
            setExpandedAnnouncementIds(new Set())
          }}
        >
          Clear view
        </Button>
      </div>

      {message ? (
        <div className="mt-5 rounded-2xl bg-primary/8 px-4 py-3 text-sm text-foreground">
          {message}
        </div>
      ) : null}

      {isLoading ? (
        <div className="mt-5 text-sm text-muted-foreground">
          {siteMessages.common.loading.notifications}
        </div>
      ) : null}

      {!isLoading && notifications.length === 0 ? (
        <div className="mt-5 rounded-2xl bg-background px-4 py-5">
          <p className="text-sm font-medium text-foreground">
            {siteMessages.common.empty.notifications.title}
          </p>
          <p className="mt-1 text-sm text-muted-foreground">
            {siteMessages.common.empty.notifications.description}
          </p>
        </div>
      ) : null}

      <div className="mt-5 space-y-3">
        {notifications.map((notification) => {
          const announcement = isSystemAnnouncement(notification)
          const body = getNotificationBody(notification)
          const excerpt = getNotificationExcerpt(notification, 220)
          const compactBodyLength = body?.replace(/\s+/g, " ").trim().length ?? 0
          const isLongAnnouncement = announcement && compactBodyLength > 220
          const isExpanded = expandedAnnouncementIds.has(notification.id)
          const displayBody = announcement
            ? isLongAnnouncement && !isExpanded
              ? excerpt
              : body
            : notification.body
          const href =
            announcement && !notification.action_url
              ? null
              : resolveNotificationHref(locale, notification)

          return (
            <div
              key={notification.id}
              className="rounded-2xl bg-background px-4 py-4"
            >
              <div className="flex items-start justify-between gap-4">
                <div className="flex min-w-0 gap-3">
                  {(() => {
                    const Icon = notificationIcon(notification.type)

                    return Icon ? (
                      <span className="mt-0.5 rounded-full bg-muted p-2 text-muted-foreground">
                        <Icon className="size-4" />
                      </span>
                    ) : null
                  })()}
                  <div className="min-w-0">
                    <p className="font-medium text-foreground">
                      {announcement
                        ? getNotificationTitle(notification, t.announcement)
                        : notification.title || t.announcement}
                    </p>
                    {displayBody ? (
                      <p
                        className={`mt-2 text-sm leading-relaxed text-muted-foreground ${
                          announcement ? "whitespace-pre-line" : ""
                        }`}
                      >
                        {displayBody}
                      </p>
                    ) : null}
                    {isLongAnnouncement ? (
                      <button
                        type="button"
                        className="mt-2 text-xs font-medium text-primary"
                        onClick={() => {
                          setExpandedAnnouncementIds((currentIds) => {
                            const nextIds = new Set(currentIds)

                            if (nextIds.has(notification.id)) {
                              nextIds.delete(notification.id)
                            } else {
                              nextIds.add(notification.id)
                            }

                            return nextIds
                          })
                        }}
                      >
                        {isExpanded ? t.collapse : t.expand}
                      </button>
                    ) : null}
                  </div>
                </div>
                {!notification.is_read ? (
                  <button
                    type="button"
                    className="rounded-full border border-border px-3 py-1 text-xs text-muted-foreground"
                    onClick={() => {
                      if (!token) {
                        return
                      }

                      void markNotificationRead(notification.id, token)
                        .then((updatedNotification) => {
                          setNotifications((currentNotifications) =>
                            currentNotifications.map((currentNotification) =>
                              currentNotification.id === notification.id
                                ? updatedNotification
                                : currentNotification,
                            ),
                          )
                          setUnreadCount((currentCount) =>
                            Math.max(0, currentCount - 1),
                          )
                        })
                        .catch((error) => {
                          setMessage(getErrorMessage(error))
                        })
                    }}
                  >
                    {t.markAllRead}
                  </button>
                ) : null}
              </div>

              {href ? (
                <div className="mt-4">
                  <Button asChild variant="ghost" size="sm" className="px-0">
                    <Link href={href}>{t.open}</Link>
                  </Button>
                </div>
              ) : null}
            </div>
          )
        })}
      </div>
    </div>
  )
}
