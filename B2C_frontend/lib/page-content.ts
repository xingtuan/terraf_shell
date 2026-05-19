import { getLocalizedHref, type Locale, type SiteMessages } from "@/lib/i18n"
import type { HomeSection, MaterialDetail, MaterialSpec } from "@/lib/types"

type HomeMessages = SiteMessages["home"]
type LocalizedRecord = object

export type HeroContent = HomeMessages["hero"] & {
  mediaUrl?: string | null
}

export type MaterialStoryContent = HomeMessages["materialStory"] & {
  steps: Array<HomeMessages["materialStory"]["steps"][number] & { mediaUrl?: string | null }>
}

export type ApplicationsContent = HomeMessages["applications"] & {
  items: Array<HomeMessages["applications"]["items"][number] & { mediaUrl?: string | null }>
}

export type CredibilityContent = HomeMessages["credibility"] & {
  mediaUrl?: string | null
}

export type MaterialFamilyContent = HomeMessages["materialFamily"] & {
  mediaUrl?: string | null
}

export type FinalCtaContent = HomeMessages["finalCta"] & {
  primaryHref?: string
  secondaryHref?: string
}

function nonEmptyString(value: unknown): string | null {
  return typeof value === "string" && value.trim() ? value : null
}

function isRecord(value: unknown): value is Record<string, unknown> {
  return value !== null && typeof value === "object" && !Array.isArray(value)
}

export function isLikelyEnglishOnly(value: unknown): boolean {
  const text = nonEmptyString(value)

  if (!text) {
    return false
  }

  const containsLocalizedScript = /[\u3400-\u9fff\uac00-\ud7af]/u.test(text)
  const containsEnglishWord = /[A-Za-z]{3,}/.test(text)

  return containsEnglishWord && !containsLocalizedScript
}

export function resolveLocalizedApiValue(
  value: unknown,
  fallback: string | null | undefined,
  locale: Locale,
): string {
  const cleanedValue = nonEmptyString(value)
  const cleanedFallback = nonEmptyString(fallback)

  if (cleanedValue && (locale === "en" || !isLikelyEnglishOnly(cleanedValue))) {
    return cleanedValue
  }

  return cleanedFallback ?? ""
}

function recordValue(
  source: LocalizedRecord | null | undefined,
  key: string,
): unknown {
  return source ? (source as Record<string, unknown>)[key] : undefined
}

function getTranslationSet(
  source: LocalizedRecord | null | undefined,
  field: string,
) {
  const translations = recordValue(source, `${field}_translations`)

  if (
    translations === null ||
    translations === undefined ||
    typeof translations !== "object" ||
    Array.isArray(translations)
  ) {
    return null
  }

  return translations as Record<string, unknown>
}

function getRequestedApiString(
  source: LocalizedRecord | null | undefined,
  field: string,
  locale: Locale,
) {
  const translations = getTranslationSet(source, field)
  const translatedValue = nonEmptyString(translations?.[locale])

  if (translatedValue) {
    return translatedValue
  }

  if (locale === "en") {
    return nonEmptyString(recordValue(source, field)) ?? nonEmptyString(translations?.en)
  }

  return null
}

function getLastResortApiString(
  source: LocalizedRecord | null | undefined,
  field: string,
) {
  const translations = getTranslationSet(source, field)
  const firstTranslatedValue = Object.values(translations ?? {}).find(
    (value): value is string => Boolean(nonEmptyString(value)),
  )

  return (
    nonEmptyString(recordValue(source, field)) ??
    nonEmptyString(translations?.en) ??
    firstTranslatedValue ??
    null
  )
}

export function resolveLocalizedApiString(
  source: LocalizedRecord | null | undefined,
  field: string,
  locale: Locale,
  fallback?: string | null,
): string {
  return (
    getRequestedApiString(source, field, locale) ??
    nonEmptyString(fallback) ??
    getLastResortApiString(source, field) ??
    ""
  )
}

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

export type FooterContent = SiteMessages["footer"] & {
  homeLabel?: string
  materialLabel?: string
  storeLabel?: string
  b2bLabel?: string
  communityLinkLabel?: string
  contactLabel?: string
  emailValue?: string
  emailHref?: string
  phoneValue?: string
  phoneHref?: string
  locationHref?: string
  privacyHref?: string
  termsHref?: string
}

function payloadString(
  payload: Record<string, unknown> | null,
  field: string,
): string | null {
  return payload ? nonEmptyString(payload[field]) : null
}

function sectionPayload(section: HomeSection | null | undefined) {
  return section?.payload && isRecord(section.payload) ? section.payload : null
}

function payloadArray(
  section: HomeSection | null | undefined,
  field: string,
): unknown[] {
  const payload = sectionPayload(section)
  const value = payload?.[field]

  return Array.isArray(value) ? value : []
}

function payloadItemString(
  item: Record<string, unknown>,
  field: string,
  locale: Locale,
  fallback?: string | null,
) {
  return resolvePayloadItemString(item, field, locale, fallback)
}

function payloadMediaUrl(item: Record<string, unknown>) {
  return (
    nonEmptyString(item.media_url) ??
    nonEmptyString(item.mediaUrl) ??
    nonEmptyString(item.media_path) ??
    null
  )
}

function resolvedPayloadHref(
  locale: Locale,
  item: Record<string, unknown>,
  fallback: string,
) {
  return resolveCmsHref(locale, nonEmptyString(item.cta_url), fallback)
}

