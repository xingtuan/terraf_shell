import { getLocalizedHref, type Locale, type SiteMessages } from "@/lib/i18n"
import {
  payloadArray as readPayloadArray,
  payloadList,
} from "@/lib/payload-array"
import type {
  CertificationCardInput,
  CommunityIdea,
  HomeSection,
  MaterialDetail,
  MaterialSpec,
  MaterialSpecIcon,
} from "@/lib/types"

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

export type CommunityIdeasContent = SiteMessages["communityPage"]["ideas"] & {
  ctaPrimaryHref?: string
  ctaSecondaryHref?: string
  ideas?: CommunityIdea[]
}

export type ContactDetailsContent = Omit<SiteMessages["contactPage"]["details"], "cards"> & {
  cards: Array<
    SiteMessages["contactPage"]["details"]["cards"][number] & {
      hrefType?: string | null
      href?: string | null
      icon?: string | null
    }
  >
}

export type LeadFormFieldKey =
  | "name"
  | "companyName"
  | "organizationType"
  | "email"
  | "phone"
  | "country"
  | "region"
  | "companyWebsite"
  | "jobTitle"
  | "application"
  | "volume"
  | "timeline"
  | "message"
  | "collaborationGoal"
  | "projectStage"
  | "materialInterest"
  | "quantityEstimate"
  | "shippingCountry"
  | "shippingRegion"
  | "shippingAddress"
  | "intendedUse"

export type LeadCustomField = {
  key: string
  type: "text" | "textarea" | "select" | "checkbox"
  label: string
  placeholder: string
  helper: string
  required: boolean
  sortOrder: number
  options: Array<{ value: string; label: string }>
}

export type LeadFieldSetting = {
  key: LeadFormFieldKey
  label: string
  placeholder: string
  helper: string
  visible: boolean
  required: boolean
  sortOrder: number
}

