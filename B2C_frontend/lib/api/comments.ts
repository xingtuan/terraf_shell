import { normalizeCommunityComment } from "@/lib/api/adapters"
import { requestApi } from "@/lib/api/client"
import { ensureArray } from "@/lib/api/normalizers"
import type { CommunityComment } from "@/lib/types"

type CommentPayload = {
  content: string
}

type EditCommentPayload = {
  body: string
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
  content: string,
  token: string,
) {
  const response = await requestApi<CommunityComment>(`/posts/${postId}/comments`, {
    method: "POST",
    token,
    body: { content } satisfies CommentPayload,
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
      body: { content } satisfies CommentPayload,
    },
  )

  return normalizeCommunityComment(response.data)
}

export async function editComment(
  commentId: number,
  body: string,
  token: string,
) {
  const response = await requestApi<CommunityComment>(`/comments/${commentId}`, {
    method: "PUT",
    token,
    body: { body } satisfies EditCommentPayload,
  })

  return normalizeCommunityComment(response.data)
}

export async function updateComment(
  commentId: number,
  content: string,
  token: string,
) {
  return editComment(commentId, content, token)
}

export async function deleteComment(commentId: number, token: string) {
  await requestApi<null>(`/comments/${commentId}`, {
    method: "DELETE",
    token,
  })
}