function localizedPayloadString(
  payload: Record<string, unknown> | null,
  field: string,
  locale: Locale,
  fallback?: string | null,
) {
  return resolveLocalizedApiString(payload, field, locale, fallback)
}

function resolveFooterHref(
  locale: Locale,
  href: string | null,
  fallback: string,
) {
  if (!href) {
    return fallback
  }

  if (/^(mailto|tel):/i.test(href)) {
    return href
  }

  return resolveCmsHref(locale, href, fallback)
}

export function buildFooterContent(
  fallback: SiteMessages["footer"],
  section: HomeSection | null | undefined,
  locale: Locale,
  headerFallback?: SiteMessages["header"],
): FooterContent {
  const payload =
    section?.key === "footer" && isRecord(section.payload) ? section.payload : null

  return {
    ...fallback,
    homeLabel: headerFallback
      ? resolveLocalizedApiString(payload, "home", locale, headerFallback.home)
      : undefined,
    materialLabel: headerFallback
      ? resolveLocalizedApiString(
          payload,
          "material",
          locale,
          headerFallback.material,
        )
      : undefined,
    storeLabel: headerFallback
      ? resolveLocalizedApiString(payload, "store", locale, headerFallback.store)
      : undefined,
    b2bLabel: headerFallback
      ? resolveLocalizedApiString(payload, "b2b", locale, headerFallback.b2b)
      : undefined,
    communityLinkLabel: headerFallback
      ? resolveLocalizedApiString(
          payload,
          "community",
          locale,
          headerFallback.community,
        )
      : undefined,
    contactLabel: headerFallback
      ? resolveLocalizedApiString(
          payload,
          "contact",
          locale,
          headerFallback.contact,
        )
      : undefined,
    description: resolveLocalizedApiString(
      section,
      "content",
      locale,
      fallback.description,
    ),
    explore: resolveLocalizedApiString(payload, "explore", locale, fallback.explore),
    business: resolveLocalizedApiString(
      payload,
      "business",
      locale,
      fallback.business,
    ),
    communityLabel: resolveLocalizedApiString(
      payload,
      "community_label",
      locale,
      fallback.communityLabel,
    ),
    materialSheet: resolveLocalizedApiString(
      payload,
      "material_sheet",
      locale,
      fallback.materialSheet,
    ),
    sampleRequest: resolveLocalizedApiString(
      payload,
      "sample_request",
      locale,
      fallback.sampleRequest,
    ),
    productDevelopment: resolveLocalizedApiString(
      payload,
      "product_development",
      locale,
      fallback.productDevelopment,
    ),
    ideaSupport: resolveLocalizedApiString(
      payload,
      "idea_support",
      locale,
      fallback.ideaSupport,
    ),
    conceptFund: resolveLocalizedApiString(
      payload,
      "concept_fund",
      locale,
      fallback.conceptFund,
    ),
    emailLabel: resolveLocalizedApiString(
      payload,
      "email_label",
      locale,
      fallback.emailLabel,
    ),
    phoneLabel: resolveLocalizedApiString(
      payload,
      "phone_label",
      locale,
      fallback.phoneLabel,
    ),
    locationLabel: resolveLocalizedApiString(
      payload,
      "location_label",
      locale,
      fallback.locationLabel,
    ),
    locationValue: resolveLocalizedApiString(
      payload,
      "location_value",
      locale,
      fallback.locationValue,
    ),
    copyright: resolveLocalizedApiString(
      payload,
      "copyright",
      locale,
      fallback.copyright,
    ),
    privacy: resolveLocalizedApiString(
      payload,
      "privacy",
      locale,
      fallback.privacy,
    ),
    terms: resolveLocalizedApiString(payload, "terms", locale, fallback.terms),
    emailValue: payloadString(payload, "email_value") ?? undefined,
    emailHref: payloadString(payload, "email_href") ?? undefined,
    phoneValue: payloadString(payload, "phone_value") ?? undefined,
    phoneHref: payloadString(payload, "phone_href") ?? undefined,
    locationHref: resolveFooterHref(
      locale,
      payloadString(payload, "location_href"),
      getLocalizedHref(locale, "contact"),
    ),
    privacyHref: resolveFooterHref(
      locale,
      payloadString(payload, "privacy_href"),
      getLocalizedHref(locale, "privacy"),
    ),
    termsHref: resolveFooterHref(
      locale,
      payloadString(payload, "terms_href"),
      getLocalizedHref(locale, "terms"),
    ),
  }
}

function buildSpecIndicator(spec: MaterialSpec, locale?: Locale) {
  const label = locale
    ? resolveLocalizedApiValue(spec.label, null, locale)
    : spec.label
  const value = locale
    ? resolveLocalizedApiValue(spec.value, null, locale)
    : spec.value

  if (value && label) {
    return `${label}: ${value}`
  }

  return label || value
}

