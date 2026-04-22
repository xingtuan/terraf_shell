"use client"

import Link from "next/link"
import { ShoppingBag } from "lucide-react"

import { Button } from "@/components/ui/button"
import { getLocalizedHref, type Locale } from "@/lib/i18n"
import { useCart } from "@/hooks/useCart"

type StoreNavProps = {
  locale: Locale
}

export function StoreNav({ locale }: StoreNavProps) {
  const { cart, openCart } = useCart()

  return (
    <section className="sticky top-20 z-40 border-b border-border/60 bg-background/95 backdrop-blur-md">
      <div className="mx-auto flex max-w-7xl flex-col gap-4 px-6 py-4 lg:flex-row lg:items-center lg:justify-between lg:px-8">
        <div>
          <p className="text-sm uppercase tracking-[0.2em] text-primary">Store</p>
          <div className="mt-2 flex flex-wrap items-center gap-4 text-sm text-muted-foreground">
            <Link
              href={getLocalizedHref(locale, "store")}
              className="transition-colors hover:text-foreground"
            >
              Collection
            </Link>
            <Link
              href={getLocalizedHref(locale, "store/cart")}
              className="transition-colors hover:text-foreground"
            >
              Cart
            </Link>
            <Link
              href={getLocalizedHref(locale, "store/orders")}
              className="transition-colors hover:text-foreground"
            >
              Orders
            </Link>
            <Link
              href={getLocalizedHref(locale, "account")}
              className="transition-colors hover:text-foreground"
            >
              Account
            </Link>
          </div>
        </div>

        <Button
          type="button"
          variant="outline"
          className="w-full justify-center gap-3 rounded-full sm:w-auto"
          onClick={() => openCart()}
        >
          <ShoppingBag className="size-4" />
          <span>Cart</span>
          <span className="rounded-full bg-foreground px-2 py-0.5 text-xs text-background">
            {cart?.item_count ?? 0}
          </span>
        </Button>
      </div>
    </section>
  )
}
