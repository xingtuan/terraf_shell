import { requestApi } from "@/lib/api/client"
import {
  createReport,
  type CreateReportPayload,
} from "@/lib/api/reports"
import type {
  CommentLikePayload,
  PostFavoritePayload,
  PostLikePayload,
} from "@/lib/types"

export async function togglePostLike(
  postId: number,
  isLiked: boolean,
  token: string,
) {
  const response = await requestApi<PostLikePayload>(`/posts/${postId}/like`, {
    method: isLiked ? "DELETE" : "POST",
    token,
  })

  return response.data
}

export async function toggleCommentLike(
  commentId: number,
  isLiked: boolean,
  token: string,
) {
  const response = await requestApi<CommentLikePayload>(
    `/comments/${commentId}/like`,
    {
      method: isLiked ? "DELETE" : "POST",
      token,
    },
  )

  return response.data
}

export async function togglePostFavorite(
  postId: number,
  isFavorited: boolean,
  token: string,
) {
  const response = await requestApi<PostFavoritePayload>(
    `/posts/${postId}/favorite`,
    {
      method: isFavorited ? "DELETE" : "POST",
      token,
    },
  )

  return response.data
}

export type SubmitReportPayload = CreateReportPayload

export async function submitReport(payload: SubmitReportPayload, token: string) {
  return createReport(payload, token)
}