export function buildHeroContent(
  fallback: HomeMessages["hero"],
  material?: MaterialDetail | null,
  heroSection?: HomeSection | null,
  locale?: Locale,
): HeroContent {
  const resolvedLocale = locale ?? "en"
  const payload = sectionPayload(heroSection)
  const cmsIndicators = payloadArray(heroSection, "metrics")
    .flatMap((rawItem, index) => {
      if (!isRecord(rawItem)) {
        return []
      }

      const label = payloadItemString(
        rawItem,
        "label",
        resolvedLocale,
        fallback.indicators[index],
      )

      return label ? [label] : []
    })
  const materialIndicators = material?.specs
    .slice(0, 3)
    .map((spec) => buildSpecIndicator(spec, locale))
    .filter(Boolean)

  return {
    ...fallback,
    eyebrow: heroSection
      ? resolveLocalizedApiString(heroSection, "subtitle", resolvedLocale, fallback.eyebrow)
      : fallback.eyebrow,
    title: heroSection
      ? resolveLocalizedApiString(heroSection, "title", resolvedLocale, fallback.title)
      : fallback.title,
    description: heroSection
      ? resolveLocalizedApiString(heroSection, "content", resolvedLocale, fallback.description)
      : fallback.description,
    primaryCta: heroSection
      ? resolveLocalizedApiString(heroSection, "cta_label", resolvedLocale, fallback.primaryCta)
      : fallback.primaryCta,
    secondaryCta: localizedPayloadString(
      payload,
      "secondary_cta_label",
      resolvedLocale,
      fallback.secondaryCta,
    ),
    indicators: cmsIndicators.length
      ? cmsIndicators
      : materialIndicators?.length
        ? materialIndicators
        : fallback.indicators,
    mediaUrl: heroSection?.media_url ?? payloadString(payload, "media_url"),
  }
}

export function buildMaterialStoryContent(
  fallback: HomeMessages["materialStory"],
  material?: MaterialDetail | null,
  locale?: Locale,
  section?: HomeSection | null,
): MaterialStoryContent {
  const resolvedLocale = locale ?? "en"
  const cmsItems = payloadArray(section, "items")
    .flatMap((rawItem, index) => {
      if (!isRecord(rawItem)) {
        return []
      }

      const fallbackStep = fallback.steps[index]
      const step = {
        number:
          payloadItemString(rawItem, "label", resolvedLocale, fallbackStep?.number) ||
          String(index + 1).padStart(2, "0"),
        title: payloadItemString(rawItem, "title", resolvedLocale, fallbackStep?.title),
        description: payloadItemString(
          rawItem,
          "description",
          resolvedLocale,
          fallbackStep?.description,
        ),
        mediaUrl: payloadMediaUrl(rawItem),
      }

      return step.title || step.description ? [step] : []
    })

  if (cmsItems.length || section) {
    return {
      ...fallback,
      eyebrow: resolveLocalizedApiString(section, "subtitle", resolvedLocale, fallback.eyebrow),
      title: resolveLocalizedApiString(section, "title", resolvedLocale, fallback.title),
      steps: cmsItems.length ? cmsItems : fallback.steps,
    }
  }

  if (!material?.story_sections.length) {
    return fallback
  }

  return {
    ...fallback,
    title: locale
      ? resolveLocalizedApiValue(material.story_overview, fallback.title, locale)
      : material.story_overview || fallback.title,
    steps: material.story_sections.slice(0, 4).map((section, index) => ({
      number: String(index + 1).padStart(2, "0"),
      title:
        (locale
          ? resolveLocalizedApiValue(section.title, section.subtitle, locale)
          : section.title || section.subtitle) ||
        fallback.steps[index]?.title ||
        fallback.title,
      description:
        (locale
          ? resolveLocalizedApiValue(section.content, section.highlight, locale)
          : section.content || section.highlight) ||
        fallback.steps[index]?.description ||
        "",
    })),
  }
}

export function buildApplicationsContent(
  fallback: HomeMessages["applications"],
  material?: MaterialDetail | null,
  locale?: Locale,
  section?: HomeSection | null,
): ApplicationsContent {
  const resolvedLocale = locale ?? "en"
  const cmsItems = payloadArray(section, "items")
    .flatMap((rawItem, index) => {
      if (!isRecord(rawItem)) {
        return []
      }

      const fallbackItem = fallback.items[index]
      const item = {
        title: payloadItemString(rawItem, "title", resolvedLocale, fallbackItem?.title),
        description: payloadItemString(
          rawItem,
          "description",
          resolvedLocale,
          fallbackItem?.description,
        ),
        mediaUrl: payloadMediaUrl(rawItem),
      }

      return item.title || item.description ? [item] : []
    })

  if (cmsItems.length || section) {
    return {
      ...fallback,
      eyebrow: resolveLocalizedApiString(section, "subtitle", resolvedLocale, fallback.eyebrow),
      title: resolveLocalizedApiString(section, "title", resolvedLocale, fallback.title),
      items: cmsItems.length ? cmsItems : fallback.items,
    }
  }

  if (!material?.applications.length) {
    return fallback
  }

  return {
    ...fallback,
    title: locale
      ? resolveLocalizedApiValue(material.summary, fallback.title, locale)
      : material.summary || fallback.title,
    items: material.applications.slice(0, 4).map((application, index) => ({
      title: locale
        ? resolveLocalizedApiValue(
            application.title,
            fallback.items[index]?.title,
            locale,
          )
        : application.title,
      description:
        (locale
          ? resolveLocalizedApiValue(
              application.description,
              application.audience || fallback.items[index]?.description,
              locale,
            )
          : application.description || application.audience) ||
        fallback.items[index]?.description ||
        "",
    })),
  }
}

