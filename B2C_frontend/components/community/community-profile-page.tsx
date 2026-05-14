"use client"

import { useEffect, useMemo, useState } from "react"
import Link from "next/link"

import { CommunityUserAvatar } from "@/components/community/CommunityUserAvatar"
import { FollowButton } from "@/components/community/FollowButton"
import { PostCard } from "@/components/community/PostCard"
import { Button } from "@/components/ui/button"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { getAccountCopy } from "@/lib/account-copy"
import { getErrorMessage } from "@/lib/api/client"
import {
  getUserComments,
  getUserFavorites,
  getUserPosts,
  getUserProfile,
} from "@/lib/api/users"
import { COMMUNITY_POSTS_REFRESH_EVENT } from "@/lib/community-events"
import { getLocalizedHref, type Locale, type SiteMessages } from "@/lib/i18n"
import type {
  ApiPaginationMeta,
  CommunityComment,
  CommunityPost,
  PaginatedResult,
  UserProfile,
} from "@/lib/types"
import { useAuthSession } from "@/hooks/use-auth-session"

type CommunityProfilePageProps = {
  locale: Locale
  username: string
  messages: SiteMessages["community"]
  initialProfile?: UserProfile | null
}

function formatMonthYear(locale: Locale, value?: string | null) {
  if (!value) {
    return null
  }

  return new Intl.DateTimeFormat(locale === "en" ? "en-US" : locale, {
    month: "long",
    year: "numeric",
  }).format(new Date(value))
}

function formatProfileDate(locale: Locale, value?: string | null) {
  if (!value) {
    return null
  }

  return new Intl.DateTimeFormat(locale === "en" ? "en-US" : locale, {
    year: "numeric",
    month: "short",
    day: "numeric",
  }).format(new Date(value))
}

function getBannerStyle(profile?: UserProfile | null) {
  const seed = profile?.avatar_url ?? profile?.username ?? ""

  if (!seed) {
    return {
      background:
        "linear-gradient(135deg, hsl(145 35% 84%), hsl(150 45% 62%))",
    }
  }

  const hue =
    Array.from(seed).reduce((total, character) => total + character.charCodeAt(0), 0) %
    360

  return {
    background: `linear-gradient(135deg, hsl(${hue} 44% 86%), hsl(${(hue + 28) % 360} 55% 70%))`,
  }
}

const PROFILE_PREVIEW_PER_PAGE = 12

function emptyMeta(): ApiPaginationMeta {
  return {
    current_page: 1,
    per_page: PROFILE_PREVIEW_PER_PAGE,
    total: 0,
    last_page: 1,
  }
}

function emptyResult<T>(): PaginatedResult<T> {
  return {
    items: [],
    meta: emptyMeta(),
  }
}

function appendResult<T extends { id: number | string }>(
  current: PaginatedResult<T>,
  next: PaginatedResult<T>,
): PaginatedResult<T> {
  const existingIds = new Set(current.items.map((item) => item.id))
  const nextItems = next.items.filter((item) => !existingIds.has(item.id))

  return {
    items: [...current.items, ...nextItems],
    meta: next.meta,
  }
}

