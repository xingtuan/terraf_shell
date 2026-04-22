"use client"

import { useState } from "react"
import Image from "next/image"

import type { ProductImage } from "@/lib/types"

type ProductGalleryProps = {
  title: string
  images: ProductImage[]
}

export function ProductGallery({ title, images }: ProductGalleryProps) {
  const [activeIndex, setActiveIndex] = useState(0)
  const galleryImages =
    images.length > 0
      ? images
      : [
          {
            id: 0,
            alt_text: title,
            media_url: "/placeholder.jpg",
            sort_order: 0,
          },
        ]

  const activeImage = galleryImages[activeIndex] ?? galleryImages[0]

  return (
    <div className="space-y-4">
      <div className="relative overflow-hidden rounded-[2rem] border border-border/60 bg-card">
        <div className="relative min-h-[520px] bg-[radial-gradient(circle_at_top,rgba(255,255,255,0.4),transparent_60%)]">
          <Image
            src={activeImage.media_url || "/placeholder.jpg"}
            alt={activeImage.alt_text || title}
            fill
            className="object-cover"
          />
        </div>
      </div>

      {galleryImages.length > 1 ? (
        <div className="grid grid-cols-4 gap-3">
          {galleryImages.map((image, index) => {
            const isActive = index === activeIndex

            return (
              <button
                key={`${image.id}-${index}`}
                type="button"
                className={`relative overflow-hidden rounded-2xl border transition-colors ${
                  isActive
                    ? "border-foreground shadow-[0_0_0_1px_rgba(0,0,0,0.06)]"
                    : "border-border/60 hover:border-foreground/40"
                }`}
                onClick={() => setActiveIndex(index)}
              >
                <div className="relative aspect-[4/4] bg-muted">
                  <Image
                    src={image.media_url || "/placeholder.jpg"}
                    alt={image.alt_text || title}
                    fill
                    className="object-cover"
                  />
                </div>
              </button>
            )
          })}
        </div>
      ) : null}
    </div>
  )
}
