import { requestApi } from "@/lib/api/client"
import enMessages from "@/messages/en.json"
import koMessages from "@/messages/ko.json"
import zhMessages from "@/messages/zh.json"

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

type LegalPageLocale = "en" | "zh" | "ko"
type LegalPageSection = {
  title?: string
  paragraphs?: string[]
}
type LegalPageFallbackSource = Omit<LegalPageContent, "bodyHtml"> & {
  sections?: LegalPageSection[]
}

const fallbackMessages = {
  en: enMessages,
  zh: zhMessages,
  ko: koMessages,
} satisfies Record<LegalPageLocale, typeof enMessages>

export async function getLegalPageContent(
  page: LegalPageKey,
  options: GetLegalPageOptions = {},
): Promise<LegalPageContent> {
  try {
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
  } catch (error) {
    return fallbackLegalContent(page, options.locale)
  }
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

function fallbackLegalContent(
  page: LegalPageKey,
  locale: string | undefined,
): LegalPageContent {
  const fallbackLocale = normalizeLegalPageLocale(locale)
  const source = fallbackMessages[fallbackLocale].legal[
    page
  ] as LegalPageFallbackSource

  return nonBlankLegalContent({
    metaTitle: source.metaTitle,
    metaDescription: source.metaDescription,
    eyebrow: source.eyebrow,
    title: source.title,
    description: source.description,
    lastUpdatedLabel: source.lastUpdatedLabel,
    lastUpdated: source.lastUpdated,
    bodyHtml: sectionsToHtml(source.sections),
  })
}

function normalizeLegalPageLocale(
  locale: string | undefined,
): LegalPageLocale {
  const normalizedLocale = locale?.toLowerCase().split("-")[0]

  return normalizedLocale === "zh" || normalizedLocale === "ko"
    ? normalizedLocale
    : "en"
}

function sectionsToHtml(sections: LegalPageSection[] | undefined) {
  if (!Array.isArray(sections) || sections.length === 0) {
    return null
  }

  return sections
    .map((section) => {
      const title = section.title?.trim()
      const paragraphs = Array.isArray(section.paragraphs)
        ? section.paragraphs
            .map((paragraph) => paragraph.trim())
            .filter(Boolean)
        : []

      return [
        title ? `<h2>${escapeHtml(title)}</h2>` : "",
        ...paragraphs.map(
          (paragraph) => `<p>${escapeHtml(paragraph)}</p>`,
        ),
      ].join("")
    })
    .join("")
}

function escapeHtml(value: string) {
  return value
    .replace(/&/g, "&amp;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
    .replace(/"/g, "&quot;")
    .replace(/'/g, "&#39;")
}
