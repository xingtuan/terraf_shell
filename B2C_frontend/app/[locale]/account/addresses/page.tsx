import { AccountAddressesPage } from "@/components/account/account-addresses-page"
import { resolveLocale } from "@/lib/resolve-locale"

type AccountAddressesPageProps = {
  params: Promise<{ locale: string }>
}

export default async function AccountAddressesPageRoute({
  params,
}: AccountAddressesPageProps) {
  const locale = await resolveLocale(params)

  return <AccountAddressesPage locale={locale} />
}
