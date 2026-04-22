"use client"

import { use } from "react"

import { StoreCartPage } from "@/components/store/store-cart-page"
import { isValidLocale } from "@/lib/i18n"

type StoreCartRouteProps = {
  params: Promise<{ locale: string }>
}

export default function CartPage({ params }: StoreCartRouteProps) {
  const resolvedParams = use(params)
  const locale = isValidLocale(resolvedParams.locale) ? resolvedParams.locale : "en"

  return <StoreCartPage locale={locale} />
}