export function buildMaterialFactsContent(
  fallback: HomeMessages["materialFacts"],
  material?: MaterialDetail | null,
  scienceSection?: HomeSection | null,
  locale?: Locale,
): HomeMessages["materialFacts"] {
  const resolvedLocale = locale ?? "en"
  const payload = sectionPayload(scienceSection)
  const cmsInfoCards = payloadArray(scienceSection, "metrics")
    .flatMap((rawItem, index) => {
      if (!isRecord(rawItem)) {
        return []
      }

      const fallbackCard = fallback.infoCards[index]
      const card = {
        label: payloadItemString(rawItem, "label", resolvedLocale, fallbackCard?.label),
        value: payloadItemString(rawItem, "value", resolvedLocale, fallbackCard?.value),
      }

      return card.label || card.value ? [card] : []
    })
  const infoCards =
    cmsInfoCards.length
      ? cmsInfoCards
      : material !== null && material !== undefined
      ? [
          {
            label: fallback.infoCards[0]?.label ?? "Material",
            value:
              material.applications.length > 0 || (material.applications_count ?? 0) > 0
                ? fallback.infoCards[0]?.value || material.title
                : material.title,
          },
          {
            label: fallback.infoCards[1]?.label ?? "Applications",
            value:
              material.status === "published"
                ? fallback.infoCards[1]?.value || material.title
                : material.title,
          },
        ]
      : fallback.infoCards

  const sectionEyebrow =
    locale && scienceSection
      ? resolveLocalizedApiString(scienceSection, "subtitle", locale, fallback.eyebrow)
      : scienceSection?.subtitle || fallback.eyebrow
  const sectionTitle =
    locale && scienceSection
      ? resolveLocalizedApiString(scienceSection, "title", locale, fallback.title)
      : scienceSection?.title || fallback.title
  const sectionContent =
    locale && scienceSection
      ? resolveLocalizedApiString(
          scienceSection,
          "content",
          locale,
          fallback.sheetDescription,
        )
      : scienceSection?.content || fallback.sheetDescription
  const sectionCta =
    locale && scienceSection
      ? resolveLocalizedApiString(scienceSection, "cta_label", locale, fallback.sheetCta)
      : scienceSection?.cta_label || fallback.sheetCta

  return {
    ...fallback,
    eyebrow: sectionEyebrow,
    title: sectionTitle || material?.headline || fallback.title,
    sheetTitle:
      localizedPayloadString(payload, "sheet_title", resolvedLocale, null) ||
      sectionTitle ||
      fallback.sheetTitle,
    sheetDescription:
      sectionContent || material?.science_overview || fallback.sheetDescription,
    sheetCta: sectionCta,
    infoCards,
    note:
      localizedPayloadString(payload, "note", resolvedLocale, null) ||
      material?.science_overview ||
      fallback.note,
  }
}

export function buildCredibilityContent(
  fallback: HomeMessages["credibility"],
  material?: MaterialDetail | null,
  locale?: Locale,
  section?: HomeSection | null,
): CredibilityContent {
  const resolvedLocale = locale ?? "en"
  const cmsFeatures = payloadArray(section, "items")
    .flatMap((rawItem, index) => {
      if (!isRecord(rawItem)) {
        return []
      }

      const fallbackItem = fallback.features[index]
      const item = {
        title: payloadItemString(rawItem, "title", resolvedLocale, fallbackItem?.title),
        description: payloadItemString(
          rawItem,
          "description",
          resolvedLocale,
          fallbackItem?.description,
        ),
      }

      return item.title || item.description ? [item] : []
    })
  const cmsBenefits = payloadArray(section, "metrics")
    .flatMap((rawItem, index) => {
      if (!isRecord(rawItem)) {
        return []
      }

      const benefit = payloadItemString(
        rawItem,
        "description",
        resolvedLocale,
        fallback.benefits[index],
      )

      return benefit ? [benefit] : []
    })

  if (cmsFeatures.length || cmsBenefits.length || section) {
    return {
      ...fallback,
      eyebrow: resolveLocalizedApiString(section, "subtitle", resolvedLocale, fallback.eyebrow),
      title: resolveLocalizedApiString(section, "title", resolvedLocale, fallback.title),
      benefits: cmsBenefits.length ? cmsBenefits : fallback.benefits,
      features: cmsFeatures.length ? cmsFeatures : fallback.features,
      mediaUrl: section?.media_url ?? payloadString(sectionPayload(section), "media_url"),
    }
  }

  if (!material) {
    return fallback
  }

  const benefits = [
    locale
      ? resolveLocalizedApiValue(material.summary, null, locale)
      : material.summary,
    locale
      ? resolveLocalizedApiValue(material.story_overview, null, locale)
      : material.story_overview,
    locale
      ? resolveLocalizedApiValue(material.science_overview, null, locale)
      : material.science_overview,
    ...material.specs
      .slice(0, 2)
      .map((spec) =>
        locale
          ? resolveLocalizedApiValue(
              spec.detail,
              buildSpecIndicator(spec, locale),
              locale,
            )
          : spec.detail || buildSpecIndicator(spec),
      ),
  ].filter((value): value is string => Boolean(value && value.trim()))

  const features = material.specs.slice(0, 4).map((spec) => ({
    title: locale
      ? resolveLocalizedApiValue(spec.label, fallback.features[0]?.title, locale)
      : spec.label,
    description: [
      locale ? resolveLocalizedApiValue(spec.value, null, locale) : spec.value,
      locale ? resolveLocalizedApiValue(spec.detail, null, locale) : spec.detail,
    ]
      .filter(Boolean)
      .join(". "),
  }))

  return {
    ...fallback,
    title: locale
      ? resolveLocalizedApiValue(material.headline, fallback.title, locale)
      : material.headline || fallback.title,
    benefits: benefits.length ? benefits.slice(0, 4) : fallback.benefits,
    features: features.length ? features : fallback.features,
  }
}

