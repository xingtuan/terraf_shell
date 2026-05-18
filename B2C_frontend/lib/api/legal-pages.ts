import { requestApi } from "@/lib/api/client"
import type { SiteMessages } from "@/lib/i18n"

export type LegalPageKey = "privacy" | "terms"

export type LegalPageContent = SiteMessages["legal"]["privacy"] & {
  bodyHtml?: string | null
}

type LegalPageOverride = Partial<
  Pick<
    LegalPageContent,
    | "metaTitle"
    | "metaDescription"
    | "eyebrow"
    | "title"
    | "description"
    | "lastUpdatedLabel"
    | "lastUpdated"
    | "bodyHtml"
  >
>

type GetLegalPageOptions = {
  baseUrl?: string
  locale?: string
}

export async function getLegalPageOverride(
  page: LegalPageKey,
  options: GetLegalPageOptions = {},
): Promise<LegalPageOverride> {
  const response = await requestApi<LegalPageOverride>(
    `/legal-pages/${encodeURIComponent(page)}`,
    {
      query: {
        locale: options.locale,
      },
      baseUrl: options.baseUrl,
      cache: "no-store",
    },
  )

  return response.data ?? {}
}

export async function getLegalPageContent(
  page: LegalPageKey,
  fallback: SiteMessages["legal"]["privacy"],
  options: GetLegalPageOptions = {},
): Promise<LegalPageContent> {
  try {
    const override = await getLegalPageOverride(page, options)

    return {
      ...fallback,
      ...nonBlankLegalOverride(override),
    }
  } catch {
    return fallback
  }
}

function nonBlankLegalOverride(
  override: LegalPageOverride,
): LegalPageOverride {
  const result: LegalPageOverride = {}

  for (const [key, value] of Object.entries(override) as Array<
    [keyof LegalPageOverride, string | null | undefined]
  >) {
    if (typeof value === "string" && value.trim().length > 0) {
      result[key] = value
    }
  }

  return result
}
