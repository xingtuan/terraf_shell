import type { Metadata } from "next"

import { LegalPage } from "@/components/legal/legal-page"
import { getMessages } from "@/lib/i18n"
import { resolveLocale } from "@/lib/resolve-locale"

type PrivacyPageProps = {
  params: Promise<{ locale: string }>
}

export async function generateMetadata({
  params,
}: PrivacyPageProps): Promise<Metadata> {
  const locale = await resolveLocale(params)
  const content = getMessages(locale).legal.privacy

  return {
    title: content.metaTitle,
    description: content.metaDescription,
  }
}

export default async function PrivacyPage({ params }: PrivacyPageProps) {
  const locale = await resolveLocale(params)
  const content = getMessages(locale).legal.privacy

  return <LegalPage content={content} />
}
