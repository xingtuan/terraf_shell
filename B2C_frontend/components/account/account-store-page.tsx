"use client"

import { useState } from "react"
import Link from "next/link"

import {
  AccountOrderActions,
  AccountOrderList,
  type AccountOrderListSummary,
} from "@/components/account/account-order-list"
import {
  AccountEmptyState,
  AccountPageHeader,
  AccountPanel,
  AccountStatCard,
} from "@/components/account/account-ui"
import { Button } from "@/components/ui/button"
import { useCart } from "@/hooks/useCart"
import { getAccountCopy } from "@/lib/account-copy"
import { formatCurrencyAmount } from "@/lib/api/products"
import { getLocalizedHref, getMessages, type Locale } from "@/lib/i18n"
import { formatAccountDate } from "@/components/account/account-utils"

type AccountStorePageProps = {
  locale: Locale
}

const initialOrderSummary: AccountOrderListSummary = {
  totalOrders: 0,
  latestOrderDate: null,
}

export function AccountStorePage({ locale }: AccountStorePageProps) {
  const { cart } = useCart()
  const copy = getAccountCopy(locale)
  const siteMessages = getMessages(locale)
  const [orderSummary, setOrderSummary] =
    useState<AccountOrderListSummary>(initialOrderSummary)
  const latestOrderDetail = orderSummary.latestOrderDate
    ? formatAccountDate(locale, orderSummary.latestOrderDate) ??
      copy.store.latestOrderEmpty
    : copy.store.latestOrderEmpty

  return (
    <AccountPanel>
      <AccountPageHeader
        eyebrow={copy.store.eyebrow}
        title={copy.store.title}
        description={copy.store.description}
        actions={<AccountOrderActions locale={locale} />}
      />

      <div className="mt-8 grid gap-4 sm:grid-cols-2">
        <AccountStatCard
          label={copy.overview.cartLabel}
          value={cart?.item_count ?? 0}
          detail={copy.store.cartTitle}
        />
        <AccountStatCard
          label={copy.overview.ordersLabel}
          value={orderSummary.totalOrders}
          detail={latestOrderDetail}
        />
      </div>

      <AccountPanel className="mt-8 bg-background/70 p-6">
        <div className="flex items-end justify-between gap-4">
          <div>
            <p className="text-sm uppercase tracking-[0.18em] text-primary">
              {copy.store.cartTitle}
            </p>
            <h2 className="mt-3 font-serif text-3xl text-foreground">
              {copy.store.cartTitle}
            </h2>
          </div>
          <div className="flex flex-wrap gap-3">
            <Button asChild>
              <Link href={getLocalizedHref(locale, "store/cart")}>
                {copy.store.viewCart}
              </Link>
            </Button>
          </div>
        </div>

        {cart && cart.item_count > 0 ? (
          <div className="mt-6 space-y-4">
            {cart.items.slice(0, 3).map((item) => (
              <div
                key={`${item.product_id}-${item.product_variant_id ?? "default"}`}
                className="rounded-[1.5rem] border border-border/60 bg-card p-4"
              >
                <div className="flex items-center justify-between gap-4">
                  <div>
                    <p className="font-medium text-foreground">
                      {item.product?.name ?? siteMessages.checkout.productFallback}
                    </p>
                    <p className="mt-1 text-sm text-muted-foreground">
                      {siteMessages.checkout.qty} {item.quantity}
                    </p>
                    {item.variant_title || item.variant_sku ? (
                      <p className="mt-1 text-xs text-muted-foreground">
                        {[
                          item.variant_title,
                          item.variant_sku
                            ? `${siteMessages.checkout.productCodeLabel} ${item.variant_sku}`
                            : null,
                        ]
                          .filter(Boolean)
                          .join(" | ")}
                      </p>
                    ) : null}
                  </div>
                  <p className="text-sm font-medium text-foreground">
                    {formatCurrencyAmount(
                      item.line_total,
                      locale,
                      item.currency ?? item.product?.currency ?? "NZD",
                    )}
                  </p>
                </div>
              </div>
            ))}

            <div className="rounded-[1.5rem] border border-border/60 bg-card p-5">
              <p className="text-sm text-muted-foreground">
                {copy.store.cartSubtotal}
              </p>
              <p className="mt-2 text-2xl text-foreground">
                {formatCurrencyAmount(cart.subtotal_usd, locale)}
              </p>
            </div>

            <div className="flex flex-wrap gap-3">
              <Button asChild>
                <Link href={getLocalizedHref(locale, "store/checkout")}>
                  {copy.store.goToCheckout}
                </Link>
              </Button>
            </div>
          </div>
        ) : (
          <div className="mt-6">
            <AccountEmptyState
              title={copy.store.cartTitle}
              description={copy.store.cartEmpty}
            />
          </div>
        )}
      </AccountPanel>

      <AccountPanel className="mt-8 bg-background/70 p-6">
        <p className="text-sm uppercase tracking-[0.18em] text-primary">
          {copy.orders.eyebrow}
        </p>
        <h2 className="mt-3 font-serif text-3xl text-foreground">
          {copy.orders.title}
        </h2>
        <p className="mt-3 text-sm leading-relaxed text-muted-foreground">
          {copy.orders.description}
        </p>

        <AccountOrderList
          locale={locale}
          onSummaryChange={setOrderSummary}
        />
      </AccountPanel>

    </AccountPanel>
  )
}
