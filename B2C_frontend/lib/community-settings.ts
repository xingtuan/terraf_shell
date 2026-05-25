import {
  defaultCommunitySettings,
  type CommunityPublicSettings,
} from "@/lib/api/public-settings"

export const communityImageExtensions = ["jpg", "jpeg", "png", "webp", "gif"]

export function normalizeCommunitySettings(
  settings?: CommunityPublicSettings | null,
): CommunityPublicSettings {
  const allowedExtensions = settings?.allowed_extensions?.length
    ? Array.from(
        new Set(
          settings.allowed_extensions
            .map((extension) => extension.replace(/^\./, "").trim().toLowerCase())
            .filter(Boolean),
        ),
      )
    : defaultCommunitySettings.allowed_extensions

  return {
    ...defaultCommunitySettings,
    ...(settings ?? {}),
    max_files: Math.max(1, Number(settings?.max_files ?? defaultCommunitySettings.max_files)),
    max_file_size_kb: Math.max(
      1,
      Number(settings?.max_file_size_kb ?? defaultCommunitySettings.max_file_size_kb),
    ),
    max_external_links: Math.max(
      0,
      Number(settings?.max_external_links ?? defaultCommunitySettings.max_external_links),
    ),
    allowed_extensions: allowedExtensions.length
      ? allowedExtensions
      : defaultCommunitySettings.allowed_extensions,
  }
}

export function countExternalLinks(value: unknown): number {
  if (Array.isArray(value)) {
    return value.reduce((count, item) => count + countExternalLinks(item), 0)
  }

  if (value === null || value === undefined) {
    return 0
  }

  const text = String(value).replace(/<[^>]*>/g, " ")

  return text.match(/https?:\/\/[^\s<>"')]+/gi)?.length ?? 0
}

export function getFileExtension(file: File): string {
  return file.name.split(".").pop()?.trim().toLowerCase() ?? ""
}

export function acceptsCommunityFile(
  file: File,
  settings: CommunityPublicSettings,
) {
  return settings.allowed_extensions.includes(getFileExtension(file))
}

export function acceptsCommunityImageFile(file: File) {
  return communityImageExtensions.includes(getFileExtension(file))
}

export function formatAllowedExtensions(settings: CommunityPublicSettings) {
  return settings.allowed_extensions.map((extension) => `.${extension}`).join(", ")
}

export function formatCommunityImageExtensions() {
  return communityImageExtensions.map((extension) => `.${extension}`).join(", ")
}

export function formatMaxFileSize(settings: CommunityPublicSettings) {
  const mb = settings.max_file_size_kb / 1024

  if (mb >= 1) {
    return `${Number.isInteger(mb) ? mb : mb.toFixed(1)} MB`
  }

  return `${settings.max_file_size_kb} KB`
}
