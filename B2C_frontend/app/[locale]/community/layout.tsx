import { getMessages } from "@/lib/i18n"
import { resolveLocale } from "@/lib/resolve-locale"
import { CommunityHeaderBar } from "@/components/community/community-header-bar"

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

  return (
    <div className="pt-20">
      <CommunityHeaderBar locale={locale} messages={messages.community} />
      {children}
    </div>
  )
}
