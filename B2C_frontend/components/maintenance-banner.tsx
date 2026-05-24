import type { MaintenanceNotice } from "@/lib/api/public-settings"
import type { Locale } from "@/lib/i18n"
import { cn } from "@/lib/utils"

type MaintenanceBannerProps = {
  notice: MaintenanceNotice
  locale: Locale
  defaultMessage: string
}

function resolveMessage(notice: MaintenanceNotice, locale: Locale, defaultMessage: string): string {
  const localeMessage = {
    en: notice.message_en,
    ko: notice.message_ko,
    zh: notice.message_zh,
  }[locale]

  if (localeMessage && localeMessage.trim() !== "") return localeMessage

  if (notice.message_en && notice.message_en.trim() !== "") return notice.message_en

  return defaultMessage
}

const levelStyles: Record<string, string> = {
  info: "bg-blue-50 border-blue-200 text-blue-800 dark:bg-blue-950 dark:border-blue-800 dark:text-blue-200",
  warning: "bg-yellow-50 border-yellow-200 text-yellow-800 dark:bg-yellow-950 dark:border-yellow-800 dark:text-yellow-200",
  error: "bg-red-50 border-red-200 text-red-800 dark:bg-red-950 dark:border-red-800 dark:text-red-200",
}

export function MaintenanceBanner({ notice, locale, defaultMessage }: MaintenanceBannerProps) {
  const message = resolveMessage(notice, locale, defaultMessage)
  const level = notice.level ?? "info"
  const style = levelStyles[level] ?? levelStyles.info

  return (
    <div
      role="alert"
      className={cn(
        "sticky top-20 z-40 w-full border-b px-4 py-2.5 text-center text-sm font-medium",
        style,
      )}
    >
      {message}
    </div>
  )
}
