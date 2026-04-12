import { requestApi } from "@/lib/api/client"
import type { B2BInquiry, InquirySubmissionResult } from "@/lib/types"

type InquiryApiResponse = {
  id: number
  reference: string
  status: string
}

export async function submitB2BInquiry(
  inquiry: B2BInquiry,
): Promise<InquirySubmissionResult> {
  const composedMessage = [
    inquiry.message.trim(),
    inquiry.volume.trim() ? `Estimated volume: ${inquiry.volume.trim()}` : null,
    inquiry.timeline?.trim()
      ? `Target timeline: ${inquiry.timeline.trim()}`
      : null,
  ]
    .filter(Boolean)
    .join("\n\n")

  const response = await requestApi<InquiryApiResponse>("/inquiries", {
    method: "POST",
    body: {
      name: inquiry.name,
      company_name: inquiry.company,
      email: inquiry.email,
      phone: inquiry.phone || null,
      country: inquiry.country || null,
      inquiry_type: inquiry.application,
      message: composedMessage,
      source_page: `${inquiry.sourcePage}:${inquiry.locale}`,
    },
  })

  return {
    success: true,
    id: response.data.id,
    reference: response.data.reference,
    status: response.data.status,
  }
}
