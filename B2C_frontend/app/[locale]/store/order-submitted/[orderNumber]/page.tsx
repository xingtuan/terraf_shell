import Link from "next/link"

import { Button } from "@/components/ui/button"
import { ApiError } from "@/lib/api/client"
import { getGuestOrder } from "@/lib/api/orders"
import { formatCurrencyAmount } from "@/lib/api/products"
import { getServerApiBaseUrl } from "@/lib/api/server-base-url"
import { getLocalizedHref, getMessages } from "@/lib/i18n"
import { resolveLocale } from "@/lib/resolve-locale"
import type { StoreOrder } from "@/lib/types"

type GuestOrderSubmittedPageProps = {
  params: Promise<{ locale: string; orderNumber: string }>
  searchParams?: Promise<{ token?: string }>
}

export default async function GuestOrderSubmittedPage({
  params,
  searchParams,
}: GuestOrderSubmittedPageProps) {
  const locale = await resolveLocale(params)
  const resolvedParams = await params
  const resolvedSearchParams = await searchParams
  const messages = getMessages(locale)
  const t = messages.guestOrderSubmitted
  const token = resolvedSearchParams?.token ?? ""
  const apiBaseUrl = await getServerApiBaseUrl()
  let order: StoreOrder | null = null
  let isInvalidToken = false

  if (token) {
    try {
      order = await getGuestOrder(resolvedParams.orderNumber, token, {
        baseUrl: apiBaseUrl,
      })
    } catch (error) {
      if (error instanceof ApiError && [401, 403, 404].includes(error.status)) {
        isInvalidToken = true
      } else {
        isInvalidToken = true
      }
    }
  } else {
    isInvalidToken = true
  }

  return (
    <main className="bg-background py-16 lg:py-20">
      <div className="mx-auto max-w-5xl px-6 lg:px-8">
        <div className="rounded-[2rem] border border-border/60 bg-card p-8 lg:p-10">
          <p className="text-sm uppercase tracking-[0.2em] text-primary">
            {t.eyebrow}
          </p>
          <h1 className="mt-4 font-serif text-4xl text-foreground">
            {isInvalidToken ? t.invalidTitle : t.title}
          </h1>
          <p className="mt-4 max-w-2xl text-sm leading-relaxed text-muted-foreground">
            {isInvalidToken ? t.invalidDescription : t.description}
          </p>

          {order ? (
            <div className="mt-8 grid gap-4 md:grid-cols-3">
              <div className="rounded-3xl border border-border/60 bg-background p-5">
                <p className="text-xs uppercase tracking-[0.18em] text-muted-foreground">
                  {t.orderNumber}
                </p>
                <p className="mt-2 text-lg font-medium text-foreground">
                  {order.order_number}
                </p>
              </div>
              <div className="rounded-3xl border border-border/60 bg-background p-5">
                <p className="text-xs uppercase tracking-[0.18em] text-muted-foreground">
                  {t.email}
                </p>
                <p className="mt-2 text-lg font-medium text-foreground">
                  {order.guest_email}
                </p>
              </div>
              <div className="rounded-3xl border border-border/60 bg-background p-5">
                <p className="text-xs uppercase tracking-[0.18em] text-muted-foreground">
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
            </div>
          ) : null}

          {order ? (
            <div className="mt-8 rounded-3xl bg-background p-6">
              <p className="text-sm font-medium text-foreground">{t.nextStepsTitle}</p>
              <div className="mt-4 grid gap-3 text-sm leading-relaxed text-muted-foreground md:grid-cols-3">
                <p>{t.nextStepEmail}</p>
                <p>{t.nextStepManualPayment}</p>
                <p>{t.nextStepNzDelivery}</p>
              </div>
            </div>
          ) : null}

          <div className="mt-8 flex flex-wrap gap-3">
            <Button asChild>
              <Link href={getLocalizedHref(locale, "store")}>
                {messages.productDetail.backToStore}
              </Link>
            </Button>
            <Button asChild variant="outline">
              <Link href={getLocalizedHref(locale, "contact")}>
                {messages.header.contact}
              </Link>
            </Button>
          </div>
        </div>
      </div>
    </main>
  )
}
