import { requestApi } from "@/lib/api/client"

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
  maintenance_notice: {
    enabled: boolean
    message: string | null
    level: string
  }
}

export async function getPublicSettings(): Promise<PublicSettings> {
  const response = await requestApi<PublicSettings>("/public-settings")

  return response.data
}
