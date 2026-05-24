import { requestApi } from "@/lib/api/client"

export type Branding = {
  logo_url: string | null
  logo_text: string
  logo_alt: string | null
}

export type PublicSettings = {
  site_name: string
  default_locale: string
  supported_locales: string[]
  store_enabled: boolean
  b2b_inquiry_enabled: boolean
  community_enabled: boolean
  guest_checkout_enabled: boolean
  funding_links_enabled: boolean
  nz_only_shipping: boolean
  contact_email: string | null
  support_email: string | null
  branding: Branding
  maintenance_mode: {
    enabled: boolean
  }
  maintenance_notice: {
    enabled: boolean
    message: string | null
    level: string
  }
}

export const defaultBranding: Branding = {
  logo_url: null,
  logo_text: "OXP",
  logo_alt: "OXP",
}

export async function getPublicSettings(baseUrl?: string): Promise<PublicSettings> {
  const response = await requestApi<PublicSettings>("/public-settings", { baseUrl })

  return response.data
}