export function buildAudiencePathsContent(
  fallback: HomeMessages["audiencePaths"],
  section: HomeSection | null | undefined,
  locale: Locale,
): HomeMessages["audiencePaths"] {
  const cards = payloadArray(section, "items")
    .flatMap((rawItem, index) => {
      if (!isRecord(rawItem)) {
        return []
      }

      const fallbackCard = fallback.cards[index]
      const card = {
        label: payloadItemString(rawItem, "label", locale, fallbackCard?.label),
        title: payloadItemString(rawItem, "title", locale, fallbackCard?.title),
        description: payloadItemString(
          rawItem,
          "description",
          locale,
          fallbackCard?.description,
        ),
        cta: payloadItemString(rawItem, "cta_label", locale, fallbackCard?.cta),
        href:
          nonEmptyString(rawItem.cta_url) ??
          nonEmptyString(rawItem.href) ??
          fallbackCard?.href ??
          "",
      }

      return card.title || card.description ? [card] : []
    })

  return {
    ...fallback,
    eyebrow: resolveLocalizedApiString(section, "subtitle", locale, fallback.eyebrow),
    title: resolveLocalizedApiString(section, "title", locale, fallback.title),
    cards: cards.length ? cards : fallback.cards,
  }
}

export function buildBusinessPillarsContent(
  fallback: HomeMessages["businessPillars"],
  section: HomeSection | null | undefined,
  locale: Locale,
): HomeMessages["businessPillars"] {
  const pillars = payloadArray(section, "items")
    .flatMap((rawItem, index) => {
      if (!isRecord(rawItem)) {
        return []
      }

      const fallbackItem = fallback.pillars[index]
      const item = {
        name: payloadItemString(rawItem, "title", locale, fallbackItem?.name),
        formula: payloadItemString(rawItem, "subtitle", locale, fallbackItem?.formula),
        description: payloadItemString(
          rawItem,
          "description",
          locale,
          fallbackItem?.description,
        ),
      }

      return item.name || item.description ? [item] : []
    })

  return {
    ...fallback,
    eyebrow: resolveLocalizedApiString(section, "subtitle", locale, fallback.eyebrow),
    title: resolveLocalizedApiString(section, "title", locale, fallback.title),
    pillars: pillars.length ? pillars : fallback.pillars,
  }
}

export function buildWhyItMattersContent(
  fallback: HomeMessages["whyItMatters"],
  section: HomeSection | null | undefined,
  locale: Locale,
  material?: MaterialDetail | null,
): HomeMessages["whyItMatters"] {
  const cards = payloadArray(section, "items")
    .flatMap((rawItem, index) => {
      if (!isRecord(rawItem)) {
        return []
      }

      const fallbackCard = fallback.cards[index]
      const card = {
        title: payloadItemString(rawItem, "title", locale, fallbackCard?.title),
        description: payloadItemString(
          rawItem,
          "description",
          locale,
          fallbackCard?.description,
        ),
      }

      return card.title || card.description ? [card] : []
    })
  const stats = payloadArray(section, "metrics")
    .flatMap((rawItem, index) => {
      if (!isRecord(rawItem)) {
        return []
      }

      const stat = payloadItemString(
        rawItem,
        "description",
        locale,
        fallback.stats[index],
      )

      return stat ? [stat] : []
    })

  if (cards.length || stats.length || section) {
    return {
      ...fallback,
      eyebrow: resolveLocalizedApiString(section, "subtitle", locale, fallback.eyebrow),
      title: resolveLocalizedApiString(section, "title", locale, fallback.title),
      cards: cards.length ? cards : fallback.cards,
      stats: stats.length ? stats : fallback.stats,
    }
  }

  if (!material) {
    return fallback
  }

  return {
    ...fallback,
    title: resolveLocalizedApiValue(material.headline, fallback.title, locale),
    cards: material.specs.slice(0, 3).map((spec, index) => ({
      title: resolveLocalizedApiValue(spec.label, fallback.cards[index]?.title, locale),
      description: [
        resolveLocalizedApiValue(spec.value, null, locale),
        resolveLocalizedApiValue(spec.detail, null, locale),
      ]
        .filter(Boolean)
        .join(". "),
    })),
    stats: [
      resolveLocalizedApiValue(material.summary, fallback.stats[0], locale),
      resolveLocalizedApiValue(material.story_overview, fallback.stats[1], locale),
      resolveLocalizedApiValue(material.science_overview, fallback.stats[2], locale),
    ].filter(Boolean),
  }
}

export function buildOpenSourceLegacyContent(
  fallback: HomeMessages["openSourceLegacy"],
  section: HomeSection | null | undefined,
  locale: Locale,
): HomeMessages["openSourceLegacy"] {
  const authors = payloadArray(section, "items")
    .flatMap((rawItem, index) => {
      if (!isRecord(rawItem)) {
        return []
      }

      const fallbackItem = fallback.authors[index]
      const item = {
        author: payloadItemString(rawItem, "title", locale, fallbackItem?.author),
        timeframe: payloadItemString(
          rawItem,
          "subtitle",
          locale,
          fallbackItem?.timeframe,
        ),
        sourceCode: payloadItemString(
          rawItem,
          "label",
          locale,
          fallbackItem?.sourceCode,
        ),
        legacy: payloadItemString(
          rawItem,
          "description",
          locale,
          fallbackItem?.legacy,
        ),
      }

      return item.author || item.legacy ? [item] : []
    })

  return {
    ...fallback,
    eyebrow: resolveLocalizedApiString(section, "subtitle", locale, fallback.eyebrow),
    title: resolveLocalizedApiString(section, "title", locale, fallback.title),
    intro: resolveLocalizedApiString(section, "content", locale, fallback.intro),
    authors: authors.length ? authors : fallback.authors,
  }
}

