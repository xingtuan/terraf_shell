"use client"

import { useEffect, useMemo, useState } from "react"
import Link from "next/link"

import { Button } from "@/components/ui/button"
import { getNotifications } from "@/lib/api/notifications"
import { getOrders } from "@/lib/api/orders"
import { listAddresses } from "@/lib/api/addresses"
import { getErrorMessage } from "@/lib/api/client"
import {
  getUserComments,
  getUserFavorites,
  getUserPosts,
  getUserProfile,
} from "@/lib/api/users"
import { getAccountCopy } from "@/lib/account-copy"
import { getLocalizedHref, type Locale } from "@/lib/i18n"
import type { Address, StoreOrder, UserNotification, UserProfile } from "@/lib/types"
import { useAuthSession } from "@/hooks/use-auth-session"
import { useCart } from "@/hooks/useCart"
import {
  AccountEmptyState,
  AccountPageHeader,
  AccountPanel,
  AccountStatCard,
} from "@/components/account/account-ui"
import {
  formatAccountDate,
  formatAddressSummary,
  getDefaultAddress,
  getOrderStatusClasses,
} from "@/components/account/account-utils"

type AccountOverviewPageProps = {
  locale: Locale
}

export function AccountOverviewPage({ locale }: AccountOverviewPageProps) {
  const session = useAuthSession()
  const { cart } = useCart()
  const copy = getAccountCopy(locale)
  const [profile, setProfile] = useState<UserProfile | null>(null)
  const [orders, setOrders] = useState<StoreOrder[]>([])
  const [totalOrders, setTotalOrders] = useState(0)
  const [addresses, setAddresses] = useState<Address[]>([])
  const [notifications, setNotifications] = useState<UserNotification[]>([])
  const [unreadNotifications, setUnreadNotifications] = useState(0)
  const [postsCount, setPostsCount] = useState(0)
  const [commentsCount, setCommentsCount] = useState(0)
  const [favoritesCount, setFavoritesCount] = useState(0)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    const token = session.token
    const username = session.user?.username

    if (!token || !username) {
      setLoading(false)
      return
    }

    const authToken: string = token
    const userIdentifier: string = username
    let cancelled = false

    async function loadOverview() {
      setLoading(true)
      setError(null)

      try {
        const [
          nextProfile,
          ordersResponse,
          nextAddresses,
          postsResponse,
          commentsResponse,
          favoritesResponse,
          notificationsResponse,
        ] = await Promise.all([
          getUserProfile(userIdentifier, authToken),
          getOrders(authToken, 1, 3),
          listAddresses(authToken),
          getUserPosts(userIdentifier, { per_page: 1 }, authToken),
          getUserComments(userIdentifier, { per_page: 1 }, authToken),
          getUserFavorites(userIdentifier, { per_page: 1 }, authToken),
          getNotifications({ per_page: 3 }, authToken),
        ])

        if (cancelled) return
        setProfile(nextProfile)
        setOrders(ordersResponse.items)
        setTotalOrders(ordersResponse.meta.total)
        setAddresses(nextAddresses)
        setPostsCount(postsResponse.meta.total)
        setCommentsCount(commentsResponse.meta.total)
        setFavoritesCount(favoritesResponse.meta.total)
        setNotifications(notificationsResponse.items)
        setUnreadNotifications(notificationsResponse.meta.unread_count ?? 0)
      } catch (loadError) {
        if (!cancelled) setError(getErrorMessage(loadError))
      } finally {
        if (!cancelled) setLoading(false)
      }
    }

    void loadOverview()

    return () => {
      cancelled = true
    }
  }, [session.token, session.user?.username])

  const defaultAddress = useMemo(() => getDefaultAddress(addresses), [addresses])
  const checklistItems = useMemo(
    () =>
      [
        !profile?.bio
          ? {
              label: copy.overview.addBioAction,
              href: getLocalizedHref(locale, "account/profile"),
            }
          : null,
        !defaultAddress
          ? {
              label: copy.overview.addDefaultAddressAction,
              href: getLocalizedHref(locale, "account/addresses"),
            }
          : null,
        totalOrders === 0
          ? {
              label: copy.overview.placeFirstOrderAction,
              href: getLocalizedHref(locale, "store"),
            }
          : null,
      ].filter((item): item is { label: string; href: string } => item !== null),
    [copy.overview.addBioAction, copy.overview.addDefaultAddressAction, copy.overview.placeFirstOrderAction, defaultAddress, locale, profile?.bio, totalOrders],
  )

  const publicProfileHref = session.user?.username
    ? getLocalizedHref(locale, `community/u/${session.user.username}`)
    : getLocalizedHref(locale, "community")

  return (
    <>
      <AccountPanel>
        <AccountPageHeader
          eyebrow={copy.overview.eyebrow}
          title={copy.overview.title}
          description={copy.overview.description}
          actions={
            <>
              <Button asChild variant="outline">
                <Link href={getLocalizedHref(locale, "account/profile")}>
                  {copy.overview.editProfile}
                </Link>
              </Button>
              <Button asChild>
                <Link href={publicProfileHref}>{copy.shell.publicProfile}</Link>
              </Button>
            </>
          }
        />

        {error ? (
          <div className="mt-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {error}
          </div>
        ) : null}

        {loading && !profile ? (
          <div className="mt-6 rounded-[1.5rem] border border-border/60 bg-background/70 p-6 text-sm text-muted-foreground">
            {copy.overview.loading}
          </div>
        ) : (
          <>
            <div className="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
              <AccountStatCard
                label={copy.overview.ordersLabel}
                value={totalOrders}
                detail={
                  orders[0]?.created_at
                    ? formatAccountDate(locale, orders[0].created_at)
                    : copy.overview.recentOrdersEmpty
                }
              />
              <AccountStatCard
                label={copy.overview.addressesLabel}
                value={addresses.length}
                detail={
                  defaultAddress
                    ? defaultAddress.label || defaultAddress.recipient_name
                    : copy.overview.defaultAddressEmpty
                }
              />
              <AccountStatCard
                label={copy.overview.postsLabel}
                value={postsCount}
                detail={`${commentsCount} comments`}
              />
              <AccountStatCard
                label={copy.overview.savedPostsLabel}
                value={favoritesCount}
                detail={copy.overview.manageCommunity}
              />
              <AccountStatCard
                label={copy.overview.cartLabel}
                value={cart?.item_count ?? 0}
                detail={copy.overview.resumeCart}
              />
              <AccountStatCard
                label={copy.overview.notificationsLabel}
                value={unreadNotifications}
                detail={
                  unreadNotifications > 0
                    ? copy.settings.unreadCount.replace(
                        "{count}",
                        String(unreadNotifications),
                      )
                    : copy.settings.noUnread
                }
              />
            </div>

            <div className="mt-8 grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
              <AccountPanel className="bg-background/70 p-6">
                <div className="flex items-end justify-between gap-4">
                  <div>
                    <p className="text-sm uppercase tracking-[0.18em] text-primary">
                      {copy.overview.recentOrdersTitle}
                    </p>
                    <h2 className="mt-3 font-serif text-3xl text-foreground">
                      {copy.overview.recentOrdersTitle}
                    </h2>
                  </div>
                  <Button asChild variant="outline">
                    <Link href={getLocalizedHref(locale, "account/orders")}>
                      {copy.overview.viewOrders}
                    </Link>
                  </Button>
                </div>

                <div className="mt-6 space-y-4">
                  {orders.length === 0 ? (
                    <AccountEmptyState
                      title={copy.overview.recentOrdersTitle}
                      description={copy.overview.recentOrdersEmpty}
                      action={
                        <Button asChild>
                          <Link href={getLocalizedHref(locale, "store")}>
                            {copy.overview.continueShopping}
                          </Link>
                        </Button>
                      }
                    />
                  ) : (
                    orders.map((order) => (
                      <div
                        key={order.order_number}
                        className="rounded-[1.5rem] border border-border/60 bg-card p-5"
                      >
                        <div className="flex flex-wrap items-center justify-between gap-4">
                          <div>
                            <p className="font-medium text-foreground">
                              {order.order_number}
                            </p>
                            <p className="mt-1 text-sm text-muted-foreground">
                              {formatAccountDate(locale, order.created_at)}
                            </p>
                          </div>
                          <span
                            className={`rounded-full px-3 py-1 text-xs uppercase tracking-[0.18em] ${getOrderStatusClasses(order.status)}`}
                          >
                            {order.status}
                          </span>
                        </div>
                        <div className="mt-4 flex flex-wrap gap-3">
                          <Button asChild variant="outline" size="sm">
                            <Link
                              href={getLocalizedHref(
                                locale,
                                `account/orders/${order.order_number}`,
                              )}
                            >
                              {copy.overview.viewOrders}
                            </Link>
                          </Button>
                        </div>
                      </div>
                    ))
                  )}
                </div>
              </AccountPanel>

              <div className="space-y-6">
                <AccountPanel className="bg-background/70 p-6">
                  <p className="text-sm uppercase tracking-[0.18em] text-primary">
                    {copy.overview.accountHealthTitle}
                  </p>
                  <h2 className="mt-3 font-serif text-3xl text-foreground">
                    {copy.overview.accountHealthTitle}
                  </h2>
                  <p className="mt-3 text-sm leading-relaxed text-muted-foreground">
                    {copy.overview.accountHealthDescription}
                  </p>

                  {checklistItems.length === 0 ? (
                    <p className="mt-6 rounded-[1.5rem] border border-emerald-200 bg-emerald-50 px-4 py-4 text-sm text-emerald-700">
                      {copy.overview.readyMessage}
                    </p>
                  ) : (
                    <div className="mt-6 space-y-3">
                      {checklistItems.map((item) => (
                        <Link
                          key={item.href}
                          href={item.href}
                          className="flex items-center justify-between rounded-[1.25rem] border border-border/60 bg-card px-4 py-4 text-sm text-foreground transition-colors hover:border-border hover:bg-background"
                        >
                          <span>{item.label}</span>
                          <span className="text-muted-foreground">→</span>
                        </Link>
                      ))}
                    </div>
                  )}

                  <div className="mt-8">
                    <p className="text-sm font-medium text-foreground">
                      {copy.overview.quickActionsTitle}
                    </p>
                    <div className="mt-4 flex flex-wrap gap-3">
                      <Button asChild variant="outline">
                        <Link href={getLocalizedHref(locale, "account/addresses")}>
                          {copy.overview.manageAddresses}
                        </Link>
                      </Button>
                      <Button asChild variant="outline">
                        <Link href={getLocalizedHref(locale, "account/community")}>
                          {copy.overview.manageCommunity}
                        </Link>
                      </Button>
                      <Button asChild variant="outline">
                        <Link href={getLocalizedHref(locale, "community")}>
                          {copy.overview.browseCommunity}
                        </Link>
                      </Button>
                      <Button asChild>
                        <Link href={getLocalizedHref(locale, "store")}>
                          {copy.overview.continueShopping}
                        </Link>
                      </Button>
                    </div>
                  </div>
                </AccountPanel>

                <AccountPanel className="bg-background/70 p-6">
                  <div className="flex items-end justify-between gap-4">
                    <div>
                      <p className="text-sm uppercase tracking-[0.18em] text-primary">
                        {copy.overview.defaultAddressTitle}
                      </p>
                      <h2 className="mt-3 font-serif text-3xl text-foreground">
                        {copy.overview.defaultAddressTitle}
                      </h2>
                    </div>
                    <Button asChild variant="outline">
                      <Link href={getLocalizedHref(locale, "account/addresses")}>
                        {copy.overview.manageAddresses}
                      </Link>
                    </Button>
                  </div>

                  {defaultAddress ? (
                    <div className="mt-6 rounded-[1.5rem] border border-border/60 bg-card p-5">
                      <p className="font-medium text-foreground">
                        {defaultAddress.label || defaultAddress.recipient_name}
                      </p>
                      <p className="mt-2 text-sm text-muted-foreground">
                        {defaultAddress.recipient_name}
                      </p>
                      <p className="mt-1 text-sm leading-relaxed text-muted-foreground">
                        {formatAddressSummary(defaultAddress)}
                      </p>
                    </div>
                  ) : (
                    <AccountEmptyState
                      title={copy.overview.defaultAddressTitle}
                      description={copy.overview.defaultAddressEmpty}
                    />
                  )}
                </AccountPanel>

                <AccountPanel className="bg-background/70 p-6">
                  <div className="flex items-end justify-between gap-4">
                    <div>
                      <p className="text-sm uppercase tracking-[0.18em] text-primary">
                        {copy.overview.latestNotificationsTitle}
                      </p>
                      <h2 className="mt-3 font-serif text-3xl text-foreground">
                        {copy.overview.latestNotificationsTitle}
                      </h2>
                    </div>
                    <Button asChild variant="outline">
                      <Link href={getLocalizedHref(locale, "community")}>
                        {copy.overview.browseCommunity}
                      </Link>
                    </Button>
                  </div>

                  <div className="mt-6 space-y-3">
                    {notifications.length === 0 ? (
                      <AccountEmptyState
                        title={copy.overview.latestNotificationsTitle}
                        description={copy.overview.latestNotificationsEmpty}
                      />
                    ) : (
                      notifications.map((notification) => (
                        <div
                          key={notification.id}
                          className="rounded-[1.5rem] border border-border/60 bg-card p-4"
                        >
                          <p className="text-sm text-foreground">
                            {notification.title ??
                              notification.body ??
                              copy.overview.notificationFallback}
                          </p>
                          <p className="mt-2 text-xs text-muted-foreground">
                            {formatAccountDate(locale, notification.created_at)}
                          </p>
                        </div>
                      ))
                    )}
                  </div>
                </AccountPanel>
              </div>
            </div>
          </>
        )}
      </AccountPanel>
    </>
  )
}
