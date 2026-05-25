import type { Metadata } from "next"
import { notFound } from "next/navigation"

import { LegalPage } from "@/components/legal/legal-page"
import {
  getLegalPageContent,
  hasRenderableLegalPageContent,
} from "@/lib/api/legal-pages"
import { getServerApiBaseUrl } from "@/lib/api/server-base-url"
import { resolveLocale } from "@/lib/resolve-locale"

export const dynamic = "force-dynamic"
export const revalidate = 0

type TermsPageProps = {
  params: Promise<{ locale: string }>
}

export async function generateMetadata({
  params,
}: TermsPageProps): Promise<Metadata> {
  const locale = await resolveLocale(params)
  const content = await getLegalPageContent("terms", {
    baseUrl: await getServerApiBaseUrl(),
    locale,
  })

  return {
    title: content.metaTitle ?? content.title ?? undefined,
    description: content.metaDescription ?? content.description ?? undefined,
  }
}

export default async function TermsPage({ params }: TermsPageProps) {
  const locale = await resolveLocale(params)
  const content = await getLegalPageContent("terms", {
    baseUrl: await getServerApiBaseUrl(),
    locale,
  })

  if (!hasRenderableLegalPageContent(content)) {
    notFound()
  }

  return <LegalPage content={content} />
}