export type B2BFormContent = Omit<SiteMessages["b2bPage"]["form"], "validation"> & {
  validation: SiteMessages["b2bPage"]["form"]["validation"] & {
    required: string
  }
  formAnchorId?: string
  leftPanelEyebrow?: string
  successMessage?: string
  helpers: Partial<Record<LeadFormFieldKey, string>>
  fieldSettings: Record<LeadFormFieldKey, LeadFieldSetting>
  customFields: LeadCustomField[]
  interestOptionList?: Array<{
    id: string
    interestType: string
    label: string
    description: string
  }>
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
  socialLinks?: Array<{ label: string; href: string }>
  legalLinks?: Array<{ label: string; href: string }>
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

const leadFormFieldPayloadKeys = {
  name: "name",
  company: "companyName",
  organizationType: "organizationType",
  email: "email",
  phone: "phone",
  country: "country",
  region: "region",
  companyWebsite: "companyWebsite",
  jobTitle: "jobTitle",
  application: "application",
  volume: "volume",
  timeline: "timeline",
  message: "message",
  collaborationGoal: "collaborationGoal",
  projectStage: "projectStage",
  materialInterest: "materialInterest",
  quantityEstimate: "quantityEstimate",
  shippingCountry: "shippingCountry",
  shippingRegion: "shippingRegion",
  shippingAddress: "shippingAddress",
  intendedUse: "intendedUse",
} as const

const leadFormValidationPayloadKeys = {
  defaultField: "default_field",
  required: "required",
  max: "max",
  nameRequired: "name_required",
  companyRequired: "company_required",
  emailRequired: "email_required",
  emailInvalid: "email_invalid",
  urlInvalid: "url_invalid",
  messageRequired: "message_required",
  applicationRequired: "application_required",
  organizationTypeRequired: "organization_type_required",
  collaborationGoalRequired: "collaboration_goal_required",
  materialInterestRequired: "material_interest_required",
  intendedUseRequired: "intended_use_required",
} as const

const legacyLeadFormFieldPayloadKeys: Record<LeadFormFieldKey, string> = {
  name: "name",
  companyName: "company",
  organizationType: "organization_type",
  email: "email",
  phone: "phone",
  country: "country",
  region: "region",
  companyWebsite: "company_website",
  jobTitle: "job_title",
  application: "application",
  volume: "volume",
  timeline: "timeline",
  message: "message",
  collaborationGoal: "collaboration_goal",
  projectStage: "project_stage",
  materialInterest: "material_interest",
  quantityEstimate: "quantity_estimate",
  shippingCountry: "shipping_country",
  shippingRegion: "shipping_region",
  shippingAddress: "shipping_address",
  intendedUse: "intended_use",
}

const leadFormFieldKeys = [
  "name",
  "companyName",
  "organizationType",
  "email",
  "phone",
  "country",
  "region",
  "companyWebsite",
  "jobTitle",
  "application",
  "volume",
  "timeline",
  "message",
  "collaborationGoal",
  "projectStage",
  "materialInterest",
  "quantityEstimate",
  "shippingCountry",
  "shippingRegion",
  "shippingAddress",
  "intendedUse",
] as const satisfies readonly LeadFormFieldKey[]

const leadInterestTypes = [
  "sample_request",
  "pellet_supply",
  "product_development",
  "bulk_order",
  "partnership",
  "other",
] as const

const legacyLeadFieldKeyLookup = Object.fromEntries(
  Object.entries(legacyLeadFormFieldPayloadKeys).map(([stable, legacy]) => [
    legacy,
    stable,
  ]),
) as Record<string, LeadFormFieldKey>

function normalizeLeadFormFieldKey(value: unknown): LeadFormFieldKey | null {
  const key = nonEmptyString(value)

  if (!key) {
    return null
  }

  if ((leadFormFieldKeys as readonly string[]).includes(key)) {
    return key as LeadFormFieldKey
  }

  return legacyLeadFieldKeyLookup[key] ?? null
}

function localizedLeadFieldString(
  payload: Record<string, unknown> | null,
  container: string,
  field: LeadFormFieldKey,
  locale: Locale,
  fallback?: string | null,
) {
  const source = isRecord(payload?.[container]) ? payload[container] : null
  const stable = localizedPayloadString(source, field, locale, null)

  if (stable) {
    return stable
  }

  const legacy = localizedPayloadString(
    source,
    legacyLeadFormFieldPayloadKeys[field],
    locale,
    null,
  )

  return legacy || fallback || ""
}

function leadFieldFallbackLabel(
  fallback: SiteMessages["b2bPage"]["form"],
  field: LeadFormFieldKey,
) {
  if (field === "companyName") {
    return fallback.fields.company
  }

  return fallback.fields[field as keyof typeof fallback.fields] ?? field
}

function leadFieldFallbackPlaceholder(
  fallback: SiteMessages["b2bPage"]["form"],
  field: LeadFormFieldKey,
) {
  if (field === "companyName") {
    return fallback.placeholders.company
  }

  return fallback.placeholders[field as keyof typeof fallback.placeholders] ?? ""
}

function buildLeadHelpers(
  payload: Record<string, unknown> | null,
  locale: Locale,
): Partial<Record<LeadFormFieldKey, string>> {
  const helpers: Partial<Record<LeadFormFieldKey, string>> = {}

  for (const field of leadFormFieldKeys) {
    const helper = localizedLeadFieldString(payload, "helpers", field, locale, "")

    if (helper) {
      helpers[field] = helper
    }
  }

  return helpers
}

function buildLeadFieldSettings(
  payload: Record<string, unknown> | null,
  fallback: SiteMessages["b2bPage"]["form"],
  locale: Locale,
): Record<LeadFormFieldKey, LeadFieldSetting> {
  const defaultRequired = new Set<LeadFormFieldKey>([
    "name",
    "companyName",
    "email",
    "application",
    "message",
  ])
  const settings = Object.fromEntries(
    leadFormFieldKeys.map((field, index) => [
      field,
      {
        key: field,
        label: localizedLeadFieldString(
          payload,
          "fields",
          field,
          locale,
          leadFieldFallbackLabel(fallback, field),
        ),
        placeholder: localizedLeadFieldString(
          payload,
          "placeholders",
          field,
          locale,
          leadFieldFallbackPlaceholder(fallback, field),
        ),
        helper: localizedLeadFieldString(payload, "helpers", field, locale, ""),
        visible: true,
        required: defaultRequired.has(field),
        sortOrder: (index + 1) * 10,
      },
    ]),
  ) as Record<LeadFormFieldKey, LeadFieldSetting>

  for (const rawSetting of payloadList(payload?.field_settings)) {
    if (!isRecord(rawSetting)) {
      continue
    }

    const key = normalizeLeadFormFieldKey(rawSetting.key)

    if (!key) {
      continue
    }

    settings[key] = {
      ...settings[key],
      visible:
        typeof rawSetting.visible === "boolean"
          ? rawSetting.visible
          : settings[key].visible,
      required:
        typeof rawSetting.required === "boolean"
          ? rawSetting.required
          : settings[key].required,
      sortOrder:
        typeof rawSetting.sort_order === "number"
          ? rawSetting.sort_order
          : typeof rawSetting.order === "number"
            ? rawSetting.order
            : settings[key].sortOrder,
    }
  }

  return settings
}

function localizedOptionLabel(
  item: Record<string, unknown>,
  locale: Locale,
  fallback: string,
) {
  return payloadItemString(item, "label", locale, fallback) || fallback
}

function buildLeadCustomFields(
  payload: Record<string, unknown> | null,
  locale: Locale,
): LeadCustomField[] {
  return payloadList(payload?.custom_fields)
    .flatMap((rawField) => {
      if (!isRecord(rawField)) {
        return []
      }

      const key = nonEmptyString(rawField.key)
      const type = nonEmptyString(rawField.type)

      if (
        !key ||
        !type ||
        !["text", "textarea", "select", "checkbox"].includes(type)
      ) {
        return []
      }

      const options = payloadList(rawField.options)
        .flatMap((rawOption) => {
          if (!isRecord(rawOption)) {
            return []
          }

          const value = nonEmptyString(rawOption.value)

          return value
            ? [
                {
                  value,
                  label: localizedOptionLabel(rawOption, locale, value),
                },
              ]
            : []
        })

      return [
        {
          key,
          type: type as LeadCustomField["type"],
          label: payloadItemString(rawField, "label", locale, key) || key,
          placeholder:
            payloadItemString(rawField, "placeholder", locale, "") || "",
          helper: payloadItemString(rawField, "helper", locale, "") || "",
          required: rawField.required === true,
          sortOrder:
            typeof rawField.sort_order === "number" ? rawField.sort_order : 0,
          options,
        },
      ]
    })
    .sort((a, b) => a.sortOrder - b.sortOrder)
}

export function payloadArray(
  section: HomeSection | null | undefined,
  field: string,
): unknown[] {
  return readPayloadArray(section, field)
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

function localizedPayloadRecord<T extends Record<string, string>>(
  payload: Record<string, unknown> | null,
  field: string,
  locale: Locale,
  fallback: T,
  fieldMap: Record<keyof T, string>,
): T {
  const source = isRecord(payload?.[field]) ? payload[field] : null
  const resolved = { ...fallback }

  for (const [contentKey, payloadKey] of Object.entries(fieldMap)) {
    resolved[contentKey as keyof T] = localizedPayloadString(
      source,
      payloadKey,
      locale,
      fallback[contentKey as keyof T],
    ) as T[keyof T]
  }

  return resolved
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

function footerLinkItems(
  locale: Locale,
  payload: Record<string, unknown> | null,
  field: string,
  fallback: Array<{ label: string; href: string }>,
) {
  const items = payloadList(payload?.[field])
  const resolved = items.flatMap((rawItem, index) => {
    if (!isRecord(rawItem)) {
      return []
    }

    const label = resolvePayloadItemString(
      rawItem,
      "label",
      locale,
      fallback[index]?.label,
    )
    const href = resolveFooterHref(
      locale,
      nonEmptyString(rawItem.href),
      fallback[index]?.href ?? getLocalizedHref(locale),
    )

    return label && href ? [{ label, href }] : []
  })

  return resolved.length ? resolved : fallback
}

export function buildFooterContent(
  fallback: SiteMessages["footer"],
  section: HomeSection | null | undefined,
  locale: Locale,
  headerFallback?: SiteMessages["header"],
): FooterContent {
  const payload =
    section?.key === "footer" && isRecord(section.payload) ? section.payload : null

  const privacyHref = resolveFooterHref(
    locale,
    payloadString(payload, "privacy_href"),
    getLocalizedHref(locale, "privacy"),
  )
  const termsHref = resolveFooterHref(
    locale,
    payloadString(payload, "terms_href"),
    getLocalizedHref(locale, "terms"),
  )
  const privacyLabel = resolveLocalizedApiString(
    payload,
    "privacy",
    locale,
    fallback.privacy,
  )
  const termsLabel = resolveLocalizedApiString(payload, "terms", locale, fallback.terms)

  const footerContent = {
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
    privacy: privacyLabel,
    terms: termsLabel,
    emailValue: payloadString(payload, "email_value") ?? undefined,
    emailHref: payloadString(payload, "email_href") ?? undefined,
    phoneValue: payloadString(payload, "phone_value") ?? undefined,
    phoneHref: payloadString(payload, "phone_href") ?? undefined,
    locationHref: resolveFooterHref(
      locale,
      payloadString(payload, "location_href"),
      getLocalizedHref(locale, "contact"),
    ),
    privacyHref,
    termsHref,
    socialLinks: footerLinkItems(locale, payload, "social_links", []),
    legalLinks: footerLinkItems(locale, payload, "legal_links", [
      { label: privacyLabel, href: privacyHref },
      { label: termsLabel, href: termsHref },
    ]),
  }

  return footerContent
}

export function buildContactDetailsFromFooterContent(
  footerContent: FooterContent,
  fallback: SiteMessages["contactPage"]["details"],
  emailFallback: string,
): SiteMessages["contactPage"]["details"] {
  const cards = fallback.cards.map((card, index) => {
    if (index === 0) {
      return { ...card, value: footerContent.emailValue ?? emailFallback }
    }
    if (index === 1) {
      return { ...card, value: footerContent.phoneValue ?? card.value }
    }
    if (index === 2) {
      return { ...card, value: footerContent.locationValue ?? card.value }
    }
    return card
  })
  return { ...fallback, cards }
}

type MarketingIntroContent = {
  eyebrow: string
  title: string
  description: string
  primaryCta: string
  secondaryCta: string
}

export type PageIntroContent<T extends MarketingIntroContent> = T & {
  primaryHref?: string
  secondaryHref?: string
}

export function buildPageIntroContent<T extends MarketingIntroContent>(
  fallback: T,
  section: HomeSection | null | undefined,
  locale: Locale,
  primaryHref: string,
  secondaryHref: string,
): PageIntroContent<T> {
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
    primaryCta: resolveLocalizedApiString(
      section,
      "cta_label",
      locale,
      fallback.primaryCta,
    ),
    secondaryCta: localizedPayloadString(
      payload,
      "secondary_cta_label",
      locale,
      fallback.secondaryCta,
    ),
    primaryHref: resolveCmsHref(locale, section?.cta_url, primaryHref),
    secondaryHref: resolveCmsHref(
      locale,
      payloadString(payload, "secondary_cta_url"),
      secondaryHref,
    ),
  }
}

export function buildStoreGridContent(
  fallback: SiteMessages["storePage"]["grid"],
  section: HomeSection | null | undefined,
  locale: Locale,
): SiteMessages["storePage"]["grid"] {
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
    pricePrefix: localizedPayloadString(payload, "price_prefix", locale, fallback.pricePrefix),
    availabilityLabel: localizedPayloadString(payload, "availability_label", locale, fallback.availabilityLabel),
    categoryQuickFilterLabel: localizedPayloadString(payload, "category_quick_filter_label", locale, fallback.categoryQuickFilterLabel),
    filtersTitle: localizedPayloadString(payload, "filters_title", locale, fallback.filtersTitle),
    searchLabel: localizedPayloadString(payload, "search_label", locale, fallback.searchLabel),
    searchPlaceholder: localizedPayloadString(payload, "search_placeholder", locale, fallback.searchPlaceholder),
    allOption: localizedPayloadString(payload, "all_option", locale, fallback.allOption),
    filterHint: localizedPayloadString(payload, "filter_hint", locale, fallback.filterHint),
    categoryHint: localizedPayloadString(payload, "category_hint", locale, fallback.categoryHint),
    activeFiltersLabel: localizedPayloadString(payload, "active_filters_label", locale, fallback.activeFiltersLabel),
    removeFilterLabel: localizedPayloadString(payload, "remove_filter_label", locale, fallback.removeFilterLabel),
    sortLabel: localizedPayloadString(payload, "sort_label", locale, fallback.sortLabel),
    stockLabel: localizedPayloadString(payload, "stock_label", locale, fallback.stockLabel),
    priceLabel: localizedPayloadString(payload, "price_label", locale, fallback.priceLabel),
    minPrice: localizedPayloadString(payload, "min_price", locale, fallback.minPrice),
    maxPrice: localizedPayloadString(payload, "max_price", locale, fallback.maxPrice),
    applyFilters: localizedPayloadString(payload, "apply_filters", locale, fallback.applyFilters),
    clearAll: localizedPayloadString(payload, "clear_all", locale, fallback.clearAll),
    resultLabel: localizedPayloadString(payload, "result_label", locale, fallback.resultLabel),
    searchResultTitle: localizedPayloadString(payload, "search_result_title", locale, fallback.searchResultTitle),
    filteredProductsTitle: localizedPayloadString(payload, "filtered_products_title", locale, fallback.filteredProductsTitle),
    allProductsTitle: localizedPayloadString(payload, "all_products_title", locale, fallback.allProductsTitle),
    showingLabel: localizedPayloadString(payload, "showing_label", locale, fallback.showingLabel),
    emptyTitle: localizedPayloadString(payload, "empty_title", locale, fallback.emptyTitle),
    emptyDescription: localizedPayloadString(payload, "empty_description", locale, fallback.emptyDescription),
    emptyAction: localizedPayloadString(payload, "empty_action", locale, fallback.emptyAction),
    errorTitle: localizedPayloadString(payload, "error_title", locale, fallback.errorTitle),
    errorDescription: localizedPayloadString(payload, "error_description", locale, fallback.errorDescription),
    retryAction: localizedPayloadString(payload, "retry_action", locale, fallback.retryAction),
    attributeLabel: localizedPayloadString(payload, "attribute_label", locale, fallback.attributeLabel),
  }
}

export function buildStoreFaqContent(
  fallback: SiteMessages["storePage"]["faq"],
  section: HomeSection | null | undefined,
  locale: Locale,
): SiteMessages["storePage"]["faq"] {
  const items = payloadArray(section, "items")
    .flatMap((rawItem, index) => {
      if (!isRecord(rawItem)) {
        return []
      }

      const fallbackItem = fallback.items[index]
      const item = {
        question: payloadItemString(rawItem, "question", locale, fallbackItem?.question),
        answer: payloadItemString(rawItem, "answer", locale, fallbackItem?.answer),
      }

      return item.question || item.answer ? [item] : []
    })

  return {
    ...fallback,
    eyebrow: resolveLocalizedApiString(section, "subtitle", locale, fallback.eyebrow),
    title: resolveLocalizedApiString(section, "title", locale, fallback.title),
    items: items.length ? items : fallback.items,
  }
}

export function buildCommunityIdeasContent(
  fallback: SiteMessages["communityPage"]["ideas"],
  section: HomeSection | null | undefined,
  locale: Locale,
): CommunityIdeasContent {
  const payload = sectionPayload(section)

  const rawIdeaItems = payloadArray(section, "items")
  const rawIdeas = rawIdeaItems.length ? rawIdeaItems : payloadArray(section, "cards")
  const cmsIdeas = rawIdeas.flatMap(
    (rawItem, index): CommunityIdea[] => {
      if (!isRecord(rawItem)) return []
      const title = payloadItemString(rawItem, "title", locale)
      if (!title) return []
      const tagsField = locale === "zh" ? "tags_zh" : locale === "ko" ? "tags_ko" : "tags_en"
      const tagsRaw = typeof rawItem[tagsField] === "string" ? (rawItem[tagsField] as string) : ""
      return [
        {
          id:
            typeof rawItem.key === "string" && rawItem.key
              ? rawItem.key
              : `cms-idea-${index}`,
          title,
          summary: payloadItemString(rawItem, "summary", locale),
          stage: payloadItemString(rawItem, "stage", locale),
          supportType: payloadItemString(rawItem, "support_type", locale),
          focus: payloadItemString(rawItem, "focus", locale),
          image:
            typeof rawItem.media_url === "string" && rawItem.media_url
              ? rawItem.media_url
              : "/images/application-tableware.jpg",
          tags: tagsRaw
            .split(",")
            .map((t) => t.trim())
            .filter(Boolean),
        },
      ]
    },
  )

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
    focusLabel: localizedPayloadString(payload, "focus_label", locale, fallback.focusLabel),
    stageLabel: localizedPayloadString(payload, "stage_label", locale, fallback.stageLabel),
    supportLabel: localizedPayloadString(payload, "support_label", locale, fallback.supportLabel),
    ctaPrimary: localizedPayloadString(payload, "cta_primary_label", locale, fallback.ctaPrimary),
    ctaSecondary: localizedPayloadString(payload, "cta_secondary_label", locale, fallback.ctaSecondary),
    ctaPrimaryHref: resolveCmsHref(
      locale,
      payloadString(payload, "cta_primary_url"),
      getLocalizedHref(locale, "community/new"),
    ),
    ctaSecondaryHref: resolveCmsHref(
      locale,
      payloadString(payload, "cta_secondary_url"),
      getLocalizedHref(locale, "contact"),
    ),
    ...(cmsIdeas.length ? { ideas: cmsIdeas } : {}),
  }
}

