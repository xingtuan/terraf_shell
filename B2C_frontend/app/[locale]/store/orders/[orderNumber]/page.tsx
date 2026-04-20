"use client"

import { use, useEffect, useState } from "react"
import Image from "next/image"
import Link from "next/link"
import { CheckCircle2, Copy } from "lucide-react"

import { AuthGate } from "@/components/auth/AuthGate"
import { Button } from "@/components/ui/button"
import { getOrder } from "@/lib/api/orders"
import { getErrorMessage } from "@/lib/api/client"
import { getLocalizedHref, isValidLocale, type Locale } from "@/lib/i18n"
import type { StoreOrder } from "@/lib/types"
import { useAuthSession } from "@/hooks/use-auth-session"

type OrderConfirmationPageProps = {
  params: Promise<{ locale: string; orderNumber: string }>
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

function OrderConfirmationScreen({
  locale,
  orderNumber,
}: {
  locale: Locale
  orderNumber: string
}) {
  const session = useAuthSession()
  const [order, setOrder] = useState<StoreOrder | null>(null)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    if (!session.token) {
      return
    }

    void getOrder(orderNumber, session.token)
      .then(setOrder)
      .catch((nextError) => {
        setError(getErrorMessage(nextError))
      })
  }, [orderNumber, session.token])

  if (error) {
    return (
      <div className="mx-auto max-w-4xl px-6 py-16 lg:px-8">
        <div className="rounded-[2rem] border border-red-200 bg-red-50 p-8 text-red-700">
          {error}
        </div>
      </div>
    )
  }

  if (!order) {
    return (
      <div className="mx-auto max-w-4xl px-6 py-16 lg:px-8">
        <div className="rounded-[2rem] border border-border/60 bg-card p-8 text-sm text-muted-foreground">
          Loading order details...
        </div>
      </div>
    )
  }

  return (
    <div className="mx-auto max-w-5xl px-6 py-16 lg:px-8">
      <div className="rounded-[2rem] border border-border/60 bg-card p-8 lg:p-10">
        <div className="flex flex-col items-start gap-5 border-b border-border/60 pb-8">
          <div className="rounded-full bg-emerald-100 p-3 text-emerald-700">
            <CheckCircle2 className="size-8" />
          </div>
          <div>
            <p className="text-sm uppercase tracking-[0.2em] text-primary">
              Order Confirmed
            </p>
            <h1 className="mt-3 font-serif text-4xl text-foreground">
              Order Placed!
            </h1>
          </div>
          <div className="flex flex-wrap items-center gap-3">
            <button
              type="button"
              className="inline-flex items-center gap-2 rounded-full border border-border/70 px-4 py-2 text-sm text-foreground"
              onClick={() => {
                void navigator.clipboard.writeText(order.order_number)
              }}
            >
              <Copy className="size-4" />
              {order.order_number}
            </button>
            <span
              className={`rounded-full px-3 py-1 text-xs uppercase tracking-[0.18em] ${statusClasses(order.status)}`}
            >
              {order.status}
            </span>
          </div>
        </div>

        <div className="grid grid-cols-1 gap-8 pt-8 lg:grid-cols-[1.1fr_0.9fr]">
          <section>
            <h2 className="font-serif text-2xl text-foreground">Items</h2>
            <div className="mt-5 space-y-4">
              {order.items.map((item) => (
                <div
                  key={`${order.order_number}-${item.product_id}`}
                  className="flex gap-4 rounded-3xl border border-border/60 p-4"
                >
                  <div className="relative h-16 w-16 shrink-0 overflow-hidden rounded-2xl bg-muted">
                    <Image
                      src={item.product?.image_url || "/placeholder.jpg"}
                      alt={item.product_name}
                      fill
                      className="object-cover"
                    />
                  </div>
                  <div className="flex-1">
                    <p className="font-medium text-foreground">{item.product_name}</p>
                    <p className="mt-1 text-sm text-muted-foreground">
                      Qty {item.quantity} · ${item.unit_price_usd} USD
                    </p>
                  </div>
                </div>
              ))}
            </div>
          </section>

          <section className="space-y-6">
            <div className="rounded-3xl border border-border/60 p-5">
              <h2 className="font-serif text-2xl text-foreground">
                Shipping Address
              </h2>
              <div className="mt-4 text-sm leading-relaxed text-muted-foreground">
                <p>{order.shipping_address.name}</p>
                {order.shipping_address.phone ? (
                  <p>{order.shipping_address.phone}</p>
                ) : null}
                <p>{order.shipping_address.address_line1}</p>
                {order.shipping_address.address_line2 ? (
                  <p>{order.shipping_address.address_line2}</p>
                ) : null}
                <p>
                  {order.shipping_address.city}
                  {order.shipping_address.state_province
                    ? `, ${order.shipping_address.state_province}`
                    : ""}
                </p>
                {order.shipping_address.postal_code ? (
                  <p>{order.shipping_address.postal_code}</p>
                ) : null}
                <p>{order.shipping_address.country}</p>
              </div>
            </div>

            <div className="rounded-3xl border border-border/60 p-5">
              <h2 className="font-serif text-2xl text-foreground">Total</h2>
              <p className="mt-4 text-2xl font-medium text-foreground">
                ${Number(order.total_usd).toFixed(2)} USD
              </p>
            </div>

            <div className="rounded-3xl border border-border/60 p-5 text-sm leading-relaxed text-muted-foreground">
              <h2 className="font-serif text-2xl text-foreground">
                What happens next?
              </h2>
              <p className="mt-4">
                Our team will review your order and confirm within 2 business
                days. You can track your order status in your account.
              </p>
            </div>
          </section>
        </div>

        <div className="mt-8 flex flex-wrap gap-3 border-t border-border/60 pt-8">
          <Button asChild>
            <Link href={getLocalizedHref(locale, "store")}>Continue Shopping</Link>
          </Button>
          <Button asChild variant="outline">
            <Link href={getLocalizedHref(locale, "store/orders")}>
              View All Orders
            </Link>
          </Button>
        </div>
      </div>
    </div>
  )
}

export default function OrderConfirmationPage({
  params,
}: OrderConfirmationPageProps) {
  const resolvedParams = use(params)
  const locale = isValidLocale(resolvedParams.locale) ? resolvedParams.locale : "en"
  const orderHref = getLocalizedHref(
    locale,
    `store/orders/${resolvedParams.orderNumber}`,
  )

  return (
    <AuthGate locale={locale} redirectAfterLogin={orderHref}>
      <OrderConfirmationScreen
        locale={locale}
        orderNumber={resolvedParams.orderNumber}
      />
    </AuthGate>
  )
}
