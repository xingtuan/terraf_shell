import { getLocalizedHref, type Locale, type SiteMessages } from "@/lib/i18n"
import type { HomeSection, MaterialDetail, MaterialSpec } from "@/lib/types"

type HomeMessages = SiteMessages["home"]

function isLocalPreviewOrigin(href: string) {
  try {
    const url = new URL(href)

    return url.hostname === "127.0.0.1" || url.hostname === "localhost"
  } catch {
    return false
  }
}

function appendHrefSuffix(baseHref: string, search = "", hash = "") {
  const url = new URL(baseHref, "https://oxp.local")
  const searchParams = new URLSearchParams(search)

  searchParams.forEach((value, key) => {
    url.searchParams.set(key, value)
  })

  url.hash = hash || url.hash

  return `${url.pathname}${url.search}${url.hash}`
}

export function resolveCmsHref(
  locale: Locale,
  href?: string | null,
  fallback?: string,
) {
  if (!href) {
    return fallback ?? getLocalizedHref(locale)
  }

  if (/^https?:\/\//i.test(href) && !isLocalPreviewOrigin(href)) {
    return href
  }

  let pathname = href
  let search = ""
  let hash = ""

  try {
    const parsedUrl = /^https?:\/\//i.test(href)
      ? new URL(href)
      : new URL(href, "https://oxp.local")

    pathname = parsedUrl.pathname
    search = parsedUrl.search
    hash = parsedUrl.hash
  } catch {
    pathname = href
  }

  const normalized = pathname.replace(/^\/+|\/+$/g, "")

  if (!normalized) {
    return appendHrefSuffix(fallback ?? getLocalizedHref(locale), search, hash)
  }

  if (normalized === "materials" || normalized.startsWith("materials/")) {
    return appendHrefSuffix(getLocalizedHref(locale, "material"), search, hash)
  }

  if (normalized === "posts" || normalized.startsWith("posts/")) {
    const communityPath =
      normalized === "posts"
        ? "community"
        : `community/${normalized.slice("posts/".length)}`

    return appendHrefSuffix(getLocalizedHref(locale, communityPath), search, hash)
  }

  if (normalized === "users" || normalized.startsWith("users/")) {
    const userId = normalized.split("/")[1]

    if (userId) {
      return appendHrefSuffix(
        `${getLocalizedHref(locale, "community")}?user=${encodeURIComponent(userId)}`,
        search,
        hash,
      )
    }
  }

  if (normalized === "articles" || normalized.startsWith("articles/")) {
    return appendHrefSuffix(getLocalizedHref(locale, normalized), search, hash)
  }

  if (
    normalized === "b2b" ||
    normalized === "contact" ||
    normalized === "community" ||
    normalized === "store" ||
    normalized === "material" ||
    normalized.startsWith("community/")
  ) {
    return appendHrefSuffix(getLocalizedHref(locale, normalized), search, hash)
  }

  return appendHrefSuffix(
    fallback ?? getLocalizedHref(locale, normalized),
    search,
    hash,
  )
}

function buildSpecIndicator(spec: MaterialSpec) {
  if (spec.value && spec.label) {
    return `${spec.label}: ${spec.value}`
  }

  return spec.label || spec.value
}

export function buildHeroContent(
  fallback: HomeMessages["hero"],
  material?: MaterialDetail | null,
  heroSection?: HomeSection | null,
): HomeMessages["hero"] {
  const indicators = material?.specs
    .slice(0, 3)
    .map(buildSpecIndicator)
    .filter(Boolean)

  return {
    ...fallback,
    // Hero copy (eyebrow/title/description) is fully locale-driven.
    // Only indicators pull from live material data; CTA label can be CMS-controlled.
    primaryCta: heroSection?.cta_label || fallback.primaryCta,
    indicators: indicators?.length ? indicators : fallback.indicators,
  }
}

export function buildMaterialStoryContent(
  fallback: HomeMessages["materialStory"],
  material?: MaterialDetail | null,
): HomeMessages["materialStory"] {
  if (!material?.story_sections.length) {
    return fallback
  }

  return {
    ...fallback,
    title: material.story_overview || fallback.title,
    steps: material.story_sections.slice(0, 4).map((section, index) => ({
      number: String(index + 1).padStart(2, "0"),
      title:
        section.title ||
        section.subtitle ||
        fallback.steps[index]?.title ||
        fallback.title,
      description:
        section.content ||
        section.highlight ||
        fallback.steps[index]?.description ||
        "",
    })),
  }
}

export function buildApplicationsContent(
  fallback: HomeMessages["applications"],
  material?: MaterialDetail | null,
): HomeMessages["applications"] {
  if (!material?.applications.length) {
    return fallback
  }

  return {
    ...fallback,
    title: material.summary || fallback.title,
    items: material.applications.slice(0, 4).map((application) => ({
      title: application.title,
      description:
        application.description || application.audience || fallback.items[0]?.description || "",
    })),
  }
}

export function buildMaterialFactsContent(
  fallback: HomeMessages["materialFacts"],
  material?: MaterialDetail | null,
  scienceSection?: HomeSection | null,
): HomeMessages["materialFacts"] {
  const infoCards =
    material !== null && material !== undefined
      ? [
          {
            label: fallback.infoCards[0]?.label ?? "Material",
            value:
              material.applications.length > 0 || (material.applications_count ?? 0) > 0
                ? `${material.applications_count ?? material.applications.length} application areas`
                : material.title,
          },
          {
            label: fallback.infoCards[1]?.label ?? "Applications",
            value: material.status === "published" ? "Live backend content" : material.title,
          },
        ]
      : fallback.infoCards

  return {
    ...fallback,
    eyebrow: scienceSection?.subtitle || fallback.eyebrow,
    title: scienceSection?.title || material?.headline || fallback.title,
    sheetTitle: scienceSection?.title || fallback.sheetTitle,
    sheetDescription:
      scienceSection?.content || material?.science_overview || fallback.sheetDescription,
    sheetCta: scienceSection?.cta_label || fallback.sheetCta,
    infoCards,
    note: material?.science_overview || fallback.note,
  }
}

export function buildCredibilityContent(
  fallback: HomeMessages["credibility"],
  material?: MaterialDetail | null,
): HomeMessages["credibility"] {
  if (!material) {
    return fallback
  }

  const benefits = [
    material.summary,
    material.story_overview,
    material.science_overview,
    ...material.specs.slice(0, 2).map((spec) => spec.detail || buildSpecIndicator(spec)),
  ].filter((value): value is string => Boolean(value && value.trim()))

  const features = material.specs.slice(0, 4).map((spec) => ({
    title: spec.label,
    description: [spec.value, spec.detail].filter(Boolean).join(". "),
  }))

  return {
    ...fallback,
    title: material.headline || fallback.title,
    benefits: benefits.length ? benefits.slice(0, 4) : fallback.benefits,
    features: features.length ? features : fallback.features,
  }
}