export function buildContactDetailsContent(
  fallback: SiteMessages["contactPage"]["details"],
  section: HomeSection | null | undefined,
  footerContent: FooterContent,
  emailFallback: string,
  locale: Locale,
): ContactDetailsContent {
  const syncedFallback = buildContactDetailsFromFooterContent(
    footerContent,
    fallback,
    emailFallback,
  )
  const payload = sectionPayload(section)
  const rawCards = payloadArray(section, "cards")
  const cmsCards = (rawCards.length ? rawCards : payloadArray(section, "items"))
    .flatMap((rawItem, index) => {
      if (!isRecord(rawItem)) {
        return []
      }

      const fallbackCard = syncedFallback.cards[index]
      const card = {
        label: payloadItemString(rawItem, "label", locale, fallbackCard?.label),
        value: payloadItemString(rawItem, "value", locale, fallbackCard?.value),
        detail: payloadItemString(rawItem, "detail", locale, fallbackCard?.detail),
        hrefType: nonEmptyString(rawItem.href_type),
        href: nonEmptyString(rawItem.href),
        icon: nonEmptyString(rawItem.icon),
      }

      return card.label || card.value || card.detail ? [card] : []
    })

  return {
    ...syncedFallback,
    eyebrow: resolveLocalizedApiString(section, "subtitle", locale, syncedFallback.eyebrow),
    title: resolveLocalizedApiString(section, "title", locale, syncedFallback.title),
    description: resolveLocalizedApiString(
      section,
      "content",
      locale,
      syncedFallback.description,
    ),
    cards: cmsCards.length ? cmsCards : syncedFallback.cards,
    response: localizedPayloadString(payload, "response", locale, syncedFallback.response),
  }
}

