import { redirect } from "next/navigation"

import { getLocalizedHref } from "@/lib/i18n"
import { resolveLocale } from "@/lib/resolve-locale"

type AccountOrdersRoutePageProps = {
  params: Promise<{ locale: string }>
}

export default async function AccountOrdersRoutePage({
  params,
}: AccountOrdersRoutePageProps) {
  const locale = await resolveLocale(params)

  redirect(getLocalizedHref(locale, "account/store"))
}
