import { AccountOverviewPage } from "@/components/account/account-overview-page"
import { resolveLocale } from "@/lib/resolve-locale"

type AccountPageProps = {
  params: Promise<{ locale: string }>
}

export default async function AccountPage({ params }: AccountPageProps) {
  const locale = await resolveLocale(params)

  return <AccountOverviewPage locale={locale} />
}
