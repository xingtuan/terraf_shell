"use client"

import { use, useEffect, useState } from "react"
import Image from "next/image"
import Link from "next/link"

import { AuthGate } from "@/components/auth/AuthGate"
import { Button } from "@/components/ui/button"
import { cancelOrder, getOrders } from "@/lib/api/orders"
import { getErrorMessage } from "@/lib/api/client"
import { getLocalizedHref, isValidLocale, type Locale } from "@/lib/i18n"
import type { StoreOrder } from "@/lib/types"
import { useAuthSession } from "@/hooks/use-auth-session"

type OrdersPageProps = {
  params: Promise<{ locale: string }>
}

function statusClasses(status: StoreOrder["status"]) {
  switch (status) {
    case "confirmed":
      return "bg-sky-100 text-sky-700"
    case "processing":
      return "bg-amber-100 text-amber-700"
    case "shipped":
      return "bg-violet-100 text-violet-700"
    case "delivered":
      return "bg-emerald-100 text-emerald-700"
    case "cancelled":
      return "bg-red-100 text-red-700"
    default:
      return "bg-muted text-foreground"
  }
}

function OrdersScreen({ locale }: { locale: Locale }) {
  const session = useAuthSession()
  const [orders, setOrders] = useState<StoreOrder[]>([])
  const [page, setPage] = useState(1)
  const [lastPage, setLastPage] = useState(1)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    if (!session.token) {
      return
    }

    setLoading(true)
    setError(null)

    void getOrders(session.token, page, 10)
      .then((response) => {
        setOrders(response.items)
        setLastPage(response.meta.last_page)
      })
      .catch((nextError) => {
        setError(getErrorMessage(nextError))
      })
      .finally(() => {
        setLoading(false)
      })
  }, [page, session.token])

  async function handleCancel(orderNumber: string) {
    if (!session.token) {
      return
    }

    if (!window.confirm("Cancel this pending order?")) {
      return
    }

    try {
      const updatedOrder = await cancelOrder(orderNumber, session.token)
      setOrders((currentOrders) =>
        currentOrders.map((order) =>
          order.order_number === orderNumber ? updatedOrder : order,
        ),
      )
    } catch (nextError) {
      setError(getErrorMessage(nextError))
    }
  }

  return (
    <div className="mx-auto max-w-7xl px-6 py-16 lg:px-8">
      <div className="mb-10">
        <p className="text-sm uppercase tracking-[0.2em] text-primary">Orders</p>
        <h1 className="mt-3 font-serif text-4xl text-foreground">Order History</h1>
      </div>

      {error ? (
        <div className="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
          {error}
        </div>
      ) : null}

      {loading ? (
        <div className="rounded-[2rem] border border-border/60 bg-card p-10 text-sm text-muted-foreground">
          Loading orders...
        </div>
      ) : null}

      {!loading && orders.length === 0 ? (
        <div className="rounded-[2rem] border border-border/60 bg-card p-10 text-center">
          <h2 className="font-serif text-3xl text-foreground">No orders yet</h2>
          <p className="mt-4 text-muted-foreground">
            Browse the Shellfin collection and place your first order.
          </p>
          <Button asChild className="mt-6">
            <Link href={getLocalizedHref(locale, "store")}>Browse Collection</Link>
          </Button>
        </div>
      ) : null}

      <div className="space-y-5">
        {orders.map((order) => (
          <article
            key={order.order_number}
            className="rounded-[2rem] border border-border/60 bg-card p-6"
          >
            <div className="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
              <div>
                <div className="flex flex-wrap items-center gap-3">
                  <h2 className="font-serif text-2xl text-foreground">
                    {order.order_number}
                  </h2>
                  <span
                    className={`rounded-full px-3 py-1 text-xs uppercase tracking-[0.18em] ${statusClasses(order.status)}`}
                  >
                    {order.status}
                  </span>
                </div>
                <p className="mt-3 text-sm text-muted-foreground">
                  {order.created_at
                    ? new Date(order.created_at).toLocaleDateString()
                    : "Pending date"}
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
                        src={item.product?.image_url || "/placeholder.jpg"}
                        alt={item.product_name}
                        fill
                        className="object-cover"
                      />
                    </div>
                  ))}
                  {order.items.length > 3 ? (
                    <span className="text-sm text-muted-foreground">
                      +{order.items.length - 3} more
                    </span>
                  ) : null}
                </div>
                <p className="text-sm font-medium text-foreground">
                  ${Number(order.total_usd).toFixed(2)} USD
                </p>
              </div>
            </div>

            <div className="mt-6 flex flex-wrap gap-3">
              <Button asChild variant="outline">
                <Link
                  href={getLocalizedHref(
                    locale,
                    `store/orders/${order.order_number}`,
                  )}
                >
                  View Details
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
                  Cancel
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
            onClick={() => setPage((currentValue) => Math.max(1, currentValue - 1))}
          >
            Previous
          </Button>
          <p className="text-sm text-muted-foreground">
            Page {page} of {lastPage}
          </p>
          <Button
            type="button"
            variant="outline"
            disabled={page >= lastPage}
            onClick={() =>
              setPage((currentValue) => Math.min(lastPage, currentValue + 1))
            }
          >
            Next
          </Button>
        </div>
      ) : null}
    </div>
  )
}

export default function OrdersPage({ params }: OrdersPageProps) {
  const resolvedParams = use(params)
  const locale = isValidLocale(resolvedParams.locale) ? resolvedParams.locale : "en"
  const ordersHref = getLocalizedHref(locale, "store/orders")

  return (
    <AuthGate locale={locale} redirectAfterLogin={ordersHref}>
      <OrdersScreen locale={locale} />
    </AuthGate>
  )
}
