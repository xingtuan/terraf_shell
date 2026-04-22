import { AccountProfilePage as AccountProfileScreen } from "@/components/account/account-profile-page"
import { resolveLocale } from "@/lib/resolve-locale"

type AccountProfilePageProps = {
  params: Promise<{ locale: string }>
}

export default async function AccountProfilePage({
  params,
}: AccountProfilePageProps) {
  const locale = await resolveLocale(params)

  return <AccountProfileScreen locale={locale} />
}
