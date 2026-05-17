"use client"

import { useState } from "react"
import Image from "next/image"
import Link from "next/link"
import { useRouter } from "next/navigation"
import { ShoppingBag, Trash2 } from "lucide-react"

import { CommunityAuthPanel } from "@/components/community/community-auth-panel"
import { CartQuantityControl } from "@/components/store/CartQuantityControl"
import {
  AlertDialog,
  AlertDialogAction,
  AlertDialogCancel,
  AlertDialogContent,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogTrigger,
} from "@/components/ui/alert-dialog"
import { Button } from "@/components/ui/button"
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogTitle,
} from "@/components/ui/dialog"
import { formatCurrencyAmount } from "@/lib/api/products"
import { getMessages, getLocalizedHref, type Locale } from "@/lib/i18n"
import { getLocalizedCartQuantityErrorMessage } from "@/lib/store/cart-messages"
import { getCartItemQuantityLimit } from "@/lib/store/product-display"
import type { CartSummaryItem } from "@/lib/types"
import { useAuthSession } from "@/hooks/use-auth-session"
import { useCart } from "@/hooks/useCart"
import { toast } from "@/hooks/use-toast"

type StoreCartPageProps = {
  locale: Locale
}

function cartLineKey(item: CartSummaryItem) {
  return `${item.product_id}-${item.product_variant_id ?? "default"}`
}

