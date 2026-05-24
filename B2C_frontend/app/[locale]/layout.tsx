import type { Metadata } from "next"

import { AppProviders } from "@/components/app-providers"
import { Footer } from "@/components/footer"
import { Header } from "@/components/header"
import { LocaleHtmlSync } from "@/components/locale-html-sync"
import { MaintenancePage } from "@/components/maintenance-page"
import { findHomeSection, getHomeSections } from "@/lib/api/homepage"
import { getPublicSettings, defaultBranding, type Branding } from "@/lib/api/public-settings"
import { getServerApiBaseUrl } from "@/lib/api/server-base-url"
import { hasPublishedCmsSection } from "@/lib/cms-section-visibility"
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
    const footerSection = findHomeSection(sections, "footer")

    return hasPublishedCmsSection(footerSection)
      ? buildFooterContent(fallback, footerSection, locale, headerFallback)
      : null
  } catch {
    return null
  }
}

async function loadBrandingAndMaintenanceMode(): Promise<{
  branding: Branding
  maintenanceEnabled: boolean
}> {
  try {
    const apiBaseUrl = await getServerApiBaseUrl()
    const settings = await getPublicSettings(apiBaseUrl)
    return {
      branding: settings.branding ?? defaultBranding,
      maintenanceEnabled: settings.maintenance_mode?.enabled ?? false,
    }
  } catch {
    return { branding: defaultBranding, maintenanceEnabled: false }
  }
}

export default async function LocaleLayout({
  children,
  params,
}: LocaleLayoutProps) {
  const locale = await resolveLocale(params)
  const messages = getMessages(locale)

  const [{ branding, maintenanceEnabled }, footer] = await Promise.all([
    loadBrandingAndMaintenanceMode(),
    loadFooterContent(locale, messages.footer, messages.header),
  ])

  if (maintenanceEnabled) {
    return (
      <>
        <LocaleHtmlSync locale={locale} />
        <MaintenancePage messages={messages.maintenance} branding={branding} />
      </>
    )
  }

  return (
    <>
      <LocaleHtmlSync locale={locale} />
      <AppProviders>
        <Header
          locale={locale}
          header={messages.header}
          languageSwitcher={messages.languageSwitcher}
          branding={branding}
        />
        <main className="min-h-screen pt-20">{children}</main>
        {footer ? (
          <Footer locale={locale} header={messages.header} footer={footer} branding={branding} />
        ) : null}
      </AppProviders>
    </>
  )
}
