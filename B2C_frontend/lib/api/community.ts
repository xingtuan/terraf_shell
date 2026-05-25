import { communityIdeaRecords } from "@/lib/data/community"
import { getPublicSettings } from "@/lib/api/public-settings"
import {
  countExternalLinks,
  normalizeCommunitySettings,
} from "@/lib/community-settings"
import {
  defaultLocale,
  getMessages,
  isValidLocale,
  pickLocalizedValue,
  type Locale,
} from "@/lib/i18n"
import type { CommunityIdea, InquirySubmissionResult } from "@/lib/types"
import { createPost } from "@/lib/api/posts"

export async function getCommunityIdeas(
  locale: Locale,
): Promise<CommunityIdea[]> {
  // Intentionally mock-only: there is no backend endpoint yet for concept cards or fundraising ideas.
  return communityIdeaRecords.map((idea) => ({
    id: idea.id,
    title: pickLocalizedValue(idea.title, locale),
    summary: pickLocalizedValue(idea.summary, locale),
    stage: pickLocalizedValue(idea.stage, locale),
    supportType: pickLocalizedValue(idea.supportType, locale),
    focus: pickLocalizedValue(idea.focus, locale),
    image: idea.image,
    tags: pickLocalizedValue(idea.tags, locale),
  }))
}

export async function submitCommunityIdea(
  idea: Pick<CommunityIdea, "title" | "summary"> & { locale: string },
  token: string,
): Promise<InquirySubmissionResult> {
  const publicSettings = await getPublicSettings().catch(() => null)
  const communitySettings = normalizeCommunitySettings(publicSettings?.community)
  const externalLinkCount = countExternalLinks([idea.title, idea.summary])

  if (externalLinkCount > communitySettings.max_external_links) {
    const locale = isValidLocale(idea.locale) ? idea.locale : defaultLocale
    throw new Error(
      getMessages(locale).community.form.externalLinksMax.replace(
        "{max}",
        String(communitySettings.max_external_links),
      ),
    )
  }

  const post = await createPost(
    { title: idea.title, content: idea.summary, excerpt: idea.summary },
    token,
  )

  return {
    success: true,
    id: post.id,
    reference: `POST-${post.id}`,
    status: post.status ?? "pending",
  }
}
