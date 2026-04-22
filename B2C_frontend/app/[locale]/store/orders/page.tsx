import { redirect } from "next/navigation"

import { getLocalizedHref } from "@/lib/i18n"
import { resolveLocale } from "@/lib/resolve-locale"

type StoreOrdersRedirectPageProps = {
  params: Promise<{ locale: string }>
}

export default async function StoreOrdersRedirectPage({
  params,
}: StoreOrdersRedirectPageProps) {
  const locale = await resolveLocale(params)

  redirect(getLocalizedHref(locale, "account/orders"))
}
