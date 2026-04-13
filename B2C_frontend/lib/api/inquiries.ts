import { submitInquiryLead } from "@/lib/api/leads"
import type { B2BInquiry, InquirySubmissionResult } from "@/lib/types"

export async function submitB2BInquiry(
  inquiry: B2BInquiry,
): Promise<InquirySubmissionResult> {
  return submitInquiryLead(inquiry)
}
