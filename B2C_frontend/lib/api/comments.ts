import { normalizeCommunityComment } from "@/lib/api/adapters"
import { requestApi } from "@/lib/api/client"
import { ensureArray } from "@/lib/api/normalizers"
import type { CommunityComment } from "@/lib/types"

type CommentPayload = {
  body: string
  parent_id?: number
}

type LegacyCommentPayload = {
  content: string
}

export async function listComments(postId: number, token?: string | null) {
  const response = await requestApi<CommunityComment[]>(
    `/posts/${postId}/comments`,
    {
      token,
    },
  )

  return ensureArray(response.data).map(normalizeCommunityComment)
}

export async function createComment(
  postId: number,
  body: string,
  token: string,
  parentId?: number,
) {
  const response = await requestApi<CommunityComment>(`/posts/${postId}/comments`, {
    method: "POST",
    token,
    body: {
      body,
      ...(parentId !== undefined ? { parent_id: parentId } : {}),
    } satisfies CommentPayload,
  })

  return normalizeCommunityComment(response.data)
}

export async function replyToComment(
  commentId: number,
  content: string,
  token: string,
) {
  const response = await requestApi<CommunityComment>(
    `/comments/${commentId}/reply`,
    {
      method: "POST",
      token,
      body: { content } satisfies LegacyCommentPayload,
    },
  )

  return normalizeCommunityComment(response.data)
}

export async function updateComment(
  commentId: number,
  content: string,
  token: string,
) {
  const response = await requestApi<CommunityComment>(`/comments/${commentId}`, {
    method: "PUT",
    token,
    body: { content } satisfies CommentPayload,
  })

  return normalizeCommunityComment(response.data)
}

export async function deleteComment(commentId: number, token: string) {
  await requestApi<null>(`/comments/${commentId}`, {
    method: "DELETE",
    token,
  })
}
