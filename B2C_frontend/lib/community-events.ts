export const COMMUNITY_POSTS_REFRESH_EVENT = "community:posts-refresh"
export const COMMUNITY_AUTH_OPEN_EVENT = "community:auth-open"

export function dispatchCommunityPostsRefresh() {
  if (typeof window === "undefined") {
    return
  }

  window.dispatchEvent(new CustomEvent(COMMUNITY_POSTS_REFRESH_EVENT))
}

export function dispatchCommunityAuthOpen() {
  if (typeof window === "undefined") {
    return
  }

  window.dispatchEvent(new CustomEvent(COMMUNITY_AUTH_OPEN_EVENT))
}