export function CommunityProfilePage({
  locale,
  username,
  messages,
  initialProfile = null,
}: CommunityProfilePageProps) {
  const session = useAuthSession()
  const accountCopy = getAccountCopy(locale)
  const [activeUsername, setActiveUsername] = useState(
    initialProfile?.username ?? username,
  )
  const [profile, setProfile] = useState<UserProfile | null>(initialProfile)
  const [posts, setPosts] = useState<PaginatedResult<CommunityPost>>(
    emptyResult(),
  )
  const [comments, setComments] = useState<PaginatedResult<CommunityComment>>(
    emptyResult(),
  )
  const [favorites, setFavorites] = useState<PaginatedResult<CommunityPost>>(
    emptyResult(),
  )
  const [message, setMessage] = useState<string | null>(null)
  const [activeTab, setActiveTab] = useState("posts")
  const [isLoadingProfile, setIsLoadingProfile] = useState(!initialProfile)
  const [isLoadingPosts, setIsLoadingPosts] = useState(true)
  const [isLoadingComments, setIsLoadingComments] = useState(true)
  const [isLoadingFavorites, setIsLoadingFavorites] = useState(true)
  const [loadingMoreTab, setLoadingMoreTab] = useState<
    Partial<Record<"posts" | "comments" | "favorites", boolean>>
  >({})

  const [reloadKey, setReloadKey] = useState(0)

  useEffect(() => {
    setActiveUsername(initialProfile?.username ?? username)
    setProfile(initialProfile)
    setPosts(emptyResult())
    setComments(emptyResult())
    setFavorites(emptyResult())
    setActiveTab("posts")
  }, [initialProfile, username])

  useEffect(() => {
    if (!session.isReady) {
      return
    }

    const token = session.token
    const currentUsername = activeUsername
    let cancelled = false

    setMessage(null)
    setIsLoadingProfile(true)
    setIsLoadingPosts(true)
    setIsLoadingComments(true)
    setIsLoadingFavorites(true)
    setPosts(emptyResult())
    setComments(emptyResult())
    setFavorites(emptyResult())
    setLoadingMoreTab({})

    void getUserProfile(currentUsername, token)
      .then((nextProfile) => {
        if (!cancelled) setProfile(nextProfile)
      })
      .catch((loadError) => {
        if (!cancelled) {
          setProfile(null)
          setMessage(getErrorMessage(loadError))
        }
      })
      .finally(() => {
        if (!cancelled) setIsLoadingProfile(false)
      })

    void getUserPosts(
      currentUsername,
      { page: 1, per_page: PROFILE_PREVIEW_PER_PAGE },
      token,
    )
      .then((nextPosts) => {
        if (!cancelled) {
          setPosts(nextPosts)
        }
      })
      .catch((loadError) => {
        if (!cancelled) {
          setPosts(emptyResult())
          setMessage(getErrorMessage(loadError))
        }
      })
      .finally(() => {
        if (!cancelled) setIsLoadingPosts(false)
      })

    void getUserComments(
      currentUsername,
      { page: 1, per_page: PROFILE_PREVIEW_PER_PAGE },
      token,
    )
      .then((nextComments) => {
        if (!cancelled) {
          setComments(nextComments)
        }
      })
      .catch((loadError) => {
        if (!cancelled) {
          setComments(emptyResult())
          setMessage(getErrorMessage(loadError))
        }
      })
      .finally(() => {
        if (!cancelled) setIsLoadingComments(false)
      })

    void getUserFavorites(
      currentUsername,
      { page: 1, per_page: PROFILE_PREVIEW_PER_PAGE },
      token,
    )
      .then((nextFavorites) => {
        if (!cancelled) setFavorites(nextFavorites)
      })
      .catch((loadError) => {
        if (!cancelled) {
          setFavorites(emptyResult())
          setMessage(getErrorMessage(loadError))
        }
      })
      .finally(() => {
        if (!cancelled) setIsLoadingFavorites(false)
      })

    return () => {
      cancelled = true
    }
  }, [activeUsername, session.isReady, session.token, reloadKey])

  useEffect(() => {
    function handleRefresh() {
      setReloadKey((k) => k + 1)
    }

    window.addEventListener(COMMUNITY_POSTS_REFRESH_EVENT, handleRefresh)

    return () => {
      window.removeEventListener(COMMUNITY_POSTS_REFRESH_EVENT, handleRefresh)
    }
  }, [])

  function syncPost(updatedPost: CommunityPost) {
    setPosts((currentPosts) => ({
      ...currentPosts,
      items: currentPosts.items.map((currentPost) =>
        currentPost.id === updatedPost.id ? updatedPost : currentPost,
      ),
    }))
    setFavorites((currentPosts) => ({
      ...currentPosts,
      items: currentPosts.items.map((currentPost) =>
        currentPost.id === updatedPost.id ? updatedPost : currentPost,
      ),
    }))
  }

  function removePost(postId: number) {
    setPosts((currentPosts) => ({
      items: currentPosts.items.filter((currentPost) => currentPost.id !== postId),
      meta: {
        ...currentPosts.meta,
        total: Math.max(0, currentPosts.meta.total - 1),
      },
    }))
    setFavorites((currentPosts) => {
      const hadPost = currentPosts.items.some((currentPost) => currentPost.id === postId)

      return {
        items: currentPosts.items.filter((currentPost) => currentPost.id !== postId),
        meta: {
          ...currentPosts.meta,
          total: hadPost
            ? Math.max(0, currentPosts.meta.total - 1)
            : currentPosts.meta.total,
        },
      }
    })
    setProfile((currentProfile) =>
      currentProfile
        ? {
            ...currentProfile,
            posts_count: Math.max(0, (currentProfile.posts_count ?? 0) - 1),
          }
        : currentProfile,
    )
  }

  const isOwnProfile =
    session.isReady && Boolean(profile && session.user?.id === profile.id)
  const memberSince = formatMonthYear(locale, profile?.joined_at ?? profile?.created_at)
  const visiblePostsCount = isLoadingPosts
    ? (profile?.posts_count ?? 0)
    : posts.meta.total
  const visibleCommentsCount = isLoadingComments
    ? (profile?.comments_count ?? 0)
    : comments.meta.total
  const visibleFavoritesCount = isLoadingFavorites
    ? (profile?.favorites_count ?? 0)
    : favorites.meta.total

  const publicDetails = useMemo(
    () =>
      [
        {
          label: accountCopy.profile.locationLabel,
          value: profile?.profile?.location,
        },
        {
          label: accountCopy.profile.regionLabel,
          value: profile?.profile?.region,
        },
        {
          label: accountCopy.profile.organizationLabel,
          value: profile?.profile?.school_or_company,
        },
      ].filter(
        (item): item is { label: string; value: string } => Boolean(item.value),
      ),
    [
      accountCopy.profile.locationLabel,
      accountCopy.profile.organizationLabel,
      accountCopy.profile.regionLabel,
      profile?.profile?.location,
      profile?.profile?.region,
      profile?.profile?.school_or_company,
    ],
  )

  function showingCount(result: PaginatedResult<unknown>) {
    return accountCopy.community.showingCount
      .replace("{shown}", String(result.items.length))
      .replace("{total}", String(result.meta.total))
  }

  async function loadMorePosts() {
    if (loadingMoreTab.posts || posts.items.length >= posts.meta.total) {
      return
    }

    setLoadingMoreTab((current) => ({ ...current, posts: true }))
    setMessage(null)

    try {
      const nextPosts = await getUserPosts(
        activeUsername,
        {
          page: posts.meta.current_page + 1,
          per_page: PROFILE_PREVIEW_PER_PAGE,
        },
        session.token,
      )
      setPosts((currentPosts) => appendResult(currentPosts, nextPosts))
    } catch (loadError) {
      setMessage(getErrorMessage(loadError))
    } finally {
      setLoadingMoreTab((current) => ({ ...current, posts: false }))
    }
  }

  async function loadMoreComments() {
    if (
      loadingMoreTab.comments ||
      comments.items.length >= comments.meta.total
    ) {
      return
    }

    setLoadingMoreTab((current) => ({ ...current, comments: true }))
    setMessage(null)

    try {
      const nextComments = await getUserComments(
        activeUsername,
        {
          page: comments.meta.current_page + 1,
          per_page: PROFILE_PREVIEW_PER_PAGE,
        },
        session.token,
      )
      setComments((currentComments) => appendResult(currentComments, nextComments))
    } catch (loadError) {
      setMessage(getErrorMessage(loadError))
    } finally {
      setLoadingMoreTab((current) => ({ ...current, comments: false }))
    }
  }

  async function loadMoreFavorites() {
    if (
      loadingMoreTab.favorites ||
      favorites.items.length >= favorites.meta.total
    ) {
      return
    }

    setLoadingMoreTab((current) => ({ ...current, favorites: true }))
    setMessage(null)

    try {
      const nextFavorites = await getUserFavorites(
        activeUsername,
        {
          page: favorites.meta.current_page + 1,
          per_page: PROFILE_PREVIEW_PER_PAGE,
        },
        session.token,
      )
      setFavorites((currentFavorites) =>
        appendResult(currentFavorites, nextFavorites),
      )
    } catch (loadError) {
      setMessage(getErrorMessage(loadError))
    } finally {
      setLoadingMoreTab((current) => ({ ...current, favorites: false }))
    }
  }

  function renderLoadMoreFooter(
    result: PaginatedResult<unknown>,
    isLoadingMore: boolean | undefined,
    onLoadMore: () => void,
  ) {
    if (result.items.length === 0) {
      return null
    }

    const hasMore = result.items.length < result.meta.total

    return (
      <div className="mt-6 flex flex-wrap items-center justify-between gap-3 border-t border-border/60 pt-5">
        <p className="text-sm text-muted-foreground">
          {hasMore ? showingCount(result) : accountCopy.community.noMoreItems}
        </p>
        {hasMore ? (
          <Button
            type="button"
            variant="outline"
            disabled={isLoadingMore}
            onClick={onLoadMore}
          >
            {isLoadingMore
              ? accountCopy.community.loadingMore
              : accountCopy.community.loadMore}
          </Button>
        ) : null}
      </div>
    )
  }

  return (
    <section className="bg-background py-14 lg:py-16">
      <div className="mx-auto max-w-7xl px-6 lg:px-8">
        {message ? (
          <div className="mb-8 rounded-2xl border border-border/60 bg-card px-5 py-4 text-sm text-foreground">
            {message}
          </div>
        ) : null}

        {isLoadingProfile && !profile ? (
          <div className="rounded-[2rem] border border-border/60 bg-card p-8 text-muted-foreground">
            {messages.profile.loading}
          </div>
        ) : null}

        {!isLoadingProfile && !profile ? (
          <div className="rounded-[2rem] border border-border/60 bg-card p-8">
            <h1 className="font-serif text-3xl text-foreground">
              {messages.profile.notFound}
            </h1>
          </div>
        ) : null}

        {profile ? (
          <>
            <article className="overflow-hidden rounded-[2rem] border border-border/60 bg-card">
              <div
                className="h-44 w-full border-b border-border/40"
                style={getBannerStyle(profile)}
              />

              <div className="relative px-6 pb-8 sm:px-8">
                <div className="-mt-12 flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                  <div className="flex flex-col gap-5 sm:flex-row sm:items-end">
                    <CommunityUserAvatar
                      user={profile}
                      className="size-24 border-4 border-background shadow-lg"
                      fallbackClassName="text-xl"
                      sizes="96px"
                    />

                    <div className="space-y-3">
                      <div className="space-y-2">
                        <h1 className="font-serif text-4xl text-foreground">
                          {profile.name}
                        </h1>
                      </div>

                      {profile.bio ? (
                        <p className="max-w-2xl leading-relaxed text-muted-foreground">
                          {profile.bio}
                        </p>
                      ) : null}

                      <div className="flex flex-wrap gap-x-5 gap-y-2 text-sm text-muted-foreground">
                        {memberSince ? (
                          <span>
                            {messages.profile.memberSince.replace(
                              "{date}",
                              memberSince,
                            )}
                          </span>
                        ) : null}
                      </div>
                    </div>
                  </div>

                  {session.isReady ? (
                    isOwnProfile ? (
                      <Button asChild variant="outline">
                        <Link href={getLocalizedHref(locale, "account")}>
                          {accountCopy.publicProfile.manageAccount}
                        </Link>
                      </Button>
                    ) : (
                      <FollowButton
                        userId={profile.id}
                        initialIsFollowing={profile.is_following}
                        followerCount={profile.followers_count ?? 0}
                        userName={profile.name}
                        onChange={({ isFollowing, followerCount }) => {
                          setProfile((currentProfile) =>
                            currentProfile
                              ? {
                                  ...currentProfile,
                                  is_following: isFollowing,
                                  followers_count: followerCount,
                                }
                              : currentProfile,
                          )
                        }}
                      />
                    )
                  ) : null}
                </div>

                <div className="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
                  <div className="rounded-[1.5rem] bg-background p-5">
                    <p className="text-sm text-muted-foreground">
                      {messages.profile.postsCount}
                    </p>
                    <p className="mt-2 text-2xl text-foreground">
                      {visiblePostsCount}
                    </p>
                  </div>
                  <div className="rounded-[1.5rem] bg-background p-5">
                    <p className="text-sm text-muted-foreground">
                      {messages.profile.commentsCount}
                    </p>
                    <p className="mt-2 text-2xl text-foreground">
                      {visibleCommentsCount}
                    </p>
                  </div>
                  <div className="rounded-[1.5rem] bg-background p-5">
                    <p className="text-sm text-muted-foreground">
                      {messages.profile.favorites}
                    </p>
                    <p className="mt-2 text-2xl text-foreground">
                      {visibleFavoritesCount}
                    </p>
                  </div>
                  <div className="rounded-[1.5rem] bg-background p-5">
                    <p className="text-sm text-muted-foreground">
                      {messages.profile.followers}
                    </p>
                    <p className="mt-2 text-2xl text-foreground">
                      {profile.followers_count ?? 0}
                    </p>
                  </div>
                  <div className="rounded-[1.5rem] bg-background p-5">
                    <p className="text-sm text-muted-foreground">
                      {messages.profile.following}
                    </p>
                    <p className="mt-2 text-2xl text-foreground">
                      {profile.following_count ?? 0}
                    </p>
                  </div>
                </div>

                <div className="mt-8 rounded-[1.5rem] bg-background p-6">
                  <div className="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div>
                      <p className="text-sm uppercase tracking-[0.18em] text-primary">
                        {accountCopy.publicProfile.detailsTitle}
                      </p>
                      <h2 className="mt-3 font-serif text-3xl text-foreground">
                        {accountCopy.publicProfile.detailsTitle}
                      </h2>
                      <p className="mt-3 max-w-2xl text-sm leading-relaxed text-muted-foreground">
                        {accountCopy.publicProfile.detailsDescription}
                      </p>
                    </div>
                    {isOwnProfile ? (
                      <Button asChild variant="outline">
                        <Link href={getLocalizedHref(locale, "account/profile")}>
                          {accountCopy.publicProfile.completeProfile}
                        </Link>
                      </Button>
                    ) : null}
                  </div>

                  {publicDetails.length > 0 ||
                  profile.profile?.website ||
                  profile.profile?.portfolio_url ||
                  profile.profile?.open_to_collab ? (
                    <div className="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                      {publicDetails.map((detail) => (
                        <div
                          key={detail.label}
                          className="rounded-[1.25rem] border border-border/60 bg-card p-4"
                        >
                          <p className="text-sm text-muted-foreground">{detail.label}</p>
                          <p className="mt-2 text-foreground">{detail.value}</p>
                        </div>
                      ))}
                      {profile.profile?.website ? (
                        <div className="rounded-[1.25rem] border border-border/60 bg-card p-4">
                          <p className="text-sm text-muted-foreground">
                            {accountCopy.profile.websiteLabel}
                          </p>
                          <Link
                            href={profile.profile.website}
                            target="_blank"
                            rel="noreferrer"
                            className="mt-2 block break-all text-foreground underline-offset-4 transition-colors hover:text-primary hover:underline"
                          >
                            {profile.profile.website}
                          </Link>
                        </div>
                      ) : null}
                      {profile.profile?.portfolio_url ? (
                        <div className="rounded-[1.25rem] border border-border/60 bg-card p-4">
                          <p className="text-sm text-muted-foreground">
                            {accountCopy.profile.portfolioLabel}
                          </p>
                          <Link
                            href={profile.profile.portfolio_url}
                            target="_blank"
                            rel="noreferrer"
                            className="mt-2 block break-all text-foreground underline-offset-4 transition-colors hover:text-primary hover:underline"
                          >
                            {profile.profile.portfolio_url}
                          </Link>
                        </div>
                      ) : null}
                      {profile.profile?.open_to_collab ? (
                        <div className="rounded-[1.25rem] border border-border/60 bg-card p-4">
                          <p className="text-sm text-muted-foreground">
                            {accountCopy.profile.collaborationLabel}
                          </p>
                          <p className="mt-2 text-foreground">
                            {accountCopy.profile.collaborationLabel}
                          </p>
                        </div>
                      ) : null}
                    </div>
                  ) : (
                    <div className="mt-6 rounded-[1.5rem] border border-dashed border-border/60 bg-card p-6 text-center">
                      <p className="text-sm text-muted-foreground">
                        {accountCopy.publicProfile.noDetails}
                      </p>
                    </div>
                  )}
                </div>
              </div>
            </article>

            <Tabs value={activeTab} onValueChange={setActiveTab} className="mt-10">
              <TabsList>
                <TabsTrigger value="posts">{messages.profile.posts}</TabsTrigger>
                <TabsTrigger value="comments">{messages.profile.comments}</TabsTrigger>
                <TabsTrigger value="favorites">
                  {messages.profile.favorites}
                </TabsTrigger>
              </TabsList>

              <TabsContent value="posts" className="mt-6">
                {isLoadingPosts ? (
                  <div className="rounded-[2rem] border border-border/60 bg-card p-8 text-muted-foreground">
                    {messages.profile.loadingPosts}
                  </div>
                ) : (
                  <>
                    <p className="mb-4 text-sm text-muted-foreground">
                      {showingCount(posts)}
                    </p>
                    {posts.items.length > 0 ? (
                      <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        {posts.items.map((post) => (
                          <PostCard
                            key={post.id}
                            locale={locale}
                            post={post}
                            messages={messages}
                            token={session.token}
                            currentUserId={session.user?.id}
                            onUpdated={syncPost}
                            onDeleted={removePost}
                          />
                        ))}
                      </div>
                    ) : (
                      <div className="rounded-[2rem] border border-border/60 bg-card p-8 text-muted-foreground">
                        {messages.profile.noPosts}
                      </div>
                    )}
                    {renderLoadMoreFooter(
                      posts,
                      loadingMoreTab.posts,
                      () => void loadMorePosts(),
                    )}
                  </>
                )}
              </TabsContent>

              <TabsContent value="comments" className="mt-6">
                {isLoadingComments ? (
                  <div className="rounded-[2rem] border border-border/60 bg-card p-8 text-muted-foreground">
                    {messages.profile.loadingComments}
                  </div>
                ) : (
                  <>
                    <p className="mb-4 text-sm text-muted-foreground">
                      {showingCount(comments)}
                    </p>
                    {comments.items.length > 0 ? (
                      <div className="space-y-4">
                        {comments.items.map((comment) => {
                          const commentHref = comment.post?.slug
                            ? getLocalizedHref(
                                locale,
                                `community/${comment.post.slug}#comment-${comment.id}`,
                              )
                            : null

                          return (
                            <article
                              key={comment.id}
                              className="rounded-[1.75rem] border border-border/60 bg-card p-6"
                            >
                              <div className="flex flex-wrap items-start justify-between gap-4">
                                <div className="space-y-2">
                                  {commentHref ? (
                                    <Link
                                      href={commentHref}
                                      className="font-medium text-foreground transition-colors hover:text-primary"
                                    >
                                      {comment.post?.title}
                                    </Link>
                                  ) : (
                                    <p className="font-medium text-foreground">
                                      {messages.profile.commentWithoutPost}
                                    </p>
                                  )}
                                  <div className="flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
                                    <span>
                                      {formatProfileDate(locale, comment.created_at)}
                                    </span>
                                    <span>
                                      {messages.post.likesLabel.replace(
                                        "{count}",
                                        String(comment.likes_count),
                                      )}
                                    </span>
                                  </div>
                                </div>
                                {commentHref ? (
                                  <Button asChild variant="ghost" size="sm">
                                    <Link href={commentHref}>
                                      {messages.profile.openComment}
                                    </Link>
                                  </Button>
                                ) : null}
                              </div>
                              <p className="mt-4 whitespace-pre-wrap leading-relaxed text-foreground">
                                {comment.content}
                              </p>
                            </article>
                          )
                        })}
                      </div>
                    ) : (
                      <div className="rounded-[2rem] border border-border/60 bg-card p-8 text-muted-foreground">
                        {messages.profile.noComments}
                      </div>
                    )}
                    {renderLoadMoreFooter(
                      comments,
                      loadingMoreTab.comments,
                      () => void loadMoreComments(),
                    )}
                  </>
                )}
              </TabsContent>

              <TabsContent value="favorites" className="mt-6">
                {isLoadingFavorites ? (
                  <div className="rounded-[2rem] border border-border/60 bg-card p-8 text-muted-foreground">
                    {messages.profile.loadingFavorites}
                  </div>
                ) : (
                  <>
                    <p className="mb-4 text-sm text-muted-foreground">
                      {showingCount(favorites)}
                    </p>
                    {favorites.items.length > 0 ? (
                      <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                        {favorites.items.map((post) => (
                          <PostCard
                            key={post.id}
                            locale={locale}
                            post={post}
                            messages={messages}
                            token={session.token}
                            currentUserId={session.user?.id}
                            onUpdated={syncPost}
                            onDeleted={removePost}
                          />
                        ))}
                      </div>
                    ) : (
                      <div className="rounded-[2rem] border border-border/60 bg-card p-8 text-muted-foreground">
                        {messages.profile.noFavorites}
                      </div>
                    )}
                    {renderLoadMoreFooter(
                      favorites,
                      loadingMoreTab.favorites,
                      () => void loadMoreFavorites(),
                    )}
                  </>
                )}
              </TabsContent>
            </Tabs>
          </>
        ) : null}
      </div>
    </section>
  )
}
