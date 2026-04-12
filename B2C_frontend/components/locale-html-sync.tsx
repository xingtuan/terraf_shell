"use client"

import { useEffect } from "react"

import type { Locale } from "@/lib/i18n"

type LocaleHtmlSyncProps = {
  locale: Locale
}

export function LocaleHtmlSync({ locale }: LocaleHtmlSyncProps) {
  useEffect(() => {
    document.documentElement.lang = locale
  }, [locale])

  return null
}
