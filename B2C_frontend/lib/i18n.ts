import en from "@/messages/en.json"
import ko from "@/messages/ko.json"
import zh from "@/messages/zh.json"

export const locales = ["en", "ko", "zh"] as const

export type Locale = (typeof locales)[number]
export type SiteMessages = typeof en
export type LocalizedValue<T> = Record<Locale, T>

export const defaultLocale: Locale = "en"

const dictionaries = {
  en,
  ko,
  zh,
} satisfies Record<Locale, SiteMessages>

const intlLocaleMap: Record<Locale, string> = {
  en: "en-US",
  ko: "ko-KR",
  zh: "zh-CN",
}

export function isValidLocale(value: string): value is Locale {
  return locales.includes(value as Locale)
}

export function getMessages(locale: Locale): SiteMessages {
  return dictionaries[locale]
}

export function pickLocalizedValue<T>(
  value: LocalizedValue<T>,
  locale: Locale,
): T {
  return value[locale]
}

export function getLocalizedHref(locale: Locale, slug = ""): string {
  const normalized = slug.replace(/^\/+|\/+$/g, "")

  return normalized ? `/${locale}/${normalized}` : `/${locale}`
}

export function getIntlLocale(locale: Locale): string {
  return intlLocaleMap[locale]
}
