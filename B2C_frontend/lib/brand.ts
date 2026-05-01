export const BRAND_NAME = "OXP"
export const BRAND_DISPLAY_NAME = "OXP"

// TODO: Set this once the client confirms the final OXP contact email.
export const BRAND_CONTACT_EMAIL =
  process.env.NEXT_PUBLIC_BRAND_CONTACT_EMAIL?.trim() ?? ""

export const BRAND_CONTACT_EMAIL_FALLBACK = "Contact email pending"

export function getBrandContactLabel() {
  return BRAND_CONTACT_EMAIL || BRAND_CONTACT_EMAIL_FALLBACK
}

export function getBrandContactHref(fallbackHref: string) {
  return BRAND_CONTACT_EMAIL ? `mailto:${BRAND_CONTACT_EMAIL}` : fallbackHref
}
