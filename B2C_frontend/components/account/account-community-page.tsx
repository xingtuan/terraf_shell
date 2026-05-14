"use client"

import type { Dispatch, SetStateAction } from "react"
import { useEffect, useState } from "react"
import Link from "next/link"

import {
  AccountCommunityCommentCard,
  AccountCommunityReportCard,
  AccountCommunityUserRow,
} from "@/components/account/account-community-cards"
import { AccountCommunitySection } from "@/components/account/account-community-section"
import {
  AccountEmptyState,
  AccountPageHeader,
  AccountPanel,
  AccountStatCard,
} from "@/components/account/account-ui"
import { CreatePostPanel } from "@/components/community/CreatePostPanel"
import { PostCard } from "@/components/community/PostCard"
import { Button } from "@/components/ui/button"
import { getAccountCopy } from "@/lib/account-copy"
import { getErrorMessage } from "@/lib/api/client"
import { listMyReports } from "@/lib/api/reports"
import {
  getUserComments,
  getUserFavorites,
  getUserFollowers,
  getUserFollowing,
  getUserPosts,
  getUserProfile,
} from "@/lib/api/users"
import { getLocalizedHref, getMessages, type Locale } from "@/lib/i18n"
import type {
  ApiPaginationMeta,
  CommunityComment,
  CommunityPost,
  CommunityUser,
  PaginatedResult,
  ReportRecord,
  UserProfile,
} from "@/lib/types"
import { useAuthSession } from "@/hooks/use-auth-session"

type AccountCommunityPageProps = {
  locale: Locale
}

type SectionKey =
  | "posts"
  | "favorites"
  | "comments"
  | "followers"
  | "following"
  | "reports"

const PREVIEW_PER_PAGE: Record<SectionKey, number> = {
  posts: 4,
  favorites: 4,
  comments: 4,
  followers: 6,
  following: 6,
  reports: 5,
}

function emptyMeta(perPage: number): ApiPaginationMeta {
  return {
    current_page: 1,
    per_page: perPage,
    total: 0,
    last_page: 1,
  }
}

