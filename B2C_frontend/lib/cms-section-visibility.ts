import type { HomeSection } from "@/lib/types"

export function hasPublishedCmsSection(
  section: HomeSection | null | undefined,
): section is HomeSection {
  return Boolean(section)
}