export function buildB2BProcessContent(
  fallback: SiteMessages["b2bPage"]["process"],
  section: HomeSection | null | undefined,
  locale: Locale,
): SiteMessages["b2bPage"]["process"] {
  const steps = payloadArray(section, "items")
    .flatMap((rawItem, index) => {
      if (!isRecord(rawItem)) {
        return []
      }

      const fallbackStep = fallback.steps[index]
      const step = {
        title: payloadItemString(rawItem, "title", locale, fallbackStep?.title),
        description: payloadItemString(
          rawItem,
          "description",
          locale,
          fallbackStep?.description,
        ),
      }

      return step.title || step.description ? [step] : []
    })

  return {
    ...fallback,
    eyebrow: resolveLocalizedApiString(section, "subtitle", locale, fallback.eyebrow),
    title: resolveLocalizedApiString(section, "title", locale, fallback.title),
    steps: steps.length ? steps : fallback.steps,
  }
}

export function buildB2BCtaStripContent(
  fallback: SiteMessages["b2bPage"]["ctaStrip"],
  section: HomeSection | null | undefined,
  locale: Locale,
): SiteMessages["b2bPage"]["ctaStrip"] {
  const payload = sectionPayload(section)

  return {
    ...fallback,
    sample: localizedPayloadString(payload, "sample", locale, fallback.sample),
    materialData: localizedPayloadString(
      payload,
      "material_data",
      locale,
      fallback.materialData,
    ),
    requirements: localizedPayloadString(
      payload,
      "requirements",
      locale,
      fallback.requirements,
    ),
    bulkSupply: localizedPayloadString(
      payload,
      "bulk_supply",
      locale,
      fallback.bulkSupply,
    ),
  }
}

