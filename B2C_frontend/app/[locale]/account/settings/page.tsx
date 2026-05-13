import { redirect } from "next/navigation"
import { resolveLocale } from "@/lib/resolve-locale"
import { getLocalizedHref } from "@/lib/i18n"

type AccountSettingsRoutePageProps = {
  params: Promise<{ locale: string }>
}

export default async function AccountSettingsRoutePage({
  params,
}: AccountSettingsRoutePageProps) {
  const locale = await resolveLocale(params)
  redirect(getLocalizedHref(locale, "account/profile"))
}
