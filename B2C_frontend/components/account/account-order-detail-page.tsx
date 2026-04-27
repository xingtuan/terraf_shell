"use client"

import { useEffect, useState } from "react"
import Image from "next/image"
import Link from "next/link"

import { Button } from "@/components/ui/button"
import { getAccountCopy } from "@/lib/account-copy"
import { cancelOrder, getOrder } from "@/lib/api/orders"
import { getErrorMessage } from "@/lib/api/client"
import { formatCurrencyAmount } from "@/lib/api/products"
import { getLocalizedHref, getMessages, type Locale } from "@/lib/i18n"
import type { StoreOrder } from "@/lib/types"
import { useAuthSession } from "@/hooks/use-auth-session"
import {
  AccountPageHeader,
  AccountPanel,
  AccountStatCard,
} from "@/components/account/account-ui"
import {
  formatAccountDate,
  formatAddressSummary,
  getOrderStatusClasses,
} from "@/components/account/account-utils"

type AccountOrderDetailPageProps = {
  locale: Locale
  orderNumber: string
}

export function AccountOrderDetailPage({
  locale,
  orderNumber,
}: AccountOrderDetailPageProps) {
  const session = useAuthSession()
  const copy = getAccountCopy(locale)
  const messages = getMessages(locale).ordersPage
  const [order, setOrder] = useState<StoreOrder | null>(null)
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [copyState, setCopyState] = useState(false)

  useEffect(() => {
    const token = session.token

    if (!token) {
      setLoading(false)
      return
    }

    let cancelled = false

    async function loadOrder() {
      setLoading(true)
      setError(null)

      try {
        const nextOrder = await getOrder(orderNumber, token)
        if (cancelled) return
        setOrder(nextOrder)
      } catch (loadError) {
        if (!cancelled) setError(getErrorMessage(loadError))
      } finally {
        if (!cancelled) setLoading(false)
      }
    }

    void loadOrder()

    return () => {
      cancelled = true
    }
  }, [session.token, orderNumber])

  async function handleCancel() {
    if (!session.token || !order) {
      return
    }

    if (!window.confirm(messages.cancelConfirm)) {
      return
    }

    try {
      const updatedOrder = await cancelOrder(order.order_number, session.token)
      setOrder(updatedOrder)
    } catch (cancelError) {
      setError(getErrorMessage(cancelError))
    }
  }

  return (
    <AccountPanel>
      <AccountPageHeader
        eyebrow={copy.orders.eyebrow}
        title={order?.order_number ?? orderNumber}
        description={copy.orders.description}
        actions={
          <>
            <Button asChild variant="outline">
              <Link href={getLocalizedHref(locale, "account/orders")}>
                {copy.orders.backToOrders}
              </Link>
            </Button>
            <Button asChild>
              <Link href={getLocalizedHref(locale, "store")}>
                {copy.orders.continueShopping}
              </Link>
            </Button>
          </>
        }
      />

      {error ? (
        <div className="mt-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
          {error}
        </div>
      ) : null}

      {loading || !order ? (
        <div className="mt-8 rounded-[1.5rem] border border-border/60 bg-background/70 p-6 text-sm text-muted-foreground">
          {copy.orders.loadingDetail}
        </div>
      ) : (
        <>
          <div className="mt-8 flex flex-wrap items-center gap-3 rounded-[1.5rem] border border-border/60 bg-background/70 p-5">
            <p className="text-sm text-muted-foreground">
              {copy.orders.orderNumberLabel}
            </p>
            <p className="text-lg font-medium text-foreground">{order.order_number}</p>
            <Button
              type="button"
              variant="outline"
              size="sm"
              onClick={() => {
                void navigator.clipboard.writeText(order.order_number)
                setCopyState(true)
                window.setTimeout(() => setCopyState(false), 1200)
              }}
            >
              {copyState ? copy.orders.copied : copy.orders.copyNumber}
            </Button>
            <span
              className={`rounded-full px-3 py-1 text-xs uppercase tracking-[0.18em] ${getOrderStatusClasses(order.status)}`}
            >
              {order.status}
            </span>
          </div>

          <div className="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <AccountStatCard
              label={messages.viewDetails}
              value={order.items.length}
              detail={copy.orders.itemsTitle}
            />
            <AccountStatCard
              label={copy.orders.totalTitle}
              value={formatCurrencyAmount(order.total_usd, locale)}
              detail={formatAccountDate(locale, order.created_at)}
            />
            <AccountStatCard
              label={copy.orders.shippingTitle}
              value={order.shipping_address.country}
              detail={order.shipping_address.city}
            />
          </div>

          <div className="mt-8 grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
            <AccountPanel className="bg-background/70 p-6">
              <p className="text-sm uppercase tracking-[0.18em] text-primary">
                {copy.orders.itemsTitle}
              </p>
              <h2 className="mt-3 font-serif text-3xl text-foreground">
                {copy.orders.itemsTitle}
              </h2>

              <div className="mt-6 space-y-4">
                {order.items.map((item) => (
                  <div
                    key={`${order.order_number}-${item.product_id}`}
                    className="flex gap-4 rounded-[1.5rem] border border-border/60 bg-card p-4"
                  >
                    <div className="relative h-16 w-16 shrink-0 overflow-hidden rounded-2xl bg-muted">
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
                    <div className="flex-1">
                      <p className="font-medium text-foreground">{item.product_name}</p>
                      <p className="mt-1 text-sm text-muted-foreground">
                        Qty {item.quantity} ·{" "}
                        {formatCurrencyAmount(
                          item.unit_price_usd,
                          locale,
                          item.product?.currency ?? order.currency ?? "USD",
                        )}
                      </p>
                    </div>
                  </div>
                ))}
              </div>
            </AccountPanel>

            <div className="space-y-6">
              <AccountPanel className="bg-background/70 p-6">
                <p className="text-sm uppercase tracking-[0.18em] text-primary">
                  {copy.orders.shippingTitle}
                </p>
                <h2 className="mt-3 font-serif text-3xl text-foreground">
                  {copy.orders.shippingTitle}
                </h2>

                <div className="mt-6 space-y-2 text-sm leading-relaxed text-muted-foreground">
                  <p className="font-medium text-foreground">
                    {order.shipping_address.name}
                  </p>
                  {order.shipping_address.phone ? (
                    <p>{order.shipping_address.phone}</p>
                  ) : null}
                  <p>
                    {formatAddressSummary({
                      id: 0,
                      recipient_name: order.shipping_address.name,
                      address_line1: order.shipping_address.address_line1,
                      address_line2: order.shipping_address.address_line2 ?? null,
                      city: order.shipping_address.city,
                      state_province: order.shipping_address.state_province ?? null,
                      postal_code: order.shipping_address.postal_code ?? null,
                      country: order.shipping_address.country,
                      is_default: false,
                    })}
                  </p>
                </div>
              </AccountPanel>

              <AccountPanel className="bg-background/70 p-6">
                <p className="text-sm uppercase tracking-[0.18em] text-primary">
                  {copy.orders.totalTitle}
                </p>
                <h2 className="mt-3 font-serif text-3xl text-foreground">
                  {formatCurrencyAmount(order.total_usd, locale)}
                </h2>
                <div className="mt-4 space-y-2 text-sm text-muted-foreground">
                  <p>
                    Subtotal: {formatCurrencyAmount(order.subtotal_usd, locale)}
                  </p>
                  <p>
                    Shipping: {formatCurrencyAmount(order.shipping_usd, locale)}
                  </p>
                  {order.tax_usd ? (
                    <p>Tax: {formatCurrencyAmount(order.tax_usd, locale)}</p>
                  ) : null}
                </div>
              </AccountPanel>

              {order.customer_note ? (
                <AccountPanel className="bg-background/70 p-6">
                  <p className="text-sm uppercase tracking-[0.18em] text-primary">
                    {copy.orders.noteTitle}
                  </p>
                  <p className="mt-4 whitespace-pre-wrap text-sm leading-relaxed text-muted-foreground">
                    {order.customer_note}
                  </p>
                </AccountPanel>
              ) : null}

              <AccountPanel className="bg-background/70 p-6">
                <p className="text-sm uppercase tracking-[0.18em] text-primary">
                  {copy.orders.nextStepsTitle}
                </p>
                <h2 className="mt-3 font-serif text-3xl text-foreground">
                  {copy.orders.nextStepsTitle}
                </h2>
                <p className="mt-4 text-sm leading-relaxed text-muted-foreground">
                  {copy.orders.nextStepsDescription}
                </p>
                {order.status === "pending" ? (
                  <div className="mt-6">
                    <Button type="button" variant="outline" onClick={handleCancel}>
                      {messages.cancel}
                    </Button>
                  </div>
                ) : null}
              </AccountPanel>
            </div>
          </div>
        </>
      )}
    </AccountPanel>
  )
}
