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
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetFooter,
  SheetHeader,
  SheetTitle,
} from "@/components/ui/sheet"
import { getMessages, getLocalizedHref, type Locale } from "@/lib/i18n"
import { useAuthSession } from "@/hooks/use-auth-session"
import { useCart } from "@/hooks/useCart"

type CartSidebarProps = {
  locale: Locale
}

export function CartSidebar({ locale }: CartSidebarProps) {
  const router = useRouter()
  const session = useAuthSession()
  const { cart, error, isOpen, openCart, closeCart, addItem, updateItem, removeItem } =
    useCart()
  const authCopy = getMessages(locale).community.auth
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
              Your Cart ({cart?.item_count ?? 0} items)
            </SheetTitle>
            <SheetDescription>
              Review quantities and move straight into the Shellfin pre-order flow.
            </SheetDescription>
          </SheetHeader>

          <div className="flex-1 overflow-y-auto px-6 py-6">
            {error ? (
              <div className="mb-4 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {error}
              </div>
            ) : null}

            {!cart || cart.items.length === 0 ? (
              <div className="rounded-3xl border border-dashed border-border/70 bg-card p-8 text-center">
                <h3 className="font-serif text-2xl text-foreground">
                  Your cart is empty
                </h3>
                <p className="mt-3 text-sm text-muted-foreground">
                  Add a Shellfin piece to begin your order request.
                </p>
                <Button asChild className="mt-6">
                  <Link href={getLocalizedHref(locale, "store")}>
                    Browse our collection
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
                          src={item.product?.image_url || "/placeholder.jpg"}
                          alt={item.product?.name || "Shellfin product"}
                          fill
                          className="object-cover"
                        />
                      </div>
                      <div className="min-w-0 flex-1">
                        <div className="flex items-start justify-between gap-3">
                          <div>
                            <p className="line-clamp-2 text-sm font-medium text-foreground">
                              {item.product?.name || "Product unavailable"}
                            </p>
                            <p className="mt-1 text-sm text-muted-foreground">
                              ${item.unit_price_usd} USD
                            </p>
                          </div>
                          <p className="text-sm font-medium text-foreground">
                            ${item.line_total}
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
                              aria-label="Decrease quantity"
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
                              aria-label="Increase quantity"
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
                            Remove
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
                <span className="text-muted-foreground">Subtotal</span>
                <span className="font-medium text-foreground">
                  ${cart?.subtotal_usd ?? "0.00"} USD
                </span>
              </div>
              <p className="text-xs uppercase tracking-[0.18em] text-muted-foreground">
                Free shipping over $200
              </p>
              <Button
                type="button"
                className="w-full"
                disabled={!cart || cart.items.length === 0}
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
                Proceed to Checkout
              </Button>
            </div>
          </SheetFooter>
        </SheetContent>
      </Sheet>

      <Dialog open={isAuthOpen} onOpenChange={setIsAuthOpen}>
        <DialogContent className="max-w-2xl border-none bg-transparent p-0 shadow-none">
          <DialogTitle className="sr-only">Sign in to continue</DialogTitle>
          <DialogDescription className="sr-only">
            Sign in with the same Shellfin account used across the community.
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
