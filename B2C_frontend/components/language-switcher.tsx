"use client"

import Link from "next/link"
import { usePathname } from "next/navigation"

import { cn } from "@/lib/utils"
import { locales, type Locale, type SiteMessages } from "@/lib/i18n"

type LanguageSwitcherProps = {
  locale: Locale
  content: SiteMessages["languageSwitcher"]
  className?: string
}

function buildLocalizedPath(pathname: string, targetLocale: Locale) {
  const segments = pathname.split("/").filter(Boolean)

  if (segments.length === 0) {
    return `/${targetLocale}`
  }

  segments[0] = targetLocale

  return `/${segments.join("/")}`
}

export function LanguageSwitcher({
  locale,
  content,
  className,
}: LanguageSwitcherProps) {
  const pathname = usePathname()

  return (
    <div
      className={cn(
        "inline-flex items-center gap-1 rounded-full border border-border/70 bg-background/85 p-1 backdrop-blur-sm",
        className,
      )}
      aria-label={content.label}
    >
      {locales.map((language) => {
        const href = buildLocalizedPath(pathname, language)
        const isActive = language === locale

        return (
          <Link
            key={language}
            href={href}
            className={cn(
              "rounded-full px-3 py-1.5 text-xs tracking-[0.18em] uppercase transition-colors",
              isActive
                ? "bg-primary text-primary-foreground"
                : "text-muted-foreground hover:text-foreground",
            )}
          >
            {content.locales[language]}
          </Link>
        )
      })}
    </div>
  )
}
