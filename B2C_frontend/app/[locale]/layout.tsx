import type { Metadata } from "next"

import { AppProviders } from "@/components/app-providers"
import { Footer } from "@/components/footer"
import { Header } from "@/components/header"
import { LocaleHtmlSync } from "@/components/locale-html-sync"
import { findHomeSection, getHomeSections } from "@/lib/api/homepage"
import { getServerApiBaseUrl } from "@/lib/api/server-base-url"
import { getMessages, locales, type Locale, type SiteMessages } from "@/lib/i18n"
import { buildFooterContent } from "@/lib/page-content"
import { resolveLocale } from "@/lib/resolve-locale"

type LocaleLayoutProps = {
  children: React.ReactNode
  params: Promise<{ locale: string }>
}

export function generateStaticParams() {
  return locales.map((locale) => ({ locale }))
}

export async function generateMetadata({
  params,
}: LocaleLayoutProps): Promise<Metadata> {
  const locale = await resolveLocale(params)
  const messages = getMessages(locale)

  return {
    title: messages.meta.title,
    description: messages.meta.description,
    alternates: {
      languages: {
        en: "/en",
        ko: "/ko",
        zh: "/zh",
      },
    },
  }
}

async function loadFooterContent(
  locale: Locale,
  fallback: SiteMessages["footer"],
  headerFallback: SiteMessages["header"],
) {
  try {
    const apiBaseUrl = await getServerApiBaseUrl()
    const sections = await getHomeSections({ baseUrl: apiBaseUrl, locale })

    return buildFooterContent(
      fallback,
      findHomeSection(sections, "footer"),
      locale,
      headerFallback,
    )
  } catch {
    return fallback
  }
}

export default async function LocaleLayout({
  children,
  params,
}: LocaleLayoutProps) {
  const locale = await resolveLocale(params)
  const messages = getMessages(locale)
  const footer = await loadFooterContent(locale, messages.footer, messages.header)

  return (
    <>
      <LocaleHtmlSync locale={locale} />
      <AppProviders>
        <Header
          locale={locale}
          header={messages.header}
          languageSwitcher={messages.languageSwitcher}
        />
        <main className="min-h-screen pt-20">{children}</main>
        <Footer locale={locale} header={messages.header} footer={footer} />
      </AppProviders>
    </>
  )
}
