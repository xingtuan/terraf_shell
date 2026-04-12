import { notFound } from "next/navigation"

import { isValidLocale, type Locale } from "@/lib/i18n"

export async function resolveLocale(
  params: Promise<{ locale: string }>,
): Promise<Locale> {
  const { locale } = await params

  if (!isValidLocale(locale)) {
    notFound()
  }

  return locale
}
