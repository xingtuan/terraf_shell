import type { Metadata } from "next"

import { LegalPage } from "@/components/legal/legal-page"
import { getLegalPageContent } from "@/lib/api/legal-pages"
import { getServerApiBaseUrl } from "@/lib/api/server-base-url"
import { getMessages } from "@/lib/i18n"
import { resolveLocale } from "@/lib/resolve-locale"

type PrivacyPageProps = {
  params: Promise<{ locale: string }>
}

export async function generateMetadata({
  params,
}: PrivacyPageProps): Promise<Metadata> {
  const locale = await resolveLocale(params)
  const content = await getLegalPageContent(
    "privacy",
    getMessages(locale).legal.privacy,
    {
      baseUrl: await getServerApiBaseUrl(),
      locale,
    },
  )

  return {
    title: content.metaTitle,
    description: content.metaDescription,
  }
}

export default async function PrivacyPage({ params }: PrivacyPageProps) {
  const locale = await resolveLocale(params)
  const content = await getLegalPageContent(
    "privacy",
    getMessages(locale).legal.privacy,
    {
      baseUrl: await getServerApiBaseUrl(),
      locale,
    },
  )

  return <LegalPage content={content} />
}
