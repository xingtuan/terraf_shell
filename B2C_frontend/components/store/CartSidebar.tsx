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
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetFooter,
  SheetHeader,
  SheetTitle,
} from "@/components/ui/sheet"
import { getMessages, getLocalizedHref, type Locale } from "@/lib/i18n"
import { getLocalizedCartQuantityErrorMessage } from "@/lib/store/cart-messages"
import { getCartItemQuantityLimit } from "@/lib/store/product-display"
import type { CartSummaryItem } from "@/lib/types"
import { useAuthSession } from "@/hooks/use-auth-session"
import { useCart } from "@/hooks/useCart"
import { toast } from "@/hooks/use-toast"

type CartSidebarProps = {
  locale: Locale
}

function cartLineKey(item: CartSummaryItem) {
  return `${item.product_id}-${item.product_variant_id ?? "default"}`
}

export function CartSidebar({ locale }: CartSidebarProps) {
  const router = useRouter()
  const session = useAuthSession()
  const {
    cart,
    error,
    loading,
    isOpen,
    openCart,
    closeCart,
    clearError,
    updateItem,
    removeItem,
    clearCart,
  } =
    useCart()
  const messages = getMessages(locale)
  const authCopy = messages.community.auth
  const t = messages.cartSidebar
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

  return (
    <>
      <Sheet
        open={isOpen}
        onOpenChange={(nextOpen) => {
          if (nextOpen) {
            openCart()
            return
          }

          closeCart()
        }}
      >
        <SheetContent side="right" className="w-full max-w-xl gap-0 sm:max-w-xl">
          <SheetHeader className="border-b border-border/60 px-6 py-5">
            <SheetTitle className="flex items-center gap-3 text-xl">
              <ShoppingBag className="size-5" />
              {t.title.replace("{count}", String(cart?.item_count ?? 0))}
            </SheetTitle>
            <SheetDescription>
              {t.guestHint}
            </SheetDescription>
          </SheetHeader>

          <div className="flex-1 overflow-y-auto px-6 py-6">
            {error ? (
              <div className="mb-4 rounded-2xl bg-destructive/10 px-4 py-3 text-sm text-destructive">
                {error}
              </div>
            ) : null}

            {!cart || cart.items.length === 0 ? (
              <div className="rounded-3xl border border-dashed border-border/70 bg-card p-8 text-center">
                <h3 className="font-serif text-2xl text-foreground">
                  {messages.common.empty.cart.title}
                </h3>
                <p className="mt-3 text-sm text-muted-foreground">
                  {messages.common.empty.cart.description}
                </p>
                <Button asChild className="mt-6">
                  <Link href={getLocalizedHref(locale, "store")}>
                    {messages.common.empty.cart.cta}
                  </Link>
                </Button>
              </div>
            ) : (
              <div className="space-y-4">
                {cart.items.map((item) => {
                  const lineKey = cartLineKey(item)
                  const maxQuantity =
                    item.max_quantity ?? getCartItemQuantityLimit(item, 10)

                  return (
                  <article
                    key={lineKey}
                    className="rounded-3xl border border-border/60 bg-card p-4"
                  >
                    <div className="flex gap-4">
                      <div className="relative h-[60px] w-[60px] shrink-0 overflow-hidden rounded-2xl bg-muted">
                        <Image
                          src={item.product?.primary_image_url || item.product?.image_url || "/placeholder.jpg"}
                          alt={item.product?.name || "OXP product"}
                          fill
                          className="object-cover"
                        />
                      </div>
                      <div className="min-w-0 flex-1">
                        <div className="flex items-start justify-between gap-3">
                          <div>
                            <p className="line-clamp-2 text-sm font-medium text-foreground">
                              {item.product?.name || t.productUnavailable}
                            </p>
                            {item.product?.stock_status_label ? (
                              <p className="mt-1 text-xs uppercase tracking-[0.18em] text-muted-foreground">
                                {item.product.stock_status_label}
                              </p>
                            ) : null}
                            {item.variant_title || item.variant_sku ? (
                              <p className="mt-1 text-xs text-muted-foreground">
                                {[item.variant_title, item.variant_sku ? `${messages.checkout.productCodeLabel} ${item.variant_sku}` : null]
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

                        <div className="mt-4 flex items-center justify-between gap-3">
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
              </div>
            )}
          </div>

          <SheetFooter className="border-t border-border/60 bg-background/95 px-6 py-5">
            <div className="space-y-4">
              <div className="flex items-center justify-between text-sm">
                <span className="text-muted-foreground">{t.subtotal}</span>
                <span className="font-medium text-foreground">
                  {formatCurrencyAmount(cart?.subtotal_usd ?? "0.00", locale)}
                </span>
              </div>
              <div className="flex items-center justify-between text-sm">
                <span className="text-muted-foreground">{t.estShipping}</span>
                <span className="font-medium text-foreground">
                  {formatCurrencyAmount(
                    cart?.estimated_shipping_usd ?? "0.00",
                    locale,
                  )}
                </span>
              </div>
              <div className="flex items-center justify-between text-sm">
                <span className="text-muted-foreground">{t.estTax}</span>
                <span className="font-medium text-foreground">
                  {formatCurrencyAmount(
                    cart?.estimated_tax_usd ?? "0.00",
                    locale,
                  )}
                </span>
              </div>
              <p className="text-xs uppercase tracking-[0.18em] text-muted-foreground">
                {t.freeShippingOver}{" "}
                {formatCurrencyAmount(
                  cart?.free_shipping_threshold_usd ?? "200.00",
                  locale,
                )}
              </p>
              <div className="grid gap-3">
                <Button asChild variant="outline" className="w-full">
                  <Link href={getLocalizedHref(locale, "store/cart")}>{t.viewCart}</Link>
                </Button>
                {session.user ? (
                  <Button
                    type="button"
                    className="w-full"
                    disabled={!cart || cart.items.length === 0 || loading}
                    onClick={() => {
                      closeCart()
                      router.push(getLocalizedHref(locale, "store/checkout"))
                    }}
                  >
                    {t.proceedToCheckout}
                  </Button>
                ) : (
                  <>
                    <Button
                      type="button"
                      className="w-full"
                      disabled={!cart || cart.items.length === 0 || loading}
                      onClick={() => {
                        closeCart()
                        router.push(getLocalizedHref(locale, "store/checkout"))
                      }}
                    >
                      {t.guestCheckout}
                    </Button>
                    <p className="text-xs leading-relaxed text-muted-foreground">
                      {t.guestCheckoutHint}
                    </p>
                    <Button
                      type="button"
                      variant="outline"
                      className="w-full"
                      disabled={!cart || cart.items.length === 0 || loading}
                      onClick={() => {
                        setIsAuthOpen(true)
                      }}
                    >
                      {t.signInToCheckout}
                    </Button>
                  </>
                )}
                <AlertDialog>
                  <AlertDialogTrigger asChild>
                    <Button
                      type="button"
                      variant="ghost"
                      disabled={!cart || cart.items.length === 0 || loading}
                    >
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
            </div>
          </SheetFooter>
        </SheetContent>
      </Sheet>

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
              closeCart()
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
