import { requestApi } from "@/lib/api/client"
import type { PostFavoritePayload, PostLikePayload } from "@/lib/types"

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
