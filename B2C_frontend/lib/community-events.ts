export const COMMUNITY_POSTS_REFRESH_EVENT = "community:posts-refresh"

export function dispatchCommunityPostsRefresh() {
  if (typeof window === "undefined") {
    return
  }

  window.dispatchEvent(new CustomEvent(COMMUNITY_POSTS_REFRESH_EVENT))
}
