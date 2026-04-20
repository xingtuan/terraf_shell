"use client"

import Image from "next/image"
import Link from "next/link"

import { Button } from "@/components/ui/button"
import { formatProductPrice } from "@/lib/api/products"
import { getLocalizedHref, type Locale } from "@/lib/i18n"
import type { Product } from "@/lib/types"
import { useCart } from "@/hooks/useCart"

type ProductCardProps = {
  locale: Locale
  product: Product
}

const categoryLabels: Record<string, string> = {
  tableware: "Tableware",
  planters: "Planters",
  wellness_interior: "Wellness & Interior",
  architectural: "Architectural",
}

const modelLabels: Record<string, string> = {
  lite_15: "1.5 Lite",
  heritage_16: "1.6 Heritage",
}

const colorLabels: Record<string, string> = {
  ocean_bone: "Ocean Bone",
  forged_ash: "Forged Ash",
}

export function ProductCard({ locale, product }: ProductCardProps) {
  const { addItem } = useCart()
  const productHref = getLocalizedHref(locale, `store/${product.slug}`)

  return (
    <article className="overflow-hidden rounded-3xl border border-border/60 bg-card transition-transform duration-300 hover:-translate-y-1">
      <Link href={productHref} className="block">
        <div className="relative min-h-[280px] overflow-hidden bg-muted">
          <Image
            src={product.image_url || "/placeholder.jpg"}
            alt={product.name}
            fill
            className="object-cover"
          />
        </div>
      </Link>

      <div className="space-y-4 p-6">
        <div className="flex items-center justify-between gap-4">
          <span className="rounded-full bg-primary/10 px-3 py-1 text-xs uppercase tracking-[0.18em] text-primary">
            {categoryLabels[product.category] || product.category}
          </span>
          <span
            className={`text-sm ${
              product.in_stock ? "text-emerald-600" : "text-red-600"
            }`}
          >
            {product.in_stock ? "In Stock" : "Out of Stock"}
          </span>
        </div>

        <div>
          <Link href={productHref} className="transition-colors hover:text-primary">
            <h3 className="font-serif text-2xl text-foreground">{product.name}</h3>
          </Link>
          <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
            {(modelLabels[product.model] || product.model)} /{" "}
            {(colorLabels[product.color] || product.color)}
          </p>
        </div>

        <div className="flex items-center justify-between gap-4 border-t border-border/60 pt-4">
          <p className="text-sm font-medium text-foreground">
            {formatProductPrice(product, locale)} USD
          </p>
          <div className="flex gap-3">
            <Button asChild variant="outline">
              <Link href={productHref}>View Details</Link>
            </Button>
            <Button
              type="button"
              disabled={!product.in_stock}
              onClick={() => {
                void addItem(product.id, 1)
              }}
            >
              Add to Cart
            </Button>
          </div>
        </div>
      </div>
    </article>
  )
}
