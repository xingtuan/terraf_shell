import { redirect } from "next/navigation"

import { getLocalizedHref } from "@/lib/i18n"
import { resolveLocale } from "@/lib/resolve-locale"

type AccountProfilePageProps = {
  params: Promise<{ locale: string }>
}

export default async function AccountProfilePage({
  params,
}: AccountProfilePageProps) {
  const locale = await resolveLocale(params)

  redirect(getLocalizedHref(locale, "account"))
}
