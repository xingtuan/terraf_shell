import type { Metadata } from "next"

import { LegalPage } from "@/components/legal/legal-page"
import { getMessages } from "@/lib/i18n"
import { resolveLocale } from "@/lib/resolve-locale"

type TermsPageProps = {
  params: Promise<{ locale: string }>
}

export async function generateMetadata({
  params,
}: TermsPageProps): Promise<Metadata> {
  const locale = await resolveLocale(params)
  const content = getMessages(locale).legal.terms

  return {
    title: content.metaTitle,
    description: content.metaDescription,
  }
}

export default async function TermsPage({ params }: TermsPageProps) {
  const locale = await resolveLocale(params)
  const content = getMessages(locale).legal.terms

  return <LegalPage content={content} />
}