export function buildCollaborationContent(
  fallback: HomeMessages["collaboration"],
  section: HomeSection | null | undefined,
  locale: Locale,
): HomeMessages["collaboration"] & { cardHrefs: string[] } {
  const fallbackHrefs = [
    `${getLocalizedHref(locale, "b2b")}#inquiry`,
    `${getLocalizedHref(locale, "b2b")}?leadType=sample_request#inquiry`,
    `${getLocalizedHref(locale, "b2b")}?leadType=product_development_collaboration#inquiry`,
  ]
  const cards = payloadArray(section, "items")
    .flatMap((rawItem, index) => {
      if (!isRecord(rawItem)) {
        return []
      }

      const fallbackCard = fallback.cards[index]
      const card = {
        title: payloadItemString(rawItem, "title", locale, fallbackCard?.title),
        forWhom: payloadItemString(rawItem, "subtitle", locale, fallbackCard?.forWhom),
        description: payloadItemString(
          rawItem,
          "description",
          locale,
          fallbackCard?.description,
        ),
        cta: payloadItemString(rawItem, "cta_label", locale, fallbackCard?.cta),
      }

      return card.title || card.description ? [card] : []
    })
  const cardHrefs = payloadArray(section, "items").map((rawItem, index) =>
    isRecord(rawItem)
      ? resolvedPayloadHref(locale, rawItem, fallbackHrefs[index] ?? fallbackHrefs[0])
      : fallbackHrefs[index] ?? fallbackHrefs[0],
  )
  const steps = payloadArray(section, "steps")
    .flatMap((rawItem, index) => {
      if (!isRecord(rawItem)) {
        return []
      }

      const step = payloadItemString(rawItem, "label", locale, fallback.steps[index])

      return step ? [step] : []
    })

  return {
    ...fallback,
    eyebrow: resolveLocalizedApiString(section, "subtitle", locale, fallback.eyebrow),
    title: resolveLocalizedApiString(section, "title", locale, fallback.title),
    cards: cards.length ? cards : fallback.cards,
    cardHrefs: cardHrefs.length ? cardHrefs : fallbackHrefs,
    processTitle: localizedPayloadString(
      sectionPayload(section),
      "process_title",
      locale,
      fallback.processTitle,
    ),
    steps: steps.length ? steps : fallback.steps,
  }
}

export function buildTrustAndCredibilityContent(
  fallback: SiteMessages["trustAndCredibility"],
  section: HomeSection | null | undefined,
  locale: Locale,
): SiteMessages["trustAndCredibility"] {
  const cards = payloadArray(section, "items")
    .flatMap((rawItem, index) => {
      if (!isRecord(rawItem)) {
        return []
      }

      const fallbackCard = fallback.cards[index]
      const card = {
        title: payloadItemString(rawItem, "title", locale, fallbackCard?.title),
        description: payloadItemString(
          rawItem,
          "description",
          locale,
          fallbackCard?.description,
        ),
      }

      return card.title || card.description ? [card] : []
    })

  return {
    ...fallback,
    eyebrow: resolveLocalizedApiString(section, "subtitle", locale, fallback.eyebrow),
    title: resolveLocalizedApiString(section, "title", locale, fallback.title),
    description: resolveLocalizedApiString(
      section,
      "content",
      locale,
      fallback.description,
    ),
    cards: cards.length ? cards : fallback.cards,
    disclaimer: localizedPayloadString(
      sectionPayload(section),
      "disclaimer",
      locale,
      fallback.disclaimer,
    ),
  }
}

export function buildMaterialFamilyContent(
  fallback: HomeMessages["materialFamily"],
  section: HomeSection | null | undefined,
  locale: Locale,
): MaterialFamilyContent {
  const payload = sectionPayload(section)
  const diagram = isRecord(payload?.diagram) ? payload.diagram : null
  const badges = isRecord(payload?.badges) ? payload.badges : null
  const legend = Array.isArray(payload?.legend)
    ? payload.legend.flatMap((rawItem, index) => {
        if (!isRecord(rawItem)) {
          return []
        }

        const fallbackItem = fallback.legend[index]
        const item = {
          label: payloadItemString(rawItem, "label", locale, fallbackItem?.label),
          description: payloadItemString(
            rawItem,
            "description",
            locale,
            fallbackItem?.description,
          ),
        }

        return item.label || item.description ? [item] : []
      })
    : []
  const lines = payloadArray(section, "items")
    .flatMap((rawItem, index) => {
      if (!isRecord(rawItem)) {
        return []
      }

      const fallbackLine = fallback.lines[index]
      const line = {
        code: nonEmptyString(rawItem.key) ?? fallbackLine?.code ?? "",
        name: payloadItemString(rawItem, "title", locale, fallbackLine?.name),
        source: payloadItemString(rawItem, "subtitle", locale, fallbackLine?.source),
        description: payloadItemString(
          rawItem,
          "description",
          locale,
          fallbackLine?.description,
        ),
        status:
          (nonEmptyString(rawItem.status) as "available" | "sibling" | "inactive" | null) ??
          fallbackLine?.status ??
          "inactive",
      }

      return line.code || line.name ? [line] : []
    })

  return {
    ...fallback,
    eyebrow: resolveLocalizedApiString(section, "subtitle", locale, fallback.eyebrow),
    title: resolveLocalizedApiString(section, "title", locale, fallback.title),
    intro: resolveLocalizedApiString(section, "content", locale, fallback.intro),
    diagram: {
      ...fallback.diagram,
      title: localizedPayloadString(diagram, "title", locale, fallback.diagram.title),
      alt: localizedPayloadString(diagram, "alt", locale, fallback.diagram.alt),
      caption: localizedPayloadString(
        diagram,
        "caption",
        locale,
        fallback.diagram.caption,
      ),
    },
    legend: legend.length ? legend : fallback.legend,
    badges: {
      current: localizedPayloadString(
        badges,
        "current",
        locale,
        fallback.badges.current,
      ),
      sibling: localizedPayloadString(
        badges,
        "sibling",
        locale,
        fallback.badges.sibling,
      ),
      inactive: localizedPayloadString(
        badges,
        "inactive",
        locale,
        fallback.badges.inactive,
      ),
    },
    lines: lines.length ? lines : fallback.lines,
    mediaUrl:
      (locale === "ko" ? nonEmptyString(diagram?.media_url_ko) : null) ??
      nonEmptyString(diagram?.media_url) ??
      section?.media_url ??
      null,
  }
}

