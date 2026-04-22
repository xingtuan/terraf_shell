"use client"

import { useState } from "react"
import Image from "next/image"
import Link from "next/link"
import { useRouter } from "next/navigation"
import { Minus, Plus, ShoppingBag, Trash2 } from "lucide-react"

import { CommunityAuthPanel } from "@/components/community/community-auth-panel"
import { Button } from "@/components/ui/button"
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogTitle,
} from "@/components/ui/dialog"
import { formatCurrencyAmount } from "@/lib/api/products"
import { getMessages, getLocalizedHref, type Locale } from "@/lib/i18n"
import { useAuthSession } from "@/hooks/use-auth-session"
import { useCart } from "@/hooks/useCart"

type StoreCartPageProps = {
  locale: Locale
}

export function StoreCartPage({ locale }: StoreCartPageProps) {
  const router = useRouter()
  const session = useAuthSession()
  const {
    cart,
    error,
    loading,
    updateItem,
    removeItem,
    clearCart,
  } = useCart()
  const messages = getMessages(locale)
  const authCopy = messages.community.auth
  const t = messages.cartPage
  const [isAuthOpen, setIsAuthOpen] = useState(false)

  if (!cart || cart.items.length === 0) {
    return (
      <div className="mx-auto max-w-5xl px-6 py-20 lg:px-8">
        <div className="rounded-[2rem] border border-border/60 bg-card p-10 text-center">
          <ShoppingBag className="mx-auto size-10 text-muted-foreground" />
          <h1 className="mt-6 font-serif text-4xl text-foreground">
            {t.emptyTitle}
          </h1>
          <p className="mx-auto mt-4 max-w-2xl text-muted-foreground">
            {t.emptyDescription}
          </p>
          <Button asChild className="mt-6">
            <Link href={getLocalizedHref(locale, "store")}>{t.browseCollection}</Link>
          </Button>
        </div>
      </div>
    )
  }

  return (
    <>
      <div className="mx-auto max-w-7xl px-6 py-16 lg:px-8">
        <div className="mb-10 flex flex-wrap items-end justify-between gap-4">
          <div>
            <p className="text-sm uppercase tracking-[0.2em] text-primary">{t.eyebrow}</p>
            <h1 className="mt-3 font-serif text-4xl text-foreground">
              {t.title}
            </h1>
          </div>
          <Button
            type="button"
            variant="outline"
            disabled={loading}
            onClick={() => {
              void clearCart()
            }}
          >
            {t.clearCart}
          </Button>
        </div>

        {error ? (
          <div className="mb-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {error}
          </div>
        ) : null}

        <div className="grid gap-8 xl:grid-cols-[1.12fr_0.88fr]">
          <section className="space-y-4">
            {cart.items.map((item) => (
              <article
                key={item.product_id}
                className="rounded-[2rem] border border-border/60 bg-card p-5"
              >
                <div className="flex gap-4">
                  <div className="relative h-24 w-24 shrink-0 overflow-hidden rounded-3xl bg-muted">
                    <Image
                      src={item.product?.primary_image_url || item.product?.image_url || "/placeholder.jpg"}
                      alt={item.product?.name || "Shellfin product"}
                      fill
                      className="object-cover"
                    />
                  </div>

                  <div className="min-w-0 flex-1">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                      <div>
                        <Link
                          href={getLocalizedHref(
                            locale,
                            `store/${item.product?.slug ?? ""}`,
                          )}
                          className="font-medium text-foreground transition-colors hover:text-primary"
                        >
                          {item.product?.name || t.productUnavailable}
                        </Link>
                        {item.product?.subtitle ? (
                          <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
                            {item.product.subtitle}
                          </p>
                        ) : null}
                        <div className="mt-3 flex flex-wrap items-center gap-3 text-xs uppercase tracking-[0.18em] text-muted-foreground">
                          {item.product?.stock_status_label ? (
                            <span>{item.product.stock_status_label}</span>
                          ) : null}
                          {item.product?.lead_time ? (
                            <span>{item.product.lead_time}</span>
                          ) : null}
                        </div>
                      </div>
                      <div className="text-right">
                        <p className="text-sm text-muted-foreground">
                          {formatCurrencyAmount(
                            item.unit_price_usd,
                            locale,
                            item.product?.currency ?? "USD",
                          )}{" "}
                          {t.each}
                        </p>
                        <p className="mt-2 text-lg font-medium text-foreground">
                          {formatCurrencyAmount(
                            item.line_total,
                            locale,
                            item.product?.currency ?? "USD",
                          )}
                        </p>
                      </div>
                    </div>

                    <div className="mt-5 flex flex-wrap items-center justify-between gap-3">
                      <div className="flex items-center rounded-full border border-border/70">
                        <button
                          type="button"
                          className="px-3 py-2 text-foreground transition-colors hover:bg-muted"
                          onClick={() => {
                            void updateItem(item.product_id, item.quantity - 1)
                          }}
                          aria-label={t.decreaseQuantity}
                        >
                          <Minus className="size-4" />
                        </button>
                        <span className="min-w-10 text-center text-sm font-medium">
                          {item.quantity}
                        </span>
                        <button
                          type="button"
                          className="px-3 py-2 text-foreground transition-colors hover:bg-muted"
                          onClick={() => {
                            if (item.quantity >= 10) {
                              return
                            }

                            void updateItem(item.product_id, item.quantity + 1)
                          }}
                          aria-label={t.increaseQuantity}
                        >
                          <Plus className="size-4" />
                        </button>
                      </div>

                      <button
                        type="button"
                        className="inline-flex items-center gap-2 text-sm text-muted-foreground transition-colors hover:text-foreground"
                        onClick={() => {
                          void removeItem(item.product_id)
                        }}
                      >
                        <Trash2 className="size-4" />
                        {t.remove}
                      </button>
                    </div>
                  </div>
                </div>
              </article>
            ))}
          </section>

          <aside className="rounded-[2rem] border border-border/60 bg-card p-8">
            <p className="text-sm uppercase tracking-[0.18em] text-primary">
              {t.orderSummary}
            </p>
            <div className="mt-6 space-y-4 text-sm">
              <div className="flex items-center justify-between">
                <span className="text-muted-foreground">{t.subtotal}</span>
                <span className="text-foreground">
                  {formatCurrencyAmount(cart.subtotal_usd, locale)}
                </span>
              </div>
              <div className="flex items-center justify-between">
                <span className="text-muted-foreground">{t.estimatedShipping}</span>
                <span className="text-foreground">
                  {formatCurrencyAmount(cart.estimated_shipping_usd, locale)}
                </span>
              </div>
              <div className="flex items-center justify-between">
                <span className="text-muted-foreground">{t.estimatedTax}</span>
                <span className="text-foreground">
                  {formatCurrencyAmount(cart.estimated_tax_usd, locale)}
                </span>
              </div>
              <div className="flex items-center justify-between border-t border-border/60 pt-4 text-base font-medium">
                <span className="text-foreground">{t.estimatedTotal}</span>
                <span className="text-foreground">
                  {formatCurrencyAmount(cart.estimated_total_usd, locale)}
                </span>
              </div>
            </div>

            <div className="mt-6 rounded-3xl bg-background p-5 text-sm leading-relaxed text-muted-foreground">
              {t.freeShippingNote.replace("{threshold}", formatCurrencyAmount(cart.free_shipping_threshold_usd, locale))}
            </div>

            <div className="mt-8 space-y-3">
              <Button
                type="button"
                className="w-full"
                onClick={() => {
                  const checkoutHref = getLocalizedHref(locale, "store/checkout")

                  if (!session.user) {
                    setIsAuthOpen(true)
                    return
                  }

                  router.push(checkoutHref)
                }}
              >
                {session.user ? t.proceedToCheckout : t.signInToCheckout}
              </Button>
              <Button asChild variant="outline" className="w-full">
                <Link href={getLocalizedHref(locale, "store")}>
                  {t.continueShopping}
                </Link>
              </Button>
            </div>
          </aside>
        </div>
      </div>

      <Dialog open={isAuthOpen} onOpenChange={setIsAuthOpen}>
        <DialogContent className="max-w-2xl border-none bg-transparent p-0 shadow-none">
          <DialogTitle className="sr-only">{t.signInDialogTitle}</DialogTitle>
          <DialogDescription className="sr-only">
            {t.signInDialogDescription}
          </DialogDescription>
          <CommunityAuthPanel
            copy={authCopy}
            user={session.user}
            isReady={session.isReady}
            isLoadingUser={session.isLoadingUser}
            context="store"
            redirectAfterLogin={getLocalizedHref(locale, "store/checkout")}
            onSuccess={() => {
              setIsAuthOpen(false)
              router.push(getLocalizedHref(locale, "store/checkout"))
            }}
            onLogin={session.login}
            onRegister={session.register}
            onLogout={session.logout}
            onRefresh={session.refreshUser}
          />
        </DialogContent>
      </Dialog>
    </>
  )
}
