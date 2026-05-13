import { requestApi } from "@/lib/api/client"
import type { ReportRecord } from "@/lib/types"

export type ReportTargetType = "post" | "comment"

export type CreateReportPayload = {
  target_type: ReportTargetType
  target_id: number
  reason: string
  description?: string
}

export async function createReport(
  payload: CreateReportPayload,
  token: string,
) {
  const response = await requestApi<ReportRecord>("/reports", {
    method: "POST",
    token,
    body: payload,
  })

  return response.data
}