export function buildFinalCtaContent(
  fallback: HomeMessages["finalCta"],
  section: HomeSection | null | undefined,
  locale: Locale,
): FinalCtaContent {
  const payload = sectionPayload(section)

  return {
    ...fallback,
    title: resolveLocalizedApiString(section, "title", locale, fallback.title),
    description: resolveLocalizedApiString(
      section,
      "content",
      locale,
      fallback.description,
    ),
    primaryCta: localizedPayloadString(
      payload,
      "primary_cta_label",
      locale,
      fallback.primaryCta,
    ),
    secondaryCta: localizedPayloadString(
      payload,
      "secondary_cta_label",
      locale,
      fallback.secondaryCta,
    ),
    primaryHref: resolveCmsHref(
      locale,
      payloadString(payload, "primary_cta_url"),
      `${getLocalizedHref(locale, "b2b")}#inquiry`,
    ),
    secondaryHref: resolveCmsHref(
      locale,
      payloadString(payload, "secondary_cta_url"),
      getLocalizedHref(locale, "store"),
    ),
  }
}

export function buildMaterialProofPointsContent(
  fallback: SiteMessages["materialProof"]["proofPoints"],
  section: HomeSection | null | undefined,
  locale: Locale,
): SiteMessages["materialProof"]["proofPoints"] {
  const cards = payloadArray(section, "items")
    .flatMap((rawItem, index) => {
      if (!isRecord(rawItem)) {
        return []
      }

      const fallbackCard = fallback.cards[index]
      const card = {
        title: payloadItemString(rawItem, "title", locale, fallbackCard?.title),
        description: payloadItemString(
          rawItem,
          "description",
          locale,
          fallbackCard?.description,
        ),
      }

      return card.title || card.description ? [card] : []
    })

  return {
    ...fallback,
    eyebrow: resolveLocalizedApiString(section, "subtitle", locale, fallback.eyebrow),
    title: resolveLocalizedApiString(section, "title", locale, fallback.title),
    description: resolveLocalizedApiString(
      section,
      "content",
      locale,
      fallback.description,
    ),
    cards: cards.length ? cards : fallback.cards,
  }
}

export function buildTechnicalDownloadsContent(
  fallback: SiteMessages["materialProof"]["technicalDownloads"],
  section: HomeSection | null | undefined,
  locale: Locale,
): SiteMessages["materialProof"]["technicalDownloads"] {
  const payload = sectionPayload(section)

  return {
    ...fallback,
    eyebrow: resolveLocalizedApiString(section, "subtitle", locale, fallback.eyebrow),
    title: resolveLocalizedApiString(section, "title", locale, fallback.title),
    description: resolveLocalizedApiString(
      section,
      "content",
      locale,
      fallback.description,
    ),
    emptyTitle: localizedPayloadString(
      payload,
      "empty_title",
      locale,
      fallback.emptyTitle,
    ),
    emptyDescription: localizedPayloadString(
      payload,
      "empty_description",
      locale,
      fallback.emptyDescription,
    ),
    fileLabel: localizedPayloadString(payload, "file_label", locale, fallback.fileLabel),
    downloadLabel: resolveLocalizedApiString(
      section,
      "cta_label",
      locale,
      fallback.downloadLabel,
    ),
    onRequestLabel: localizedPayloadString(
      payload,
      "on_request_label",
      locale,
      fallback.onRequestLabel,
    ),
  }
}

export function buildCertificationsContent(
  fallback: SiteMessages["certificationsAtAGlance"],
  section: HomeSection | null | undefined,
  locale: Locale,
): SiteMessages["certificationsAtAGlance"] {
  const payload = sectionPayload(section)
  const statusLabels = isRecord(payload?.status_labels) ? payload.status_labels : null

  return {
    ...fallback,
    eyebrow: resolveLocalizedApiString(section, "subtitle", locale, fallback.eyebrow),
    title: resolveLocalizedApiString(section, "title", locale, fallback.title),
    description: resolveLocalizedApiString(
      section,
      "content",
      locale,
      fallback.description,
    ),
    verifiedLabel: localizedPayloadString(
      payload,
      "verified_label",
      locale,
      fallback.verifiedLabel,
    ),
    emptyMessage: localizedPayloadString(
      payload,
      "empty_message",
      locale,
      fallback.emptyMessage,
    ),
    issuerLabel: localizedPayloadString(
      payload,
      "issuer_label",
      locale,
      fallback.issuerLabel,
    ),
    testedAtLabel: localizedPayloadString(
      payload,
      "tested_at_label",
      locale,
      fallback.testedAtLabel,
    ),
    downloadLabel: localizedPayloadString(
      payload,
      "download_label",
      locale,
      fallback.downloadLabel,
    ),
    statusLabels: {
      certified: localizedPayloadString(
        statusLabels,
        "certified",
        locale,
        fallback.statusLabels.certified,
      ),
      tested: localizedPayloadString(
        statusLabels,
        "tested",
        locale,
        fallback.statusLabels.tested,
      ),
      in_testing: localizedPayloadString(
        statusLabels,
        "in_testing",
        locale,
        fallback.statusLabels.in_testing,
      ),
      pending: localizedPayloadString(
        statusLabels,
        "pending",
        locale,
        fallback.statusLabels.pending,
      ),
      demo: localizedPayloadString(
        statusLabels,
        "demo",
        locale,
        fallback.statusLabels.demo,
      ),
      not_applicable: localizedPayloadString(
        statusLabels,
        "not_applicable",
        locale,
        fallback.statusLabels.not_applicable,
      ),
    },
  }
}