export function buildB2BApplicationsContent(
  fallback: SiteMessages["b2bPage"]["applications"],
  section: HomeSection | null | undefined,
  locale: Locale,
): SiteMessages["b2bPage"]["applications"] {
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
    cards: cards.length ? cards : fallback.cards,
  }
}

export function buildB2BFormContent(
  fallback: SiteMessages["b2bPage"]["form"],
  section: HomeSection | null | undefined,
  locale: Locale,
): B2BFormContent {
  const payload = sectionPayload(section)
  const payloadInterestOptions = payloadArray(section, "interest_options")
    .flatMap((rawItem) => {
      if (!isRecord(rawItem)) {
        return []
      }

      const id = nonEmptyString(rawItem.id)
      const interestType = nonEmptyString(rawItem.interest_type)

      if (!id || !interestType) {
        return []
      }

      const fallbackOption =
        fallback.interestOptions[
          interestType as keyof typeof fallback.interestOptions
        ]

      return [
        {
          id,
          interestType,
          label: payloadItemString(rawItem, "label", locale, fallbackOption?.label),
          description: payloadItemString(
            rawItem,
            "description",
            locale,
            fallbackOption?.description,
          ),
        },
      ]
    })
  const interestOptions = {
    ...fallback.interestOptions,
  }

  for (const option of payloadInterestOptions) {
    if (option.interestType in interestOptions) {
      interestOptions[option.interestType as keyof typeof interestOptions] = {
        label: option.label,
        description: option.description,
      }
    }
  }

  const panelCopyPayload = isRecord(payload?.panel_copy)
    ? payload.panel_copy
    : null
  const panelCopy = { ...fallback.panelCopy }

  for (const interestType of leadInterestTypes) {
    const lines = payloadList(panelCopyPayload?.[interestType])
      .flatMap((rawLine, index) => {
        if (isRecord(rawLine)) {
          const line = payloadItemString(
            rawLine,
            "line",
            locale,
            fallback.panelCopy[interestType]?.[index],
          )

          return line ? [line] : []
        }

        const line = resolveLocalizedApiValue(
          rawLine,
          fallback.panelCopy[interestType]?.[index],
          locale,
        )

        return line ? [line] : []
      })

    if (lines.length) {
      panelCopy[interestType] = lines
    }
  }
  const fields = { ...fallback.fields }
  const placeholders = { ...fallback.placeholders }

  for (const [contentKey, fieldKey] of Object.entries(leadFormFieldPayloadKeys)) {
    const stableFieldKey = fieldKey as LeadFormFieldKey
    fields[contentKey as keyof typeof fields] = localizedLeadFieldString(
      payload,
      "fields",
      stableFieldKey,
      locale,
      fields[contentKey as keyof typeof fields],
    ) as (typeof fields)[keyof typeof fields]
    placeholders[contentKey as keyof typeof placeholders] =
      localizedLeadFieldString(
        payload,
        "placeholders",
        stableFieldKey,
        locale,
        placeholders[contentKey as keyof typeof placeholders],
      ) as (typeof placeholders)[keyof typeof placeholders]
  }

  const validationFallback = {
    ...fallback.validation,
    required: "{field} is required.",
  }
  const fieldSettings = buildLeadFieldSettings(payload, fallback, locale)

  return {
    ...fallback,
    groups: localizedPayloadRecord(
      payload,
      "groups",
      locale,
      fallback.groups,
      {
        contact: "contact",
        project: "project",
        material: "material",
        collaboration: "collaboration",
        shipping: "shipping",
      },
    ),
    fields,
    placeholders,
    helpers: buildLeadHelpers(payload, locale),
    validation: localizedPayloadRecord(
      payload,
      "validation",
      locale,
      validationFallback,
      leadFormValidationPayloadKeys,
    ),
    fieldSettings,
    customFields: buildLeadCustomFields(payload, locale),
    interestOptions,
    panelCopy,
    eyebrow: resolveLocalizedApiString(section, "subtitle", locale, fallback.eyebrow),
    title: resolveLocalizedApiString(section, "title", locale, fallback.title),
    description: resolveLocalizedApiString(
      section,
      "content",
      locale,
      fallback.description,
    ),
    productContextLabel: localizedPayloadString(
      payload,
      "product_context_label",
      locale,
      fallback.productContextLabel,
    ),
    leftPanelEyebrow: localizedPayloadString(
      payload,
      "left_panel_eyebrow",
      locale,
      "",
    ) || undefined,
    disclaimer: localizedPayloadString(
      payload,
      "privacy_note",
      locale,
      localizedPayloadString(payload, "disclaimer", locale, fallback.disclaimer),
    ),
    submit: localizedPayloadString(
      payload,
      "submit_button_label",
      locale,
      resolveLocalizedApiString(section, "cta_label", locale, fallback.submit),
    ),
    formAnchorId: payloadString(payload, "form_anchor_id") ?? undefined,
    successMessage: localizedPayloadString(
      payload,
      "submit_success_message",
      locale,
      "",
    ) || undefined,
    interestOptionList: payloadInterestOptions.length
      ? payloadInterestOptions
      : undefined,
  }
}

