import { requestApi } from "@/lib/api/client"

export type LegalPageKey = "privacy" | "terms"

export type LegalPageContent = {
  metaTitle?: string | null
  metaDescription?: string | null
  eyebrow?: string | null
  title?: string | null
  description?: string | null
  lastUpdatedLabel?: string | null
  lastUpdated?: string | null
  bodyHtml?: string | null
}

type GetLegalPageOptions = {
  baseUrl?: string
  locale?: string
}

export async function getLegalPageContent(
  page: LegalPageKey,
  options: GetLegalPageOptions = {},
): Promise<LegalPageContent> {
  const response = await requestApi<LegalPageContent>(
    `/legal-pages/${encodeURIComponent(page)}`,
    {
      query: {
        locale: options.locale,
      },
      baseUrl: options.baseUrl,
      cache: "no-store",
    },
  )

  return nonBlankLegalContent(response.data ?? {})
}

export function hasRenderableLegalPageContent(content: LegalPageContent) {
  return Boolean(
    content.eyebrow ||
      content.title ||
      content.description ||
      content.lastUpdated ||
      content.lastUpdatedLabel ||
      content.bodyHtml,
  )
}

function nonBlankLegalContent(content: LegalPageContent): LegalPageContent {
  const result: LegalPageContent = {}

  for (const [key, value] of Object.entries(content) as Array<
    [keyof LegalPageContent, string | null | undefined]
  >) {
    if (typeof value === "string" && value.trim().length > 0) {
      result[key] = value
    }
  }

  return result
}
