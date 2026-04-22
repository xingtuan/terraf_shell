import type { Locale } from "@/lib/i18n"

export type CommunityCopy = {
  pageIntro: {
    eyebrow: string
    title: string
    description: string
    primaryCta: string
    secondaryCta: string
  }
  detailIntro: {
    eyebrow: string
    title: string
    description: string
    primaryCta: string
    secondaryCta: string
  }
  hub: {
    eyebrow: string
    title: string
    description: string
    latestSort: string
    hotSort: string
    refresh: string
    loading: string
    emptyTitle: string
    emptyDescription: string
    totalLabel: string
    readMore: string
    loginHint: string
    backendStatusTitle: string
    backendStatusDescription: string
  }
  auth: {
    title: string
    description: string
    loginTab: string
    registerTab: string
    loadingSession: string
    currentUser: string
    signedInAs: string
    logout: string
    refreshProfile: string
    email: string
    password: string
    name: string
    username: string
    confirmPassword: string
    loginSubmit: string
    registerSubmit: string
    guestHint: string
  }
  actions: {
    likes: string
    favorites: string
    comments: string
    like: string
    unlike: string
    favorite: string
    unfavorite: string
    signInToInteract: string
  }
  detail: {
    authorLabel: string
    categoryLabel: string
    publishedLabel: string
    statusLabel: string
    tagsLabel: string
    commentsTitle: string
    commentsDescription: string
    commentPlaceholder: string
    submitComment: string
    commentPending: string
    commentPosted: string
    loginToComment: string
    loadingPost: string
    loadingComments: string
    backToFeed: string
    noCommentsTitle: string
    noCommentsDescription: string
    repliesLabel: string
    pendingBadge: string
    rejectedBadge: string
  }
}

const englishCopy: CommunityCopy = {
  pageIntro: {
    eyebrow: "Live Community",
    title: "Backend-connected posts, auth, comments, likes, and favorites.",
    description:
      "The community section now uses the Laravel API instead of local mock cards. Sign in, open a post, and test the full discussion flow.",
    primaryCta: "Browse Posts",
    secondaryCta: "Sign In to Participate",
  },
  detailIntro: {
    eyebrow: "Community Post",
    title: "Read the full post and join the discussion.",
    description:
      "This page loads the post, current viewer state, comments, and interaction actions from the backend API.",
    primaryCta: "Back to Community",
    secondaryCta: "Jump to Comments",
  },
  hub: {
    eyebrow: "Posts Feed",
    title: "Latest discussions from the backend community API.",
    description:
      "Posts are now fetched from `/api/posts`, and authenticated viewers can like, favorite, and comment from the frontend.",
    latestSort: "Latest",
    hotSort: "Hot",
    refresh: "Refresh",
    loading: "Loading posts...",
    emptyTitle: "No posts available yet",
    emptyDescription:
      "The frontend is connected, but the backend has no visible approved posts for this feed.",
    totalLabel: "Visible posts",
    readMore: "Read post",
    loginHint: "Sign in to unlock likes, favorites, and comment submission.",
    backendStatusTitle: "Connected endpoints",
    backendStatusDescription:
      "Auth, posts, post detail, comments, likes, and favorites are now wired through a shared API layer.",
  },
  auth: {
    title: "Community Access",
    description:
      "Use the backend auth endpoints here. The saved bearer token stays in local storage for the current browser.",
    loginTab: "Login",
    registerTab: "Register",
    loadingSession: "Checking saved session...",
    currentUser: "Current user",
    signedInAs: "Signed in as",
    logout: "Logout",
    refreshProfile: "Refresh profile",
    email: "Email",
    password: "Password",
    name: "Name",
    username: "Username",
    confirmPassword: "Confirm password",
    loginSubmit: "Login",
    registerSubmit: "Create account",
    guestHint: "Public posts are readable without signing in.",
  },
  actions: {
    likes: "Likes",
    favorites: "Favorites",
    comments: "Comments",
    like: "Like",
    unlike: "Unlike",
    favorite: "Save",
    unfavorite: "Saved",
    signInToInteract: "Sign in first to like, save, or comment.",
  },
  detail: {
    authorLabel: "Author",
    categoryLabel: "Category",
    publishedLabel: "Published",
    statusLabel: "Status",
    tagsLabel: "Tags",
    commentsTitle: "Comments",
    commentsDescription:
      "The list below is fetched from the backend and includes nested replies when available.",
    commentPlaceholder: "Write a comment for this post...",
    submitComment: "Post comment",
    commentPending: "Comment submitted. It may stay pending until moderated.",
    commentPosted: "Comment posted successfully.",
    loginToComment: "Sign in to submit a comment.",
    loadingPost: "Loading post...",
    loadingComments: "Loading comments...",
    backToFeed: "Back to community",
    noCommentsTitle: "No comments yet",
    noCommentsDescription:
      "This post has no visible comments. Sign in and add the first one.",
    repliesLabel: "Replies",
    pendingBadge: "Pending",
    rejectedBadge: "Rejected",
  },
}

const communityCopies: Record<Locale, CommunityCopy> = {
  en: englishCopy,
  ko: englishCopy,
  zh: englishCopy,
}

export function getCommunityCopy(locale: Locale) {
  return communityCopies[locale]
}
