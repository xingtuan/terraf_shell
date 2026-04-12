import { communityIdeaRecords } from "@/lib/data/community"
import { pickLocalizedValue, type Locale } from "@/lib/i18n"
import type { CommunityIdea, InquirySubmissionResult } from "@/lib/types"

export async function getCommunityIdeas(
  locale: Locale,
): Promise<CommunityIdea[]> {
  // TODO: Replace with a backend community endpoint for concepts, status, and supporter counts.
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
): Promise<InquirySubmissionResult> {
  // TODO: Replace this mock submission with a POST request to the community idea backend.
  await new Promise((resolve) => setTimeout(resolve, 300))

  const timestamp = Date.now().toString().slice(-6)
  const reference = `COM-${timestamp}`

  console.info("Mock community idea captured", {
    reference,
    idea,
  })

  return {
    success: true,
    id: Number(timestamp),
    reference,
    status: "mock",
  }
}
