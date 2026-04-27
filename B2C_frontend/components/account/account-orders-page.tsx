"use client"

import { useEffect, useMemo, useState } from "react"
import Image from "next/image"
import Link from "next/link"

import { Button } from "@/components/ui/button"
import { getAccountCopy } from "@/lib/account-copy"
import { getOrders, cancelOrder } from "@/lib/api/orders"
import { getErrorMessage } from "@/lib/api/client"
import { formatCurrencyAmount } from "@/lib/api/products"
import { getLocalizedHref, getMessages, type Locale } from "@/lib/i18n"
import type { StoreOrder } from "@/lib/types"
import { useAuthSession } from "@/hooks/use-auth-session"
import {
  AccountEmptyState,
  AccountPageHeader,
  AccountPanel,
  AccountStatCard,
} from "@/components/account/account-ui"
import {
  formatAccountDate,
  getOrderStatusClasses,
} from "@/components/account/account-utils"

type AccountOrdersPageProps = {
  locale: Locale
}

export function AccountOrdersPage({ locale }: AccountOrdersPageProps) {
  const session = useAuthSession()
  const copy = getAccountCopy(locale)
  const messages = getMessages(locale).ordersPage
  const [orders, setOrders] = useState<StoreOrder[]>([])
  const [page, setPage] = useState(1)
  const [lastPage, setLastPage] = useState(1)
  const [totalOrders, setTotalOrders] = useState(0)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    const token = session.token

    if (!token) {
      setLoading(false)
      return
    }

    let cancelled = false

    async function loadOrders() {
      setLoading(true)
      setError(null)

      try {
        const response = await getOrders(token, page, 10)
        if (cancelled) return
        setOrders(response.items)
        setLastPage(response.meta.last_page)
        setTotalOrders(response.meta.total)
      } catch (loadError) {
        if (!cancelled) setError(getErrorMessage(loadError))
      } finally {
        if (!cancelled) setLoading(false)
      }
    }

    void loadOrders()

    return () => {
      cancelled = true
    }
  }, [session.token, page])

  const activeOrders = useMemo(
    () =>
      orders.filter(
        (order) => order.status !== "delivered" && order.status !== "cancelled",
      ).length,
    [orders],
  )
  const pendingOrders = useMemo(
    () => orders.filter((order) => order.status === "pending").length,
    [orders],
  )

  async function handleCancel(orderNumber: string) {
    if (!session.token) {
      return
    }

    if (!window.confirm(messages.cancelConfirm)) {
      return
    }

    try {
      const updatedOrder = await cancelOrder(orderNumber, session.token)
      setOrders((currentOrders) =>
        currentOrders.map((order) =>
          order.order_number === orderNumber ? updatedOrder : order,
        ),
      )
    } catch (cancelError) {
      setError(getErrorMessage(cancelError))
    }
  }

  return (
    <AccountPanel>
      <AccountPageHeader
        eyebrow={copy.orders.eyebrow}
        title={copy.orders.title}
        description={copy.orders.description}
        actions={
          <Button asChild>
            <Link href={getLocalizedHref(locale, "store")}>
              {messages.browseCollection}
            </Link>
          </Button>
        }
      />

      {error ? (
        <div className="mt-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
          {error}
        </div>
      ) : null}

      <div className="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <AccountStatCard
          label={copy.orders.totalOrders}
          value={totalOrders}
          detail={copy.orders.title}
        />
        <AccountStatCard
          label={copy.orders.activeOrders}
          value={activeOrders}
          detail={messages.pageOf
            .replace("{page}", String(page))
            .replace("{lastPage}", String(lastPage))}
        />
        <AccountStatCard
          label={copy.orders.pendingOrders}
          value={pendingOrders}
          detail={messages.cancel}
        />
      </div>

      {loading ? (
        <div className="mt-8 rounded-[1.5rem] border border-border/60 bg-background/70 p-6 text-sm text-muted-foreground">
          {messages.loading}
        </div>
      ) : null}

      {!loading && orders.length === 0 ? (
        <div className="mt-8">
          <AccountEmptyState
            title={messages.emptyTitle}
            description={messages.emptyDescription}
            action={
              <Button asChild>
                <Link href={getLocalizedHref(locale, "store")}>
                  {messages.browseCollection}
                </Link>
              </Button>
            }
          />
        </div>
      ) : null}

      <div className="mt-8 space-y-5">
        {orders.map((order) => (
          <article
            key={order.order_number}
            className="rounded-[2rem] border border-border/60 bg-background/70 p-6"
          >
            <div className="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
              <div>
                <div className="flex flex-wrap items-center gap-3">
                  <h2 className="font-serif text-2xl text-foreground">
                    {order.order_number}
                  </h2>
                  <span
                    className={`rounded-full px-3 py-1 text-xs uppercase tracking-[0.18em] ${getOrderStatusClasses(order.status)}`}
                  >
                    {order.status}
                  </span>
                </div>
                <p className="mt-3 text-sm text-muted-foreground">
                  {formatAccountDate(locale, order.created_at) ?? messages.pendingDate}
                </p>
              </div>

              <div className="flex flex-wrap items-center gap-3">
                <div className="flex items-center gap-2">
                  {order.items.slice(0, 3).map((item) => (
                    <div
                      key={`${order.order_number}-${item.product_id}`}
                      className="relative h-10 w-10 overflow-hidden rounded-full border border-border/60 bg-muted"
                    >
                      <Image
                        src={
                          item.product?.primary_image_url ||
                          item.product?.image_url ||
                          "/placeholder.jpg"
                        }
                        alt={item.product_name}
                        fill
                        className="object-cover"
                      />
                    </div>
                  ))}
                  {order.items.length > 3 ? (
                    <span className="text-sm text-muted-foreground">
                      {messages.moreItems.replace(
                        "{count}",
                        String(order.items.length - 3),
                      )}
                    </span>
                  ) : null}
                </div>
                <p className="text-sm font-medium text-foreground">
                  {formatCurrencyAmount(order.total_usd, locale)}
                </p>
              </div>
            </div>

            <div className="mt-6 flex flex-wrap gap-3">
              <Button asChild variant="outline">
                <Link
                  href={getLocalizedHref(
                    locale,
                    `account/orders/${order.order_number}`,
                  )}
                >
                  {messages.viewDetails}
                </Link>
              </Button>
              {order.status === "pending" ? (
                <Button
                  type="button"
                  variant="ghost"
                  onClick={() => {
                    void handleCancel(order.order_number)
                  }}
                >
                  {messages.cancel}
                </Button>
              ) : null}
            </div>
          </article>
        ))}
      </div>

      {lastPage > 1 ? (
        <div className="mt-8 flex items-center justify-between">
          <Button
            type="button"
            variant="outline"
            disabled={page <= 1}
            onClick={() => setPage((currentPage) => Math.max(1, currentPage - 1))}
          >
            {messages.previous}
          </Button>
          <p className="text-sm text-muted-foreground">
            {messages.pageOf
              .replace("{page}", String(page))
              .replace("{lastPage}", String(lastPage))}
          </p>
          <Button
            type="button"
            variant="outline"
            disabled={page >= lastPage}
            onClick={() =>
              setPage((currentPage) => Math.min(lastPage, currentPage + 1))
            }
          >
            {messages.next}
          </Button>
        </div>
      ) : null}
    </AccountPanel>
  )
}
