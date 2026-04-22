import { AccountSettingsPage } from "@/components/account/account-settings-page"
import { resolveLocale } from "@/lib/resolve-locale"

type AccountSettingsRoutePageProps = {
  params: Promise<{ locale: string }>
}

export default async function AccountSettingsRoutePage({
  params,
}: AccountSettingsRoutePageProps) {
  const locale = await resolveLocale(params)

  return <AccountSettingsPage locale={locale} />
}
