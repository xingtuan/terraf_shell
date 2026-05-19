import { redirect } from "next/navigation"

import {
  defaultLocale,
  getLocalizedHref,
  isValidLocale,
  type Locale,
} from "@/lib/i18n"

type RootResetPasswordPageProps = {
  searchParams?: Promise<Record<string, string | string[] | undefined>>
}

function firstParam(value: string | string[] | undefined) {
  return Array.isArray(value) ? value[0] : value
}

function queryStringWithoutLocale(
  searchParams: Record<string, string | string[] | undefined>,
) {
  const params = new URLSearchParams()

  for (const [key, value] of Object.entries(searchParams)) {
    if (key === "locale" || value === undefined) continue

    if (Array.isArray(value)) {
      value.forEach((item) => params.append(key, item))
    } else {
      params.set(key, value)
    }
  }

  const queryString = params.toString()

  return queryString ? `?${queryString}` : ""
}

export default async function RootResetPasswordPage({
  searchParams,
}: RootResetPasswordPageProps) {
  const resolvedSearchParams = (await searchParams) ?? {}
  const localeParam = firstParam(resolvedSearchParams.locale)
  const locale: Locale =
    localeParam && isValidLocale(localeParam) ? localeParam : defaultLocale

  redirect(
    `${getLocalizedHref(locale, "auth/reset-password")}${queryStringWithoutLocale(
      resolvedSearchParams,
    )}`,
  )
}
