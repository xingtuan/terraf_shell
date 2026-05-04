"use client"

import Link from "next/link"
import { useEffect, useState } from "react"

import { Button } from "@/components/ui/button"
import { getErrorMessage } from "@/lib/api/client"
import {
  listNotifications,
  markNotificationRead,
} from "@/lib/api/notifications"
import { getLocalizedHref, getMessages, type Locale } from "@/lib/i18n"
import { resolveCmsHref } from "@/lib/page-content"
import type { UserNotification } from "@/lib/types"

type CommunityNotificationsPanelProps = {
  locale: Locale
  token?: string | null
}

function resolveNotificationHref(locale: Locale, notification: UserNotification) {
  if (notification.action_url) {
    return resolveCmsHref(locale, notification.action_url, getLocalizedHref(locale, "community"))
  }

  if (notification.target && "slug" in notification.target) {
    return getLocalizedHref(locale, `community/${notification.target.slug}`)
  }

  if (notification.target && "username" in notification.target) {
    return `${getLocalizedHref(locale, "community")}?user=${notification.target.id}`
  }

  return getLocalizedHref(locale, "community")
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

  useEffect(() => {
    if (!token) {
      setNotifications([])
      setUnreadCount(0)
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
        {notifications.map((notification) => (
          <div
            key={notification.id}
            className="rounded-2xl bg-background px-4 py-4"
          >
            <div className="flex items-start justify-between gap-4">
              <div>
                <p className="font-medium text-foreground">
                  {notification.title || t.announcement}
                </p>
                {notification.body ? (
                  <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
                    {notification.body}
                  </p>
                ) : null}
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

            <div className="mt-4">
              <Button asChild variant="ghost" size="sm" className="px-0">
                <Link href={resolveNotificationHref(locale, notification)}>
                  {t.open}
                </Link>
              </Button>
            </div>
          </div>
        ))}
      </div>
    </div>
  )
}
