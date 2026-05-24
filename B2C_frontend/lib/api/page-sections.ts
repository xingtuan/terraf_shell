import { requestApi } from "@/lib/api/client"
import { normalizeHomeSection } from "@/lib/api/adapters"
import { ensureArray } from "@/lib/api/normalizers"
import type { HomeSection, PageKey } from "@/lib/types"

export type { PageKey } from "@/lib/types"

type PageSectionsRequest = {
  baseUrl?: string
  locale?: string
  page: PageKey
}

export async function getPageSections({
  baseUrl,
  locale,
  page,
}: PageSectionsRequest) {
  const response = await requestApi<HomeSection[]>("/page-sections", {
    query: {
      locale,
      page,
    },
    baseUrl,
  })

  return ensureArray(response.data).map(normalizeHomeSection)
}

export function findPageSection(sections: HomeSection[], key: string) {
  return sections.find((section) => section.key === key) ?? null
}

export function getSectionPayload(section: HomeSection | null | undefined) {
  return section?.payload && typeof section.payload === "object"
    ? section.payload
    : null
}