export function buildB2BAfterSubmitContent(
  fallback: SiteMessages["b2bPage"]["afterSubmit"],
  section: HomeSection | null | undefined,
  locale: Locale,
): SiteMessages["b2bPage"]["afterSubmit"] {
  const items = payloadArray(section, "items")
    .flatMap((rawItem, index) => {
      if (!isRecord(rawItem)) {
        return []
      }

      const item = payloadItemString(rawItem, "label", locale, fallback.items[index])

      return item ? [item] : []
    })

  return {
    ...fallback,
    eyebrow: resolveLocalizedApiString(section, "subtitle", locale, fallback.eyebrow),
    title: resolveLocalizedApiString(section, "title", locale, fallback.title),
    items: items.length ? items : fallback.items,
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
        description:
          payloadItemString(rawItem, "content", resolvedLocale, null) ||
          payloadItemString(rawItem, "description", resolvedLocale, null) ||
          payloadItemString(rawItem, "highlight", resolvedLocale, fallbackStep?.description),
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
        description:
          payloadItemString(rawItem, "description", resolvedLocale, null) ||
          payloadItemString(rawItem, "audience", resolvedLocale, fallbackItem?.description),
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
  _material?: MaterialDetail | null,
  scienceSection?: HomeSection | null,
  locale?: Locale,
): HomeMessages["materialFacts"] {
  const resolvedLocale = locale ?? "en"
  const payload = sectionPayload(scienceSection)
  const infoCardPayload = payloadArray(scienceSection, "info_cards")
  const legacyMetricPayload = payloadArray(scienceSection, "metrics")
  const cmsInfoCards = (infoCardPayload.length ? infoCardPayload : legacyMetricPayload)
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
  const infoCards = cmsInfoCards.length ? cmsInfoCards : fallback.infoCards

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
  const sectionCta = localizedPayloadString(
    payload,
    "sheet_cta_label",
    resolvedLocale,
    null,
  )
  const legacySectionCta =
    locale && scienceSection
      ? resolveLocalizedApiString(scienceSection, "cta_label", locale, fallback.sheetCta)
      : scienceSection?.cta_label || fallback.sheetCta

  return {
    ...fallback,
    eyebrow: sectionEyebrow,
    title: sectionTitle || fallback.title,
    sheetTitle:
      localizedPayloadString(payload, "sheet_title", resolvedLocale, null) ||
      fallback.sheetTitle,
    sheetDescription:
      localizedPayloadString(payload, "sheet_description", resolvedLocale, null) ||
      sectionContent ||
      fallback.sheetDescription,
    sheetCta: sectionCta || legacySectionCta,
    infoCards,
    note:
      localizedPayloadString(payload, "note", resolvedLocale, null) ||
      fallback.note,
  }
}

const materialSpecIconFallbacks: MaterialSpecIcon[] = [
  "feather",
  "shield",
  "leaf",
  "badge",
]

function materialSpecIcon(value: unknown, index = 0): MaterialSpecIcon {
  return value === "feather" ||
    value === "shield" ||
    value === "leaf" ||
    value === "badge"
    ? value
    : materialSpecIconFallbacks[index % materialSpecIconFallbacks.length]
}

export function hasCmsFactCards(section: HomeSection | null | undefined): boolean {
  return payloadArray(section, "items").length > 0 || payloadArray(section, "metrics").length > 0
}

export function buildMaterialFactSpecs(
  section: HomeSection | null | undefined,
  locale: Locale,
  fallbackSpecs: MaterialSpec[] = [],
): MaterialSpec[] {
  const factItems = payloadArray(section, "items")
  const legacyMetricItems = payloadArray(section, "metrics")
  const rawItems = factItems.length ? factItems : legacyMetricItems

  if (!rawItems.length) {
    return fallbackSpecs
  }

  return rawItems.flatMap((rawItem, index) => {
    if (!isRecord(rawItem)) {
      return []
    }

    const label = payloadItemString(rawItem, "label", locale)
    const value = payloadItemString(rawItem, "value", locale)
    const detail =
      payloadItemString(rawItem, "detail", locale, null) ||
      payloadItemString(rawItem, "description", locale, null)

    if (!label && !value && !detail) {
      return []
    }

    return [
      {
        id: nonEmptyString(rawItem.key) ?? `cms-material-fact-${index}`,
        key: nonEmptyString(rawItem.key),
        label,
        value,
        detail,
        unit: nonEmptyString(rawItem.unit),
        icon: materialSpecIcon(rawItem.icon, index),
        sort_order: index,
        media_url: payloadMediaUrl(rawItem),
      },
    ]
  })
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
  const benefitPayloadItems = payloadArray(section, "benefits")
  const metricPayloadItems = payloadArray(section, "metrics")
  const cmsBenefits = (benefitPayloadItems.length ? benefitPayloadItems : metricPayloadItems)
    .flatMap((rawItem, index) => {
      if (typeof rawItem === "string") {
        return rawItem.trim() ? [rawItem] : []
      }

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
  const legend = payloadList(payload?.legend)
    .flatMap((rawItem, index) => {
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
      null,
    ) || resolveLocalizedApiString(
      section,
      "cta_label",
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
      payloadString(payload, "primary_cta_url") ?? section?.cta_url,
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

export type CertificationsContent = SiteMessages["certificationsAtAGlance"] & {
  certifications?: CertificationCardInput[]
}

export function buildCertificationsContent(
  fallback: SiteMessages["certificationsAtAGlance"],
  section: HomeSection | null | undefined,
  locale: Locale,
): CertificationsContent {
  const payload = sectionPayload(section)
  const statusLabels = isRecord(payload?.status_labels) ? payload.status_labels : null

  const certificationItems = payloadArray(section, "items")
  const cmsCertifications = (certificationItems.length
    ? certificationItems
    : payloadArray(section, "certifications")).flatMap(
    (rawItem): CertificationCardInput[] => {
      if (!isRecord(rawItem)) return []
      const name = payloadItemString(rawItem, "name", locale) || undefined
      const label = payloadItemString(rawItem, "label", locale) || undefined
      if (!name && !label) return []
      return [
        {
          key: typeof rawItem.key === "string" ? rawItem.key : undefined,
          name,
          label,
          value: payloadItemString(rawItem, "value", locale) || undefined,
          result: typeof rawItem.result === "string" ? rawItem.result : undefined,
          unit: typeof rawItem.unit === "string" ? rawItem.unit : undefined,
          status: typeof rawItem.status === "string" ? rawItem.status : undefined,
          verified: typeof rawItem.verified === "boolean" ? rawItem.verified : undefined,
          description: payloadItemString(rawItem, "description", locale) || undefined,
          issuer: payloadItemString(rawItem, "issuer", locale) || undefined,
          tested_at: typeof rawItem.tested_at === "string" ? rawItem.tested_at : undefined,
          document_url:
            typeof rawItem.document_url === "string" ? rawItem.document_url : undefined,
        },
      ]
    },
  )

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
      not_applicable: localizedPayloadString(
        statusLabels,
        "not_applicable",
        locale,
        fallback.statusLabels.not_applicable,
      ),
    },
    ...(cmsCertifications.length ? { certifications: cmsCertifications } : {}),
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
  const items = payloadArray(section, "items")
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