export function StoreCartPage({ locale }: StoreCartPageProps) {
  const router = useRouter()
  const session = useAuthSession()
  const {
    cart,
    error,
    loading,
    clearError,
    updateItem,
    removeItem,
    clearCart,
  } = useCart()
  const messages = getMessages(locale)
  const authCopy = messages.community.auth
  const t = messages.cartPage
  const quantityCopy = messages.cartQuantity
  const [isAuthOpen, setIsAuthOpen] = useState(false)
  const [updatingLineKey, setUpdatingLineKey] = useState<string | null>(null)
  const [lineErrors, setLineErrors] = useState<Record<string, string>>({})

  function clearLineError(key: string) {
    setLineErrors((currentErrors) => {
      const { [key]: _removed, ...nextErrors } = currentErrors

      return nextErrors
    })
  }

  async function handleQuantityCommit(
    item: CartSummaryItem,
    nextQuantity: number,
  ) {
    const lineKey = cartLineKey(item)

    setUpdatingLineKey(lineKey)
    clearLineError(lineKey)

    try {
      await updateItem(item.product_id, nextQuantity, item.product_variant_id)
      toast({ title: quantityCopy.quantityUpdated })
    } catch (nextError) {
      const message = getLocalizedCartQuantityErrorMessage(
        nextError,
        messages.common.errors,
        quantityCopy,
      )

      setLineErrors((currentErrors) => ({
        ...currentErrors,
        [lineKey]: message,
      }))
      clearError()
      toast({
        title: quantityCopy.unableToUpdateQuantity,
        description: message,
        variant: "destructive",
      })
      throw nextError
    } finally {
      setUpdatingLineKey(null)
    }
  }

  async function handleRemoveItem(
    item: CartSummaryItem,
    options: { rethrow?: boolean } = {},
  ) {
    const lineKey = cartLineKey(item)

    setUpdatingLineKey(lineKey)
    clearLineError(lineKey)

    try {
      await removeItem(item.product_id, item.product_variant_id)
      toast({ title: quantityCopy.itemRemoved })
    } catch (nextError) {
      const message = getLocalizedCartQuantityErrorMessage(
        nextError,
        messages.common.errors,
        quantityCopy,
      )

      setLineErrors((currentErrors) => ({
        ...currentErrors,
        [lineKey]: message,
      }))
      clearError()
      toast({
        title: quantityCopy.unableToUpdateQuantity,
        description: message,
        variant: "destructive",
      })

      if (options.rethrow) {
        throw nextError
      }
    } finally {
      setUpdatingLineKey(null)
    }
  }

  if (!cart || cart.items.length === 0) {
    return (
      <div className="mx-auto max-w-5xl px-6 py-20 lg:px-8">
        <div className="rounded-[2rem] border border-border/60 bg-card p-10 text-center">
          <ShoppingBag className="mx-auto size-10 text-muted-foreground" />
          <h1 className="mt-6 font-serif text-4xl text-foreground">
            {messages.common.empty.cart.title}
          </h1>
          <p className="mx-auto mt-4 max-w-2xl text-muted-foreground">
            {messages.common.empty.cart.description}
          </p>
          <Button asChild className="mt-6">
            <Link href={getLocalizedHref(locale, "store")}>{messages.common.empty.cart.cta}</Link>
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
          <AlertDialog>
            <AlertDialogTrigger asChild>
              <Button type="button" variant="outline" disabled={loading}>
                {t.clearCart}
              </Button>
            </AlertDialogTrigger>
            <AlertDialogContent>
              <AlertDialogHeader>
                <AlertDialogTitle>
                  {messages.common.confirm.clearCart.title}
                </AlertDialogTitle>
                <AlertDialogDescription>
                  {messages.common.confirm.clearCart.description}
                </AlertDialogDescription>
              </AlertDialogHeader>
              <AlertDialogFooter>
                <AlertDialogCancel>
                  {messages.common.confirm.clearCart.cancel}
                </AlertDialogCancel>
                <AlertDialogAction
                  onClick={() => {
                    void clearCart()
                  }}
                >
                  {messages.common.confirm.clearCart.confirm}
                </AlertDialogAction>
              </AlertDialogFooter>
            </AlertDialogContent>
          </AlertDialog>
        </div>

        {error ? (
          <div className="mb-6 rounded-2xl bg-destructive/10 px-4 py-3 text-sm text-destructive">
            {error}
          </div>
        ) : null}

        <div className="grid gap-8 xl:grid-cols-[1.12fr_0.88fr]">
          <section className="space-y-4">
            {cart.items.map((item) => {
              const lineKey = cartLineKey(item)
              const maxQuantity =
                item.max_quantity ?? getCartItemQuantityLimit(item, 10)

              return (
              <article
                key={lineKey}
                className="rounded-[2rem] border border-border/60 bg-card p-5"
              >
                <div className="flex gap-4">
                  <div className="relative h-24 w-24 shrink-0 overflow-hidden rounded-3xl bg-muted">
                    <Image
                      src={item.product?.primary_image_url || item.product?.image_url || "/placeholder.jpg"}
                      alt={item.product?.name || t.productUnavailable}
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
                        {item.variant_title || Object.keys(item.option_values ?? {}).length ? (
                          <p className="mt-2 text-sm text-muted-foreground">
                            {[item.variant_title, item.variant_sku ? `SKU ${item.variant_sku}` : null]
                              .filter(Boolean)
                              .join(" | ")}
                          </p>
                        ) : null}
                        {Object.keys(item.option_values ?? {}).length ? (
                          <div className="mt-2 flex flex-wrap gap-2 text-xs text-muted-foreground">
                            {Object.entries(item.option_values ?? {}).map(([key, value]) => (
                              <span key={key} className="rounded-full bg-muted px-2 py-1">
                                {key.replace(/_/g, " ")}: {String(value).replace(/_/g, " ")}
                              </span>
                            ))}
                          </div>
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
                            item.currency ?? item.product?.currency ?? "NZD",
                          )}{" "}
                          {t.each}
                        </p>
                        <p className="mt-2 text-lg font-medium text-foreground">
                          {formatCurrencyAmount(
                            item.line_total,
                            locale,
                            item.currency ?? item.product?.currency ?? "NZD",
                          )}
                        </p>
                      </div>
                    </div>

                    <div className="mt-5 flex flex-wrap items-center justify-between gap-3">
                      <CartQuantityControl
                        quantity={item.quantity}
                        min={0}
                        max={maxQuantity}
                        disabled={loading && updatingLineKey !== lineKey}
                        loading={updatingLineKey === lineKey}
                        error={lineErrors[lineKey] ?? item.quantity_error_message}
                        onCommit={(nextQuantity) =>
                          handleQuantityCommit(item, nextQuantity)
                        }
                        onRemove={() =>
                          handleRemoveItem(item, { rethrow: true })
                        }
                        labels={{
                          quantityInput: quantityCopy.quantityInputLabel,
                          decreaseQuantity: quantityCopy.decreaseQuantity,
                          increaseQuantity: quantityCopy.increaseQuantity,
                          quantityUnavailable: quantityCopy.quantityUnavailable,
                          onlyAvailable: quantityCopy.onlyAvailable,
                          enterValidQuantity: quantityCopy.enterValidQuantity,
                          maxQuantityReached: quantityCopy.maxQuantityReached,
                          updatingQuantity: quantityCopy.updatingQuantity,
                        }}
                      />

                      <button
                        type="button"
                        className="inline-flex items-center gap-2 text-sm text-muted-foreground transition-colors hover:text-foreground"
                        onClick={() => {
                          void handleRemoveItem(item)
                        }}
                        disabled={updatingLineKey === lineKey}
                      >
                        <Trash2 className="size-4" />
                        {t.remove}
                      </button>
                    </div>
                  </div>
                </div>
              </article>
              )
            })}
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
                <span className="text-muted-foreground">{t.shipping}</span>
                <span className="text-foreground">{t.shippingCalculatedAtCheckout}</span>
              </div>
              <div className="flex items-center justify-between">
                <span className="text-muted-foreground">
                  {cart.tax_label ?? t.gstIncluded}
                </span>
                <span className="text-foreground">
                  {formatCurrencyAmount(cart.estimated_tax_usd, locale)}
                </span>
              </div>
              <div className="flex items-center justify-between border-t border-border/60 pt-4 text-base font-medium">
                <span className="text-foreground">{t.subtotal}</span>
                <span className="text-foreground">
                  {formatCurrencyAmount(cart.subtotal_usd, locale)}
                </span>
              </div>
            </div>

            <div className="mt-6 rounded-3xl bg-background p-5 text-sm leading-relaxed text-muted-foreground">
              <p>{t.shippingAtCheckoutNote}</p>
              <p className="mt-2">{t.noAccountRequired}</p>
            </div>

            <div className="mt-8 space-y-3">
              <Button
                type="button"
                className="w-full"
                onClick={() => {
                  router.push(getLocalizedHref(locale, "store/checkout"))
                }}
              >
                {session.user ? t.proceedToCheckout : t.guestCheckout}
              </Button>
              {!session.user ? (
                <>
                  <p className="text-xs leading-relaxed text-muted-foreground">
                    {t.guestCheckoutHint}
                  </p>
                  <Button
                    type="button"
                    variant="outline"
                    className="w-full"
                    onClick={() => setIsAuthOpen(true)}
                  >
                    {t.signInToCheckout}
                  </Button>
                </>
              ) : null}
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
