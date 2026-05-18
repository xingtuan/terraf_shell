"use client"

import type { FormEvent } from "react"
import { useEffect, useMemo, useState } from "react"
import Image from "next/image"
import Link from "next/link"
import {
  CheckCircle2,
  Clock3,
  Mail,
  PackageCheck,
  PackageSearch,
  Search,
  Truck,
  XCircle,
} from "lucide-react"

import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import { ApiError, getErrorMessage } from "@/lib/api/client"
import { getGuestOrder, lookupGuestOrder } from "@/lib/api/orders"
import { formatCurrencyAmount } from "@/lib/api/products"
import { getLocalizedHref, getMessages, type Locale } from "@/lib/i18n"
import type { StoreOrder, StoreOrderStatus } from "@/lib/types"
import {
  formatAccountDate,
  getOrderStatusClasses,
  getOrderStatusLabel,
  getPaymentStatusLabel,
} from "@/components/account/account-utils"

type StoreOrderLookupPageProps = {
  locale: Locale
  initialOrderNumber?: string
  initialToken?: string
}

const TRACKED_STATUSES: Exclude<StoreOrderStatus, "cancelled">[] = [
  "pending",
  "confirmed",
  "processing",
  "shipped",
  "delivered",
]

const statusIcons = {
  pending: Clock3,
  confirmed: CheckCircle2,
  processing: PackageSearch,
  shipped: Truck,
  delivered: PackageCheck,
} satisfies Record<Exclude<StoreOrderStatus, "cancelled">, typeof Clock3>

function formatDetailedDate(locale: Locale, value?: string | null) {
  return formatAccountDate(locale, value, {
    year: "numeric",
    month: "short",
    day: "numeric",
    hour: "2-digit",
    minute: "2-digit",
  })
}

function statusTimestamp(
  order: StoreOrder,
  status: Exclude<StoreOrderStatus, "cancelled">,
) {
  switch (status) {
    case "pending":
      return order.created_at
    case "confirmed":
      return order.confirmed_at
    case "processing":
      return null
    case "shipped":
      return order.shipped_at
    case "delivered":
      return order.delivered_at
  }
}

function completedStatusIndex(order: StoreOrder) {
  if (order.status !== "cancelled") {
    return Math.max(0, TRACKED_STATUSES.indexOf(order.status))
  }

  return Math.max(
    0,
    ...TRACKED_STATUSES.map((status, index) =>
      statusTimestamp(order, status) ? index : -1,
    ),
  )
}

function shippingAddressSummary(order: StoreOrder) {
  return [
    order.shipping_address.address_line1,
    order.shipping_address.address_line2,
    [
      order.shipping_address.city,
      order.shipping_address.state_province,
      order.shipping_address.postal_code,
    ]
      .filter(Boolean)
      .join(", "),
    order.shipping_address.country,
  ]
    .filter(Boolean)
    .join(", ")
}

