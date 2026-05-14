import { requestApi } from "@/lib/api/client"
import { ensureArray, normalizePaginationMeta } from "@/lib/api/normalizers"
import type { PaginatedResult, ReportRecord } from "@/lib/types"

export type ReportTargetType = "post" | "comment"

export type CreateReportPayload = {
  target_type: ReportTargetType
  target_id: number
  reason: string
  description?: string
}

export type ReportParams = {
  page?: number
  per_page?: number
}

function normalizeReport(report: ReportRecord): ReportRecord {
  return {
    ...report,
    moderator_note: report.moderator_note ?? null,
    public_note: report.public_note ?? null,
    resolution_action: report.resolution_action ?? null,
    resolution_action_label: report.resolution_action_label ?? null,
    reviewed_at: report.reviewed_at ?? null,
    resolved_at: report.resolved_at ?? null,
    dismissed_at: report.dismissed_at ?? null,
    completed_at: report.completed_at ?? null,
    created_at: report.created_at ?? null,
    updated_at: report.updated_at ?? null,
  }
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

  return normalizeReport(response.data)
}

export async function listMyReports(
  token: string,
  params: ReportParams = {},
): Promise<PaginatedResult<ReportRecord>> {
  const response = await requestApi<ReportRecord[]>("/reports", {
    token,
    query: params,
  })

  const items = ensureArray(response.data).map(normalizeReport)

  return {
    items,
    meta: normalizePaginationMeta(response.meta, items.length),
  }
}

export async function getMyReport(reportId: number, token: string) {
  const response = await requestApi<ReportRecord>(`/reports/${reportId}`, {
    token,
  })

  return normalizeReport(response.data)
}
