"use client"

import { useEffect, useState } from "react"
import Link from "next/link"

import { Button } from "@/components/ui/button"
import { getAccountCopy } from "@/lib/account-copy"
import { listAddresses } from "@/lib/api/addresses"
import { getErrorMessage } from "@/lib/api/client"
import { getOrders } from "@/lib/api/orders"
import { formatCurrencyAmount } from "@/lib/api/products"
import { getLocalizedHref, type Locale } from "@/lib/i18n"
import type { Address, StoreOrder } from "@/lib/types"
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

type AccountStorePageProps = {
  locale: Locale
}

export function AccountStorePage({ locale }: AccountStorePageProps) {
  const session = useAuthSession()
  const { cart, openCart } = useCart()
  const copy = getAccountCopy(locale)
  const [orders, setOrders] = useState<StoreOrder[]>([])
  const [totalOrders, setTotalOrders] = useState(0)
  const [addresses, setAddresses] = useState<Address[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)

  useEffect(() => {
    if (!session.token) {
      return
    }

    setLoading(true)
    setError(null)

    void Promise.all([getOrders(session.token, 1, 3), listAddresses(session.token)])
      .then(([ordersResponse, nextAddresses]) => {
        setOrders(ordersResponse.items)
        setTotalOrders(ordersResponse.meta.total)
        setAddresses(nextAddresses)
      })
      .catch((loadError) => {
        setError(getErrorMessage(loadError))
      })
      .finally(() => {
        setLoading(false)
      })
  }, [session.token])

  const defaultAddress = getDefaultAddress(addresses)
  const latestOrder = orders[0] ?? null

  return (
    <AccountPanel>
      <AccountPageHeader
        eyebrow={copy.store.eyebrow}
        title={copy.store.title}
        description={copy.store.description}
        actions={
          <>
            <Button asChild variant="outline">
              <Link href={getLocalizedHref(locale, "account/orders")}>
                {copy.overview.viewOrders}
              </Link>
            </Button>
            <Button asChild>
              <Link href={getLocalizedHref(locale, "store")}>
                {copy.store.browseStore}
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

      <div className="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <AccountStatCard
          label={copy.overview.cartLabel}
          value={cart?.item_count ?? 0}
          detail={copy.store.cartTitle}
        />
        <AccountStatCard
          label={copy.overview.ordersLabel}
          value={totalOrders}
          detail={
            latestOrder?.created_at
              ? formatAccountDate(locale, latestOrder.created_at)
              : copy.store.latestOrderEmpty
          }
        />
        <AccountStatCard
          label={copy.addresses.defaultStatus}
          value={defaultAddress ? copy.addresses.defaultReady : copy.addresses.noDefault}
          detail={
            defaultAddress
              ? defaultAddress.label || defaultAddress.recipient_name
              : copy.store.checkoutNeedsAddress
          }
        />
      </div>

      {loading ? (
        <div className="mt-8 rounded-[1.5rem] border border-border/60 bg-background/70 p-6 text-sm text-muted-foreground">
          {copy.store.loading}
        </div>
      ) : null}

      <div className="mt-8 grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
        <AccountPanel className="bg-background/70 p-6">
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
              <Button type="button" variant="outline" onClick={() => openCart()}>
                {copy.store.openCart}
              </Button>
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
                  key={item.product_id}
                  className="rounded-[1.5rem] border border-border/60 bg-card p-4"
                >
                  <div className="flex items-center justify-between gap-4">
                    <div>
                      <p className="font-medium text-foreground">
                        {item.product?.name ?? `Product #${item.product_id}`}
                      </p>
                      <p className="mt-1 text-sm text-muted-foreground">
                        Qty {item.quantity}
                      </p>
                    </div>
                    <p className="text-sm font-medium text-foreground">
                      {formatCurrencyAmount(
                        item.line_total,
                        locale,
                        item.product?.currency ?? "USD",
                      )}
                    </p>
                  </div>
                </div>
              ))}

              <div className="rounded-[1.5rem] border border-border/60 bg-card p-5">
                <p className="text-sm text-muted-foreground">{copy.store.cartSubtotal}</p>
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
                <Button asChild variant="outline">
                  <Link href={getLocalizedHref(locale, "store")}>
                    {copy.store.browseStore}
                  </Link>
                </Button>
              </div>
            </div>
          ) : (
            <div className="mt-6">
              <AccountEmptyState
                title={copy.store.cartTitle}
                description={copy.store.cartEmpty}
                action={
                  <Button asChild>
                    <Link href={getLocalizedHref(locale, "store")}>
                      {copy.store.browseStore}
                    </Link>
                  </Button>
                }
              />
            </div>
          )}
        </AccountPanel>

        <div className="space-y-6">
          <AccountPanel className="bg-background/70 p-6">
            <p className="text-sm uppercase tracking-[0.18em] text-primary">
              {copy.store.latestOrderTitle}
            </p>
            <h2 className="mt-3 font-serif text-3xl text-foreground">
              {copy.store.latestOrderTitle}
            </h2>

            {latestOrder ? (
              <div className="mt-6 rounded-[1.5rem] border border-border/60 bg-card p-5">
                <div className="flex items-center justify-between gap-4">
                  <div>
                    <p className="font-medium text-foreground">
                      {latestOrder.order_number}
                    </p>
                    <p className="mt-1 text-sm text-muted-foreground">
                      {formatAccountDate(locale, latestOrder.created_at)}
                    </p>
                  </div>
                  <span
                    className={`rounded-full px-3 py-1 text-xs uppercase tracking-[0.18em] ${getOrderStatusClasses(latestOrder.status)}`}
                  >
                    {latestOrder.status}
                  </span>
                </div>
                <p className="mt-4 text-sm font-medium text-foreground">
                  {formatCurrencyAmount(latestOrder.total_usd, locale)}
                </p>
                <div className="mt-4">
                  <Button asChild variant="outline" size="sm">
                    <Link
                      href={getLocalizedHref(
                        locale,
                        `account/orders/${latestOrder.order_number}`,
                      )}
                    >
                      {copy.overview.viewOrders}
                    </Link>
                  </Button>
                </div>
              </div>
            ) : (
              <div className="mt-6">
                <AccountEmptyState
                  title={copy.store.latestOrderTitle}
                  description={copy.store.latestOrderEmpty}
                />
              </div>
            )}
          </AccountPanel>

          <AccountPanel className="bg-background/70 p-6">
            <p className="text-sm uppercase tracking-[0.18em] text-primary">
              {copy.store.checkoutReadyTitle}
            </p>
            <h2 className="mt-3 font-serif text-3xl text-foreground">
              {copy.store.checkoutReadyTitle}
            </h2>

            {defaultAddress ? (
              <div className="mt-6 rounded-[1.5rem] border border-border/60 bg-card p-5">
                <p className="font-medium text-foreground">
                  {defaultAddress.label || defaultAddress.recipient_name}
                </p>
                <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
                  {formatAddressSummary(defaultAddress)}
                </p>
                <p className="mt-4 text-sm text-muted-foreground">
                  {copy.store.checkoutReadyDescription}
                </p>
              </div>
            ) : (
              <div className="mt-6">
                <AccountEmptyState
                  title={copy.store.checkoutReadyTitle}
                  description={copy.store.checkoutNeedsAddress}
                  action={
                    <Button asChild>
                      <Link href={getLocalizedHref(locale, "account/addresses")}>
                        {copy.overview.manageAddresses}
                      </Link>
                    </Button>
                  }
                />
              </div>
            )}
          </AccountPanel>

          <AccountPanel className="bg-background/70 p-6">
            <p className="text-sm uppercase tracking-[0.18em] text-primary">
              {copy.store.savedItemsTitle}
            </p>
            <h2 className="mt-3 font-serif text-3xl text-foreground">
              {copy.store.savedItemsTitle}
            </h2>
            <p className="mt-4 text-sm leading-relaxed text-muted-foreground">
              {copy.store.savedItemsDescription}
            </p>
          </AccountPanel>
        </div>
      </div>
    </AccountPanel>
  )
}