export function StoreOrderLookupPage({
  locale,
  initialOrderNumber = "",
  initialToken = "",
}: StoreOrderLookupPageProps) {
  const messages = getMessages(locale)
  const t = messages.orderLookup
  const checkoutMessages = messages.checkout
  const [orderNumber, setOrderNumber] = useState(initialOrderNumber)
  const [email, setEmail] = useState("")
  const [order, setOrder] = useState<StoreOrder | null>(null)
  const [loading, setLoading] = useState(false)
  const [error, setError] = useState<string | null>(null)
  const completedIndex = useMemo(
    () => (order ? completedStatusIndex(order) : 0),
    [order],
  )

  useEffect(() => {
    if (!initialOrderNumber || !initialToken) {
      return
    }

    let cancelled = false

    async function loadTokenOrder() {
      setLoading(true)
      setError(null)

      try {
        const nextOrder = await getGuestOrder(initialOrderNumber, initialToken)

        if (cancelled) return

        setOrder(nextOrder)
        setEmail(nextOrder.guest_email ?? "")
      } catch (loadError) {
        if (cancelled) return

        if (loadError instanceof ApiError && [401, 403, 404].includes(loadError.status)) {
          setError(t.lookupFailed)
        } else {
          setError(getErrorMessage(loadError))
        }
      } finally {
        if (!cancelled) setLoading(false)
      }
    }

    void loadTokenOrder()

    return () => {
      cancelled = true
    }
  }, [initialOrderNumber, initialToken, t.lookupFailed])

  async function handleSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()

    const normalizedOrderNumber = orderNumber.trim()
    const normalizedEmail = email.trim()

    if (!normalizedOrderNumber || !normalizedEmail) {
      setError(t.validationRequired)
      return
    }

    setLoading(true)
    setError(null)

    try {
      const nextOrder = await lookupGuestOrder(normalizedOrderNumber, normalizedEmail)

      setOrder(nextOrder)
      setOrderNumber(nextOrder.order_number)
      setEmail(nextOrder.guest_email ?? normalizedEmail)
    } catch (lookupError) {
      setOrder(null)

      if (lookupError instanceof ApiError && [404, 422].includes(lookupError.status)) {
        setError(t.lookupFailed)
      } else {
        setError(getErrorMessage(lookupError))
      }
    } finally {
      setLoading(false)
    }
  }

  return (
    <main className="bg-background py-16 lg:py-20">
      <div className="mx-auto max-w-7xl px-6 lg:px-8">
        <div className="grid gap-8 xl:grid-cols-[0.82fr_1.18fr]">
          <section className="rounded-[2rem] border border-border/60 bg-card p-6 lg:p-8">
            <p className="text-sm uppercase tracking-[0.2em] text-primary">
              {t.eyebrow}
            </p>
            <h1 className="mt-4 font-serif text-4xl text-foreground">
              {t.title}
            </h1>
            <p className="mt-4 text-sm leading-relaxed text-muted-foreground">
              {t.description}
            </p>

            <form className="mt-8 space-y-5" onSubmit={handleSubmit}>
              <div className="space-y-2">
                <Label htmlFor="guest-order-number">{t.orderNumberLabel}</Label>
                <Input
                  id="guest-order-number"
                  value={orderNumber}
                  onChange={(event) => setOrderNumber(event.target.value)}
                  placeholder={t.orderNumberPlaceholder}
                  autoComplete="off"
                />
              </div>
              <div className="space-y-2">
                <Label htmlFor="guest-order-email">{t.emailLabel}</Label>
                <Input
                  id="guest-order-email"
                  type="email"
                  value={email}
                  onChange={(event) => setEmail(event.target.value)}
                  placeholder={t.emailPlaceholder}
                  autoComplete="email"
                />
              </div>

              {error ? (
                <div className="rounded-2xl bg-destructive/10 px-4 py-3 text-sm text-destructive">
                  {error}
                </div>
              ) : null}

              {initialToken && order ? (
                <div className="rounded-2xl bg-primary/8 px-4 py-3 text-sm text-foreground">
                  {t.secureLinkNotice}
                </div>
              ) : null}

              <Button type="submit" className="w-full gap-2" disabled={loading}>
                <Search className="size-4" />
                {loading ? t.loading : t.submit}
              </Button>
            </form>

            <div className="mt-8 rounded-2xl border border-border/60 bg-background p-5">
              <p className="text-sm font-medium text-foreground">
                {t.accountOrdersPrompt}
              </p>
              <div className="mt-4 flex flex-wrap gap-3">
                <Button asChild variant="outline" size="sm">
                  <Link href={getLocalizedHref(locale, "account/orders")}>
                    {t.accountOrdersAction}
                  </Link>
                </Button>
                <Button asChild variant="ghost" size="sm">
                  <Link href={getLocalizedHref(locale, "store")}>
                    {t.continueShopping}
                  </Link>
                </Button>
              </div>
            </div>
          </section>

          <section className="min-h-[34rem] rounded-[2rem] border border-border/60 bg-card p-6 lg:p-8">
            {!order ? (
              <div className="flex h-full min-h-[28rem] flex-col items-center justify-center text-center">
                <PackageSearch className="size-12 text-muted-foreground" />
                <h2 className="mt-5 font-serif text-3xl text-foreground">
                  {t.emptyTitle}
                </h2>
                <p className="mt-3 max-w-xl text-sm leading-relaxed text-muted-foreground">
                  {t.emptyDescription}
                </p>
              </div>
            ) : (
              <div className="space-y-8">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                  <div>
                    <p className="text-sm uppercase tracking-[0.18em] text-primary">
                      {t.summaryTitle}
                    </p>
                    <h2 className="mt-3 font-serif text-4xl text-foreground">
                      {order.order_number}
                    </h2>
                    <p className="mt-2 text-sm text-muted-foreground">
                      {t.submittedOn}{" "}
                      {formatDetailedDate(locale, order.created_at) ?? t.waitingStep}
                    </p>
                  </div>
                  <div className="flex flex-wrap gap-2">
                    <span
                      className={`rounded-full px-3 py-1 text-xs uppercase tracking-[0.18em] ${getOrderStatusClasses(order.status)}`}
                    >
                      {getOrderStatusLabel(order.status, messages.orderStatuses)}
                    </span>
                    <span className="rounded-full bg-amber-100 px-3 py-1 text-xs uppercase tracking-[0.18em] text-amber-700">
                      {getPaymentStatusLabel(
                        order.payment_status,
                        messages.paymentStatuses,
                      )}
                    </span>
                  </div>
                </div>

                <div className="grid gap-4 md:grid-cols-3">
                  <div className="rounded-2xl border border-border/60 bg-background p-5">
                    <p className="text-xs uppercase tracking-[0.16em] text-muted-foreground">
                      {t.total}
                    </p>
                    <p className="mt-2 text-lg font-medium text-foreground">
                      {formatCurrencyAmount(
                        order.total_usd,
                        locale,
                        order.currency ?? "NZD",
                      )}
                    </p>
                  </div>
                  <div className="rounded-2xl border border-border/60 bg-background p-5">
                    <p className="text-xs uppercase tracking-[0.16em] text-muted-foreground">
                      {t.confirmationEmail}
                    </p>
                    <p className="mt-2 break-words text-sm font-medium text-foreground">
                      {order.guest_email}
                    </p>
                  </div>
                  <div className="rounded-2xl border border-border/60 bg-background p-5">
                    <p className="text-xs uppercase tracking-[0.16em] text-muted-foreground">
                      {t.shippingMethod}
                    </p>
                    <p className="mt-2 text-sm font-medium text-foreground">
                      {order.shipping_method?.label ?? t.waitingStep}
                    </p>
                  </div>
                </div>

                <div>
                  <p className="text-sm uppercase tracking-[0.18em] text-primary">
                    {t.timelineTitle}
                  </p>
                  <ol className="mt-4 grid gap-3 md:grid-cols-5">
                    {TRACKED_STATUSES.map((status, index) => {
                      const Icon = statusIcons[status]
                      const timestamp = statusTimestamp(order, status)
                      const isComplete =
                        index <= completedIndex &&
                        (order.status !== "cancelled" ||
                          status === "pending" ||
                          Boolean(timestamp))
                      const isCurrent = order.status === status

                      return (
                        <li
                          key={status}
                          className={`rounded-2xl border p-4 ${
                            isComplete || isCurrent
                              ? "border-primary/30 bg-primary/8"
                              : "border-border/60 bg-background"
                          }`}
                        >
                          <Icon
                            className={`size-5 ${
                              isComplete || isCurrent
                                ? "text-primary"
                                : "text-muted-foreground"
                            }`}
                          />
                          <p className="mt-3 text-sm font-medium text-foreground">
                            {getOrderStatusLabel(status, messages.orderStatuses)}
                          </p>
                          <p className="mt-1 text-xs leading-relaxed text-muted-foreground">
                            {timestamp
                              ? formatDetailedDate(locale, timestamp)
                              : isCurrent
                                ? t.currentStep
                                : isComplete
                                  ? t.completedStep
                                  : t.waitingStep}
                          </p>
                        </li>
                      )
                    })}
                  </ol>

                  {order.status === "cancelled" ? (
                    <div className="mt-4 flex gap-3 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                      <XCircle className="mt-0.5 size-4 shrink-0" />
                      <p>
                        {t.cancelledNotice}{" "}
                        {formatDetailedDate(locale, order.cancelled_at) ?? ""}
                      </p>
                    </div>
                  ) : null}
                </div>

                <div className="grid gap-6 lg:grid-cols-[1.05fr_0.95fr]">
                  <div>
                    <p className="text-sm uppercase tracking-[0.18em] text-primary">
                      {t.itemsTitle}
                    </p>
                    <div className="mt-4 space-y-3">
                      {order.items.map((item) => (
                        <div
                          key={`${order.order_number}-${item.product_id}-${item.product_variant_id ?? "default"}`}
                          className="flex gap-4 rounded-2xl border border-border/60 bg-background p-4"
                        >
                          <div className="relative h-16 w-16 shrink-0 overflow-hidden rounded-xl bg-muted">
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
                          <div className="min-w-0 flex-1">
                            <p className="font-medium text-foreground">
                              {item.product_name}
                            </p>
                            {item.variant_title || item.variant_sku ? (
                              <p className="mt-1 text-xs text-muted-foreground">
                                {[item.variant_title, item.variant_sku ? `SKU ${item.variant_sku}` : null]
                                  .filter(Boolean)
                                  .join(" | ")}
                              </p>
                            ) : null}
                            <p className="mt-1 text-sm text-muted-foreground">
                              {checkoutMessages.qty} {item.quantity} x{" "}
                              {formatCurrencyAmount(
                                item.unit_price_usd,
                                locale,
                                item.currency ?? order.currency ?? "NZD",
                              )}
                            </p>
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>

                  <div className="space-y-4">
                    <div className="rounded-2xl border border-border/60 bg-background p-5">
                      <p className="text-sm font-medium text-foreground">
                        {t.shippingTitle}
                      </p>
                      <p className="mt-3 text-sm leading-relaxed text-muted-foreground">
                        {order.shipping_address.name}
                        <br />
                        {shippingAddressSummary(order)}
                      </p>
                    </div>

                    <div className="rounded-2xl border border-border/60 bg-background p-5">
                      <p className="text-sm font-medium text-foreground">
                        {t.nextStepsTitle}
                      </p>
                      <div className="mt-3 space-y-2 text-sm text-muted-foreground">
                        <p>
                          {t.subtotal}:{" "}
                          {formatCurrencyAmount(
                            order.subtotal_usd,
                            locale,
                            order.currency ?? "NZD",
                          )}
                        </p>
                        <p>
                          {t.shipping}:{" "}
                          {formatCurrencyAmount(
                            order.shipping_usd,
                            locale,
                            order.currency ?? "NZD",
                          )}
                        </p>
                        <p>
                          {t.tax}:{" "}
                          {formatCurrencyAmount(
                            order.tax_usd,
                            locale,
                            order.currency ?? "NZD",
                          )}
                        </p>
                        <p className="pt-2 font-medium text-foreground">
                          {t.orderStatus}:{" "}
                          {getOrderStatusLabel(order.status, messages.orderStatuses)}
                        </p>
                        <p className="font-medium text-foreground">
                          {t.paymentStatus}:{" "}
                          {getPaymentStatusLabel(
                            order.payment_status,
                            messages.paymentStatuses,
                          )}
                        </p>
                      </div>
                    </div>

                    <div className="flex items-start gap-3 rounded-2xl border border-border/60 bg-background p-5">
                      <Mail className="mt-0.5 size-4 shrink-0 text-primary" />
                      <p className="text-sm leading-relaxed text-muted-foreground">
                        {t.emailNotice}
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            )}
          </section>
        </div>
      </div>
    </main>
  )
}
