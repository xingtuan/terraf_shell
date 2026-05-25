import { requestApi } from "@/lib/api/client"

export type Branding = {
  logo_url: string | null
  logo_text: string
  logo_alt: string | null
}

export type MaintenanceNotice = {
  enabled: boolean
  message_en: string | null
  message_ko: string | null
  message_zh: string | null
  level: "info" | "warning" | "error"
}

export type CommunityPublicSettings = {
  allow_guest_upload: boolean
  max_files: number
  max_file_size_kb: number
  allowed_extensions: string[]
  max_external_links: number
  sensitive_words_enabled: boolean
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
  maintenance_notice: MaintenanceNotice
  community: CommunityPublicSettings
}

export const defaultBranding: Branding = {
  logo_url: null,
  logo_text: "OXP",
  logo_alt: "OXP",
}

export const defaultCommunitySettings: CommunityPublicSettings = {
  allow_guest_upload: false,
  max_files: 12,
  max_file_size_kb: 10240,
  allowed_extensions: [
    "jpg",
    "jpeg",
    "png",
    "webp",
    "gif",
    "pdf",
    "doc",
    "docx",
    "ppt",
    "pptx",
    "xls",
    "xlsx",
    "txt",
    "md",
    "csv",
    "zip",
    "rar",
    "7z",
    "stl",
    "obj",
    "glb",
    "gltf",
    "dwg",
    "dxf",
    "step",
    "stp",
    "iges",
    "igs",
    "srt",
  ],
  max_external_links: 4,
  sensitive_words_enabled: false,
}

export async function getPublicSettings(baseUrl?: string): Promise<PublicSettings> {
  const response = await requestApi<PublicSettings>("/public-settings", { baseUrl })

  return {
    ...response.data,
    community: {
      ...defaultCommunitySettings,
      ...(response.data.community ?? {}),
    },
  }
}
