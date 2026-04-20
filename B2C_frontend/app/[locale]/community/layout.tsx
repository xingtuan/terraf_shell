import { getMessages } from "@/lib/i18n"
import { resolveLocale } from "@/lib/resolve-locale"
import { CommunityHeaderBar } from "@/components/community/community-header-bar"
import { getPageContent } from "@/lib/api/content"
import { getServerApiBaseUrl } from "@/lib/api/server-base-url"

type CommunityLayoutProps = {
  children: React.ReactNode
  params: Promise<{ locale: string }>
}

export default async function CommunityLayout({
  children,
  params,
}: CommunityLayoutProps) {
  const locale = await resolveLocale(params)
  const messages = getMessages(locale)
  const apiBaseUrl = await getServerApiBaseUrl()
  const content = await getPageContent("community", locale, { baseUrl: apiBaseUrl })

  return (
    <div className="pt-20">
      <CommunityHeaderBar
        locale={locale}
        messages={messages.community}
        heroSection={content.hero ?? null}
      />
      {children}
    </div>
  )
}