function emptyResult<T>(perPage: number): PaginatedResult<T> {
  return {
    items: [],
    meta: emptyMeta(perPage),
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

function removeResultItem<T extends { id: number | string }>(
  current: PaginatedResult<T>,
  itemId: number | string,
): PaginatedResult<T> {
  const hadItem = current.items.some((item) => item.id === itemId)

  return {
    items: current.items.filter((item) => item.id !== itemId),
    meta: {
      ...current.meta,
      total: hadItem ? Math.max(0, current.meta.total - 1) : current.meta.total,
    },
  }
}

export function AccountCommunityPage({ locale }: AccountCommunityPageProps) {
  const session = useAuthSession()
  const copy = getAccountCopy(locale)
  const messages = getMessages(locale).community
  const [profile, setProfile] = useState<UserProfile | null>(null)
  const [posts, setPosts] = useState<PaginatedResult<CommunityPost>>(
    emptyResult(PREVIEW_PER_PAGE.posts),
  )
  const [comments, setComments] = useState<PaginatedResult<CommunityComment>>(
    emptyResult(PREVIEW_PER_PAGE.comments),
  )
  const [favorites, setFavorites] = useState<PaginatedResult<CommunityPost>>(
    emptyResult(PREVIEW_PER_PAGE.favorites),
  )
  const [followers, setFollowers] = useState<PaginatedResult<CommunityUser>>(
    emptyResult(PREVIEW_PER_PAGE.followers),
  )
  const [following, setFollowing] = useState<PaginatedResult<CommunityUser>>(
    emptyResult(PREVIEW_PER_PAGE.following),
  )
  const [reports, setReports] = useState<PaginatedResult<ReportRecord>>(
    emptyResult(PREVIEW_PER_PAGE.reports),
  )
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [sectionErrors, setSectionErrors] = useState<
    Partial<Record<SectionKey, string>>
  >({})
  const [loadingMore, setLoadingMore] = useState<
    Partial<Record<SectionKey, boolean>>
  >({})
  const [isCreateOpen, setIsCreateOpen] = useState(false)
  const [reloadKey, setReloadKey] = useState(0)

  useEffect(() => {
    const token = session.token
    const username = session.user?.username

    if (!token || !username) {
      setLoading(false)
      return
    }

    const authToken: string = token
    const userIdentifier: string = username
    let cancelled = false

    async function loadCommunity() {
      setLoading(true)
      setError(null)
      setSectionErrors({})

      try {
        const [
          nextProfile,
          postsResponse,
          commentsResponse,
          favoritesResponse,
          followersResponse,
          followingResponse,
          reportsResponse,
        ] = await Promise.all([
          getUserProfile(userIdentifier, authToken),
          getUserPosts(
            userIdentifier,
            { page: 1, per_page: PREVIEW_PER_PAGE.posts },
            authToken,
          ),
          getUserComments(
            userIdentifier,
            { page: 1, per_page: PREVIEW_PER_PAGE.comments },
            authToken,
          ),
          getUserFavorites(
            userIdentifier,
            { page: 1, per_page: PREVIEW_PER_PAGE.favorites },
            authToken,
          ),
          getUserFollowers(
            userIdentifier,
            { page: 1, per_page: PREVIEW_PER_PAGE.followers },
            authToken,
          ),
          getUserFollowing(
            userIdentifier,
            { page: 1, per_page: PREVIEW_PER_PAGE.following },
            authToken,
          ),
          listMyReports(authToken, {
            page: 1,
            per_page: PREVIEW_PER_PAGE.reports,
          }),
        ])

        if (cancelled) return
        setProfile(nextProfile)
        setPosts(postsResponse)
        setComments(commentsResponse)
        setFavorites(favoritesResponse)
        setFollowers(followersResponse)
        setFollowing(followingResponse)
        setReports(reportsResponse)
      } catch (loadError) {
        if (!cancelled) setError(getErrorMessage(loadError))
      } finally {
        if (!cancelled) setLoading(false)
      }
    }

    void loadCommunity()

    return () => {
      cancelled = true
    }
  }, [session.token, session.user?.username, reloadKey])

  function syncPost(updatedPost: CommunityPost) {
    setPosts((currentPosts) => ({
      ...currentPosts,
      items: currentPosts.items.map((currentPost) =>
        currentPost.id === updatedPost.id ? updatedPost : currentPost,
      ),
    }))
    setFavorites((currentFavorites) => ({
      ...currentFavorites,
      items: currentFavorites.items.map((currentPost) =>
        currentPost.id === updatedPost.id ? updatedPost : currentPost,
      ),
    }))
  }

  function removePost(postId: number) {
    setPosts((currentPosts) => removeResultItem(currentPosts, postId))
    setFavorites((currentFavorites) => removeResultItem(currentFavorites, postId))
    setProfile((currentProfile) =>
      currentProfile
        ? {
            ...currentProfile,
            posts_count: Math.max(0, (currentProfile.posts_count ?? 0) - 1),
          }
        : currentProfile,
    )
  }

  async function loadMore<T extends { id: number | string }>(
    key: SectionKey,
    result: PaginatedResult<T>,
    fetchPage: (page: number) => Promise<PaginatedResult<T>>,
    setResult: Dispatch<SetStateAction<PaginatedResult<T>>>,
  ) {
    if (loadingMore[key] || result.items.length >= result.meta.total) {
      return
    }

    setLoadingMore((current) => ({ ...current, [key]: true }))
    setSectionErrors((current) => ({ ...current, [key]: undefined }))

    try {
      const nextResult = await fetchPage(result.meta.current_page + 1)
      setResult((current) => appendResult(current, nextResult))
    } catch (loadError) {
      setSectionErrors((current) => ({
        ...current,
        [key]: getErrorMessage(loadError),
      }))
    } finally {
      setLoadingMore((current) => ({ ...current, [key]: false }))
    }
  }

  const publicProfileHref = session.user?.username
    ? getLocalizedHref(locale, `community/u/${session.user.username}`)
    : getLocalizedHref(locale, "community")

  const sectionLabels = {
    viewAllLabel: copy.community.viewAll,
    loadMoreLabel: copy.community.loadMore,
    loadingMoreLabel: copy.community.loadingMore,
    showingCountLabel: copy.community.showingCount,
    noMoreItemsLabel: copy.community.noMoreItems,
  }

  const username = session.user?.username
  const token = session.token

  return (
    <>
      <AccountPanel>
        <AccountPageHeader
          eyebrow={copy.community.eyebrow}
          title={copy.community.title}
          description={copy.community.description}
          actions={
            <>
              <Button asChild variant="outline">
                <Link href={publicProfileHref}>
                  {copy.community.viewPublicProfile}
                </Link>
              </Button>
              <Button type="button" onClick={() => setIsCreateOpen(true)}>
                {copy.community.createPost}
              </Button>
            </>
          }
        />

        {error ? (
          <div className="mt-6 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {error}
          </div>
        ) : null}

        {loading ? (
          <div className="mt-8 rounded-[1.5rem] border border-border/60 bg-background/70 p-6 text-sm text-muted-foreground">
            {copy.community.loading}
          </div>
        ) : (
          <>
            <div className="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-6">
              <AccountStatCard
                label={copy.community.postsTotal}
                value={posts.meta.total}
              />
              <AccountStatCard
                label={copy.community.savedPostsTotal}
                value={favorites.meta.total}
              />
              <AccountStatCard
                label={copy.community.commentsTotal}
                value={comments.meta.total}
              />
              <AccountStatCard
                label={copy.community.followersTotal}
                value={followers.meta.total}
              />
              <AccountStatCard
                label={copy.community.followingTotal}
                value={following.meta.total}
              />
              <AccountStatCard
                label={copy.community.reportsTotal}
                value={reports.meta.total}
              />
            </div>

            <div className="mt-8 grid gap-6 xl:grid-cols-2">
              <AccountCommunitySection
                title={copy.community.postsTitle}
                description={copy.community.postsDescription}
                items={posts.items}
                meta={posts.meta}
                emptyState={
                  <AccountEmptyState
                    title={copy.community.postsTitle}
                    description={messages.profile.noPosts}
                  />
                }
                renderItem={(post) => (
                  <PostCard
                    key={post.id}
                    locale={locale}
                    post={post}
                    messages={messages}
                    token={token}
                    currentUserId={session.user?.id}
                    onUpdated={syncPost}
                    onDeleted={removePost}
                  />
                )}
                onLoadMore={() => {
                  if (!username) return
                  void loadMore(
                    "posts",
                    posts,
                    (page) =>
                      getUserPosts(
                        username,
                        { page, per_page: PREVIEW_PER_PAGE.posts },
                        token,
                      ),
                    setPosts,
                  )
                }}
                viewAllHref={getLocalizedHref(locale, "account/community/posts")}
                loadingMore={loadingMore.posts}
                error={sectionErrors.posts}
                className="bg-background/70 p-6"
                itemClassName="space-y-6"
                {...sectionLabels}
              />

              <AccountCommunitySection
                title={copy.community.favoritesTitle}
                description={copy.community.favoritesDescription}
                items={favorites.items}
                meta={favorites.meta}
                emptyState={
                  <AccountEmptyState
                    title={copy.community.favoritesTitle}
                    description={messages.profile.noFavorites}
                  />
                }
                renderItem={(post) => (
                  <PostCard
                    key={post.id}
                    locale={locale}
                    post={post}
                    messages={messages}
                    token={token}
                    currentUserId={session.user?.id}
                    onUpdated={syncPost}
                    onDeleted={removePost}
                  />
                )}
                onLoadMore={() => {
                  if (!username) return
                  void loadMore(
                    "favorites",
                    favorites,
                    (page) =>
                      getUserFavorites(
                        username,
                        { page, per_page: PREVIEW_PER_PAGE.favorites },
                        token,
                      ),
                    setFavorites,
                  )
                }}
                viewAllHref={getLocalizedHref(locale, "account/community/saved")}
                loadingMore={loadingMore.favorites}
                error={sectionErrors.favorites}
                className="bg-background/70 p-6"
                itemClassName="space-y-6"
                {...sectionLabels}
              />
            </div>

            <div className="mt-6 grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
              <AccountCommunitySection
                title={copy.community.commentsTitle}
                description={copy.community.commentsDescription}
                items={comments.items}
                meta={comments.meta}
                emptyState={
                  <AccountEmptyState
                    title={copy.community.commentsTitle}
                    description={messages.profile.noComments}
                  />
                }
                renderItem={(comment) => (
                  <AccountCommunityCommentCard
                    key={comment.id}
                    locale={locale}
                    messages={messages}
                    comment={comment}
                  />
                )}
                onLoadMore={() => {
                  if (!username) return
                  void loadMore(
                    "comments",
                    comments,
                    (page) =>
                      getUserComments(
                        username,
                        { page, per_page: PREVIEW_PER_PAGE.comments },
                        token,
                      ),
                    setComments,
                  )
                }}
                viewAllHref={getLocalizedHref(locale, "account/community/comments")}
                loadingMore={loadingMore.comments}
                error={sectionErrors.comments}
                className="bg-background/70 p-6"
                {...sectionLabels}
              />

              <div className="space-y-6">
                <AccountCommunitySection
                  title={copy.community.followersTitle}
                  description={copy.community.followersDescription}
                  items={followers.items}
                  meta={followers.meta}
                  emptyState={
                    <AccountEmptyState
                      title={copy.community.followersTitle}
                      description={copy.community.noFollowers}
                    />
                  }
                  renderItem={(user) => (
                    <AccountCommunityUserRow
                      key={user.id}
                      locale={locale}
                      user={user}
                    />
                  )}
                  onLoadMore={() => {
                    if (!username) return
                    void loadMore(
                      "followers",
                      followers,
                      (page) =>
                        getUserFollowers(
                          username,
                          { page, per_page: PREVIEW_PER_PAGE.followers },
                          token,
                        ),
                      setFollowers,
                    )
                  }}
                  viewAllHref={getLocalizedHref(
                    locale,
                    "account/community/followers",
                  )}
                  loadingMore={loadingMore.followers}
                  error={sectionErrors.followers}
                  className="bg-background/70 p-6"
                  itemClassName="space-y-3"
                  {...sectionLabels}
                />

                <AccountCommunitySection
                  title={copy.community.followingTitle}
                  description={copy.community.followingDescription}
                  items={following.items}
                  meta={following.meta}
                  emptyState={
                    <AccountEmptyState
                      title={copy.community.followingTitle}
                      description={copy.community.noFollowing}
                    />
                  }
                  renderItem={(user) => (
                    <AccountCommunityUserRow
                      key={user.id}
                      locale={locale}
                      user={user}
                    />
                  )}
                  onLoadMore={() => {
                    if (!username) return
                    void loadMore(
                      "following",
                      following,
                      (page) =>
                        getUserFollowing(
                          username,
                          { page, per_page: PREVIEW_PER_PAGE.following },
                          token,
                        ),
                      setFollowing,
                    )
                  }}
                  viewAllHref={getLocalizedHref(
                    locale,
                    "account/community/following",
                  )}
                  loadingMore={loadingMore.following}
                  error={sectionErrors.following}
                  className="bg-background/70 p-6"
                  itemClassName="space-y-3"
                  {...sectionLabels}
                />
              </div>
            </div>

            <AccountCommunitySection
              title={copy.community.reportsTitle}
              description={copy.community.reportsDescription}
              items={reports.items}
              meta={reports.meta}
              emptyState={
                <AccountEmptyState
                  title={copy.community.reportsTitle}
                  description={copy.community.reportsEmpty}
                />
              }
              renderItem={(report) => (
                <AccountCommunityReportCard
                  key={report.id}
                  locale={locale}
                  copy={copy.community}
                  report={report}
                />
              )}
              onLoadMore={() => {
                if (!token) return
                void loadMore(
                  "reports",
                  reports,
                  (page) =>
                    listMyReports(token, {
                      page,
                      per_page: PREVIEW_PER_PAGE.reports,
                    }),
                  setReports,
                )
              }}
              viewAllHref={getLocalizedHref(locale, "account/community/reports")}
              loadingMore={loadingMore.reports}
              error={sectionErrors.reports}
              className="mt-8 bg-background/70 p-6"
              {...sectionLabels}
            />
          </>
        )}
      </AccountPanel>

      <CreatePostPanel
        locale={locale}
        messages={messages}
        token={session.token}
        open={isCreateOpen}
        onOpenChange={setIsCreateOpen}
        onSuccess={() => {
          setReloadKey((k) => k + 1)
        }}
      />
    </>
  )
}
