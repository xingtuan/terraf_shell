import { getMessages, type Locale, type SiteMessages } from "@/lib/i18n"

export type CommunityCopy = SiteMessages["community"]

export function getCommunityCopy(locale: Locale): CommunityCopy {
  return getMessages(locale).community
}
