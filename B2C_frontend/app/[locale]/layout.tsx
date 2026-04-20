import type { Metadata } from "next"

import { AppProviders } from "@/components/app-providers"
import { Footer } from "@/components/footer"
import { Header } from "@/components/header"
import { LocaleHtmlSync } from "@/components/locale-html-sync"
import { getMessages, locales } from "@/lib/i18n"
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

export default async function LocaleLayout({
  children,
  params,
}: LocaleLayoutProps) {
  const locale = await resolveLocale(params)
  const messages = getMessages(locale)

  return (
    <>
      <LocaleHtmlSync locale={locale} />
      <AppProviders>
        <Header
          locale={locale}
          header={messages.header}
          languageSwitcher={messages.languageSwitcher}
        />
        <main className="min-h-screen">{children}</main>
        <Footer locale={locale} header={messages.header} footer={messages.footer} />
      </AppProviders>
    </>
  )
}