export function buildTechnicalDownloads(
  section: HomeSection | null | undefined,
  locale: Locale,
) {
  return payloadArray(section, "downloads").flatMap((rawItem, index) => {
    if (!isRecord(rawItem)) {
      return []
    }

    const title = payloadItemString(rawItem, "title", locale, null)
    const description = payloadItemString(rawItem, "description", locale, null)

    return title || description || rawItem.url || rawItem.document_url
      ? [
          {
            id: `cms-${index}`,
            title,
            label: title,
            description,
            type: nonEmptyString(rawItem.type) ?? "document",
            url: nonEmptyString(rawItem.url),
            document_url: nonEmptyString(rawItem.document_url),
          },
        ]
      : []
  })
}

export function buildMaterialComparisonContent(
  fallback: SiteMessages["materialProof"]["comparison"],
  section: HomeSection | null | undefined,
  locale: Locale,
): SiteMessages["materialProof"]["comparison"] {
  const payload = sectionPayload(section)
  const columns = payloadArray(section, "columns")
    .flatMap((rawItem, index) => {
      if (!isRecord(rawItem)) {
        return []
      }

      const column = payloadItemString(rawItem, "label", locale, fallback.columns[index])

      return column ? [column] : []
    })
  const rows = payloadArray(section, "rows")
    .flatMap((rawItem, index) => {
      if (!isRecord(rawItem)) {
        return []
      }

      const fallbackRow = fallback.rows[index]
      const row = {
        label: payloadItemString(rawItem, "label", locale, fallbackRow?.label),
        oxp: payloadItemString(rawItem, "oxp", locale, fallbackRow?.oxp),
        plastic: payloadItemString(rawItem, "plastic", locale, fallbackRow?.plastic),
        ceramic: payloadItemString(rawItem, "ceramic", locale, fallbackRow?.ceramic),
        paper: payloadItemString(rawItem, "paper", locale, fallbackRow?.paper),
      }

      return row.label ? [row] : []
    })

  return {
    ...fallback,
    eyebrow: resolveLocalizedApiString(section, "subtitle", locale, fallback.eyebrow),
    title: resolveLocalizedApiString(section, "title", locale, fallback.title),
    description: resolveLocalizedApiString(
      section,
      "content",
      locale,
      fallback.description,
    ),
    columns: columns.length ? columns : fallback.columns,
    rows: rows.length ? rows : fallback.rows,
    disclaimer: localizedPayloadString(
      payload,
      "disclaimer",
      locale,
      fallback.disclaimer,
    ),
  }
}

function resolvePayloadItemString(
  item: Record<string, unknown>,
  field: string,
  locale: Locale,
  fallback?: string | null,
) {
  const translations = item[`${field}_translations`]
  const translatedValue =
    isRecord(translations) ? nonEmptyString(translations[locale]) : null

  if (translatedValue) {
    return translatedValue
  }

  const fallbackValue = nonEmptyString(fallback)

  if (locale !== "en" && fallbackValue) {
    return fallbackValue
  }

  const directValue = nonEmptyString(item[field])
  const englishValue =
    isRecord(translations) ? nonEmptyString(translations.en) : null

  return directValue ?? englishValue ?? fallbackValue ?? ""
}

export function buildPilotProjectsContent(
  fallback: SiteMessages["pilotProjects"],
  section: HomeSection | null | undefined,
  locale: Locale,
): SiteMessages["pilotProjects"] {
  const rawItems = section?.payload?.items
  const items = Array.isArray(rawItems)
    ? rawItems
        .flatMap((rawItem, index) => {
          if (!isRecord(rawItem)) {
            return []
          }

          const fallbackItem = fallback.items[index]
          const item = {
            title: resolvePayloadItemString(
              rawItem,
              "title",
              locale,
              fallbackItem?.title,
            ),
            status: resolvePayloadItemString(
              rawItem,
              "status",
              locale,
              fallbackItem?.status,
            ),
            description: resolvePayloadItemString(
              rawItem,
              "description",
              locale,
              fallbackItem?.description,
            ),
          }

          return item.title || item.status || item.description ? [item] : []
        })
    : []

  return {
    ...fallback,
    eyebrow: resolveLocalizedApiString(section, "subtitle", locale, fallback.eyebrow),
    title: resolveLocalizedApiString(section, "title", locale, fallback.title),
    description: resolveLocalizedApiString(
      section,
      "content",
      locale,
      fallback.description,
    ),
    items: items.length ? items : fallback.items,
  }
}
