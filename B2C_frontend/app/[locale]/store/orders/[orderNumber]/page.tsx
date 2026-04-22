import { redirect } from "next/navigation"

import { getLocalizedHref } from "@/lib/i18n"
import { resolveLocale } from "@/lib/resolve-locale"

type StoreOrderRedirectPageProps = {
  params: Promise<{ locale: string; orderNumber: string }>
}

export default async function StoreOrderRedirectPage({
  params,
}: StoreOrderRedirectPageProps) {
  const locale = await resolveLocale(params)
  const resolvedParams = await params

  redirect(
    getLocalizedHref(locale, `account/orders/${resolvedParams.orderNumber}`),
  )
}
