import { redirect } from "next/navigation"

import { getLocalizedHref } from "@/lib/i18n"
import { resolveLocale } from "@/lib/resolve-locale"

type LegacyCommunityProfileRoutePageProps = {
  params: Promise<{ locale: string; username: string }>
}

export default async function LegacyCommunityProfileRoutePage({
  params,
}: LegacyCommunityProfileRoutePageProps) {
  const locale = await resolveLocale(params)
  const resolvedParams = await params

  redirect(getLocalizedHref(locale, `community/u/${resolvedParams.username}`))
}
