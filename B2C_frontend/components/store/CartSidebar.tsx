"use client"

import { useState } from "react"
import Image from "next/image"
import Link from "next/link"
import { useRouter } from "next/navigation"
import { Minus, Plus, ShoppingBag, Trash2 } from "lucide-react"

import { CommunityAuthPanel } from "@/components/community/community-auth-panel"
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
import { getProductQuantityLimit } from "@/lib/store/product-display"
import { useAuthSession } from "@/hooks/use-auth-session"
import { useCart } from "@/hooks/useCart"

type CartSidebarProps = {
  locale: Locale
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
    updateItem,
    removeItem,
    clearCart,
  } =
    useCart()
  const messages = getMessages(locale)
  const authCopy = messages.community.auth
  const t = messages.cartSidebar
  const [isAuthOpen, setIsAuthOpen] = useState(false)

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
                {cart.items.map((item) => (
                  <article
                    key={item.product_id}
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
                          </div>
                          <p className="text-sm font-medium text-foreground">
                            {formatCurrencyAmount(
                              item.line_total,
                              locale,
                              item.product?.currency ?? "USD",
                            )}
                          </p>
                        </div>

                        <div className="mt-4 flex items-center justify-between gap-3">
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
                                const maxQuantity = item.product
                                  ? getProductQuantityLimit(item.product, 10)
                                  : 10

                                if (item.quantity >= maxQuantity) {
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
                <Button
                  type="button"
                  className="w-full"
                  disabled={!cart || cart.items.length === 0 || loading}
                  onClick={() => {
                    const checkoutHref = getLocalizedHref(locale, "store/checkout")

                    if (!session.user) {
                      setIsAuthOpen(true)
                      return
                    }

                    closeCart()
                    router.push(checkoutHref)
                  }}
                >
                  {session.user ? t.proceedToCheckout : t.signInToCheckout}
                </Button>
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
