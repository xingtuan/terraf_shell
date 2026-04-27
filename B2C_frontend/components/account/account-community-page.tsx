"use client"

import { useEffect, useState } from "react"
import Link from "next/link"

import { CommunityUserAvatar } from "@/components/community/CommunityUserAvatar"
import { CreatePostPanel } from "@/components/community/CreatePostPanel"
import { PostCard } from "@/components/community/PostCard"
import { Button } from "@/components/ui/button"
import { getAccountCopy } from "@/lib/account-copy"
import { getErrorMessage } from "@/lib/api/client"
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
  CommunityComment,
  CommunityPost,
  CommunityUser,
  UserProfile,
} from "@/lib/types"
import { useAuthSession } from "@/hooks/use-auth-session"
import {
  AccountEmptyState,
  AccountPageHeader,
  AccountPanel,
  AccountStatCard,
} from "@/components/account/account-ui"
import { formatAccountDate } from "@/components/account/account-utils"

type AccountCommunityPageProps = {
  locale: Locale
}

export function AccountCommunityPage({ locale }: AccountCommunityPageProps) {
  const session = useAuthSession()
  const copy = getAccountCopy(locale)
  const messages = getMessages(locale).community
  const [profile, setProfile] = useState<UserProfile | null>(null)
  const [posts, setPosts] = useState<CommunityPost[]>([])
  const [comments, setComments] = useState<CommunityComment[]>([])
  const [favorites, setFavorites] = useState<CommunityPost[]>([])
  const [followers, setFollowers] = useState<CommunityUser[]>([])
  const [following, setFollowing] = useState<CommunityUser[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState<string | null>(null)
  const [isCreateOpen, setIsCreateOpen] = useState(false)
  const [reloadKey, setReloadKey] = useState(0)

  useEffect(() => {
    const token = session.token
    const username = session.user?.username

    if (!token || !username) {
      setLoading(false)
      return
    }

    let cancelled = false

    async function loadCommunity() {
      setLoading(true)
      setError(null)

      try {
        const [
          nextProfile,
          postsResponse,
          commentsResponse,
          favoritesResponse,
          followersResponse,
          followingResponse,
        ] = await Promise.all([
          getUserProfile(username, token),
          getUserPosts(username, { per_page: 4 }, token),
          getUserComments(username, { per_page: 4 }, token),
          getUserFavorites(username, { per_page: 4 }, token),
          getUserFollowers(username, { per_page: 6 }, token),
          getUserFollowing(username, { per_page: 6 }, token),
        ])

        if (cancelled) return
        setProfile(nextProfile)
        setPosts(postsResponse.items)
        setComments(commentsResponse.items)
        setFavorites(favoritesResponse.items)
        setFollowers(followersResponse.items)
        setFollowing(followingResponse.items)
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
    setPosts((currentPosts) =>
      currentPosts.map((currentPost) =>
        currentPost.id === updatedPost.id ? updatedPost : currentPost,
      ),
    )
    setFavorites((currentFavorites) =>
      currentFavorites.map((currentPost) =>
        currentPost.id === updatedPost.id ? updatedPost : currentPost,
      ),
    )
  }

  function removePost(postId: number) {
    setPosts((currentPosts) =>
      currentPosts.filter((currentPost) => currentPost.id !== postId),
    )
    setFavorites((currentFavorites) =>
      currentFavorites.filter((currentPost) => currentPost.id !== postId),
    )
    setProfile((currentProfile) =>
      currentProfile
        ? {
            ...currentProfile,
            posts_count: Math.max(0, (currentProfile.posts_count ?? 0) - 1),
          }
        : currentProfile,
    )
  }

  const publicProfileHref = session.user?.username
    ? getLocalizedHref(locale, `community/u/${session.user.username}`)
    : getLocalizedHref(locale, "community")

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
                <Link href={publicProfileHref}>{copy.community.viewPublicProfile}</Link>
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
            <div className="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-5">
              <AccountStatCard
                label={messages.profile.postsCount}
                value={profile?.posts_count ?? posts.length}
              />
              <AccountStatCard
                label={messages.profile.commentsCount}
                value={profile?.comments_count ?? comments.length}
              />
              <AccountStatCard
                label={messages.profile.favorites}
                value={favorites.length}
              />
              <AccountStatCard
                label={messages.profile.followers}
                value={profile?.followers_count ?? followers.length}
              />
              <AccountStatCard
                label={messages.profile.following}
                value={profile?.following_count ?? following.length}
              />
            </div>

            <div className="mt-8 grid gap-6 xl:grid-cols-2">
              <AccountPanel className="bg-background/70 p-6">
                <div className="flex items-end justify-between gap-4">
                  <div>
                    <p className="text-sm uppercase tracking-[0.18em] text-primary">
                      {copy.community.postsTitle}
                    </p>
                    <h2 className="mt-3 font-serif text-3xl text-foreground">
                      {copy.community.postsTitle}
                    </h2>
                  </div>
                </div>

                <div className="mt-6 space-y-6">
                  {posts.length > 0 ? (
                    posts.map((post) => (
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
                    ))
                  ) : (
                    <AccountEmptyState
                      title={copy.community.postsTitle}
                      description={messages.profile.noPosts}
                    />
                  )}
                </div>
              </AccountPanel>

              <AccountPanel className="bg-background/70 p-6">
                <div>
                  <p className="text-sm uppercase tracking-[0.18em] text-primary">
                    {copy.community.favoritesTitle}
                  </p>
                  <h2 className="mt-3 font-serif text-3xl text-foreground">
                    {copy.community.favoritesTitle}
                  </h2>
                </div>

                <div className="mt-6 space-y-6">
                  {favorites.length > 0 ? (
                    favorites.map((post) => (
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
                    ))
                  ) : (
                    <AccountEmptyState
                      title={copy.community.favoritesTitle}
                      description={messages.profile.noFavorites}
                    />
                  )}
                </div>
              </AccountPanel>
            </div>

            <div className="mt-6 grid gap-6 xl:grid-cols-[1.05fr_0.95fr]">
              <AccountPanel className="bg-background/70 p-6">
                <div>
                  <p className="text-sm uppercase tracking-[0.18em] text-primary">
                    {copy.community.commentsTitle}
                  </p>
                  <h2 className="mt-3 font-serif text-3xl text-foreground">
                    {copy.community.commentsTitle}
                  </h2>
                </div>

                <div className="mt-6 space-y-4">
                  {comments.length > 0 ? (
                    comments.map((comment) => {
                      const commentHref = comment.post?.slug
                        ? getLocalizedHref(
                            locale,
                            `community/${comment.post.slug}#comment-${comment.id}`,
                          )
                        : null

                      return (
                        <article
                          key={comment.id}
                          className="rounded-[1.5rem] border border-border/60 bg-card p-5"
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
                                  {formatAccountDate(locale, comment.created_at)}
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
                    })
                  ) : (
                    <AccountEmptyState
                      title={copy.community.commentsTitle}
                      description={messages.profile.noComments}
                    />
                  )}
                </div>
              </AccountPanel>

              <div className="space-y-6">
                <AccountPanel className="bg-background/70 p-6">
                  <p className="text-sm uppercase tracking-[0.18em] text-primary">
                    {copy.community.followersTitle}
                  </p>
                  <h2 className="mt-3 font-serif text-3xl text-foreground">
                    {copy.community.followersTitle}
                  </h2>
                  <p className="mt-3 text-sm leading-relaxed text-muted-foreground">
                    {copy.community.networkDescription}
                  </p>

                  <div className="mt-6 space-y-3">
                    {followers.length > 0 ? (
                      followers.map((user) => (
                        <Link
                          key={user.id}
                          href={getLocalizedHref(locale, `community/u/${user.username}`)}
                          className="flex items-center gap-4 rounded-[1.5rem] border border-border/60 bg-card p-4 transition-colors hover:border-border hover:bg-background"
                        >
                          <CommunityUserAvatar
                            user={user}
                            className="size-12 border border-border/60"
                            sizes="48px"
                          />
                          <div>
                            <p className="font-medium text-foreground">{user.name}</p>
                            <p className="text-sm text-muted-foreground">
                              @{user.username}
                            </p>
                          </div>
                        </Link>
                      ))
                    ) : (
                      <AccountEmptyState
                        title={copy.community.followersTitle}
                        description={copy.community.noFollowers}
                      />
                    )}
                  </div>
                </AccountPanel>

                <AccountPanel className="bg-background/70 p-6">
                  <p className="text-sm uppercase tracking-[0.18em] text-primary">
                    {copy.community.followingTitle}
                  </p>
                  <h2 className="mt-3 font-serif text-3xl text-foreground">
                    {copy.community.followingTitle}
                  </h2>

                  <div className="mt-6 space-y-3">
                    {following.length > 0 ? (
                      following.map((user) => (
                        <Link
                          key={user.id}
                          href={getLocalizedHref(locale, `community/u/${user.username}`)}
                          className="flex items-center gap-4 rounded-[1.5rem] border border-border/60 bg-card p-4 transition-colors hover:border-border hover:bg-background"
                        >
                          <CommunityUserAvatar
                            user={user}
                            className="size-12 border border-border/60"
                            sizes="48px"
                          />
                          <div>
                            <p className="font-medium text-foreground">{user.name}</p>
                            <p className="text-sm text-muted-foreground">
                              @{user.username}
                            </p>
                          </div>
                        </Link>
                      ))
                    ) : (
                      <AccountEmptyState
                        title={copy.community.followingTitle}
                        description={copy.community.noFollowing}
                      />
                    )}
                  </div>
                </AccountPanel>
              </div>
            </div>
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
