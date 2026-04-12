import { requestApi } from "@/lib/api/client"
import type { CommunityComment } from "@/lib/types"

export async function listComments(postId: number, token?: string | null) {
  const response = await requestApi<CommunityComment[]>(
    `/posts/${postId}/comments`,
    {
      token,
    },
  )

  return response.data
}

export async function createComment(
  postId: number,
  content: string,
  token: string,
) {
  const response = await requestApi<CommunityComment>(`/posts/${postId}/comments`, {
    method: "POST",
    token,
    body: { content },
  })

  return response.data
}
