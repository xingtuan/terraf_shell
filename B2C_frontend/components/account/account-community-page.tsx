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
import { listMyReports } from "@/lib/api/reports"
import { getLocalizedHref, getMessages, type Locale } from "@/lib/i18n"
import type {
  CommunityComment,
  CommunityPost,
  CommunityUser,
  ReportRecord,
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

type ReportStatusLabels = ReturnType<
  typeof getAccountCopy
>["community"]["reportStatusLabels"]

function reportStatusClass(status: string) {
  switch (status) {
    case "resolved":
      return "border-emerald-200 bg-emerald-50 text-emerald-700"
    case "reviewed":
      return "border-sky-200 bg-sky-50 text-sky-700"
    case "dismissed":
      return "border-stone-200 bg-stone-50 text-stone-700"
    default:
      return "border-amber-200 bg-amber-50 text-amber-700"
  }
}

function reportStatusLabel(
  labels: ReportStatusLabels,
  status: string,
) {
  return labels[status as keyof typeof labels] ?? status
}

function reportTargetSummary(report: ReportRecord) {
  const target = report.target

  if (target && "title" in target && target.title) {
    return target.title
  }

  if (target && "content" in target && target.content) {
    return target.content
  }

  if (target && "username" in target && target.username) {
    return `${target.name} (@${target.username})`
  }

  return `${report.target_type} #${report.target_id}`
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
  const [reports, setReports] = useState<ReportRecord[]>([])
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

    const authToken: string = token
    const userIdentifier: string = username
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
          reportsResponse,
        ] = await Promise.all([
          getUserProfile(userIdentifier, authToken),
          getUserPosts(userIdentifier, { per_page: 4 }, authToken),
          getUserComments(userIdentifier, { per_page: 4 }, authToken),
          getUserFavorites(userIdentifier, { per_page: 4 }, authToken),
          getUserFollowers(userIdentifier, { per_page: 6 }, authToken),
          getUserFollowing(userIdentifier, { per_page: 6 }, authToken),
          listMyReports(authToken, { per_page: 5 }),
        ])

        if (cancelled) return
        setProfile(nextProfile)
        setPosts(postsResponse.items)
        setComments(commentsResponse.items)
        setFavorites(favoritesResponse.items)
        setFollowers(followersResponse.items)
        setFollowing(followingResponse.items)
        setReports(reportsResponse.items)
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

            <AccountPanel id="my-reports" className="mt-8 bg-background/70 p-6">
              <div>
                <p className="text-sm uppercase tracking-[0.18em] text-primary">
                  {copy.community.reportsTitle}
                </p>
                <h2 className="mt-3 font-serif text-3xl text-foreground">
                  {copy.community.reportsTitle}
                </h2>
                <p className="mt-3 text-sm leading-relaxed text-muted-foreground">
                  {copy.community.reportsDescription}
                </p>
              </div>

              <div className="mt-6 space-y-4">
                {reports.length > 0 ? (
                  reports.map((report) => {
                    const timeline = [
                      {
                        label: copy.community.reportDateLabels.created,
                        value: report.created_at,
                      },
                      {
                        label: copy.community.reportDateLabels.updated,
                        value: report.updated_at,
                      },
                      {
                        label: copy.community.reportDateLabels.reviewed,
                        value: report.reviewed_at,
                      },
                      {
                        label: copy.community.reportDateLabels.resolved,
                        value: report.resolved_at,
                      },
                      {
                        label: copy.community.reportDateLabels.dismissed,
                        value: report.dismissed_at,
                      },
                    ].filter((item) => item.value)

                    return (
                      <article
                        key={report.id}
                        className="rounded-[1.5rem] border border-border/60 bg-card p-5"
                      >
                        <div className="flex flex-wrap items-start justify-between gap-4">
                          <div className="min-w-0 space-y-2">
                            <div className="flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
                              <span>
                                {copy.community.reportIdLabel}: {report.id}
                              </span>
                              <span className="capitalize">
                                {report.target_type}
                              </span>
                            </div>
                            <h3 className="text-lg font-medium text-foreground">
                              {reportTargetSummary(report)}
                            </h3>
                          </div>
                          <span
                            className={`rounded-full border px-3 py-1 text-xs font-medium ${reportStatusClass(report.status)}`}
                          >
                            {reportStatusLabel(
                              copy.community.reportStatusLabels,
                              report.status,
                            )}
                          </span>
                        </div>

                        <dl className="mt-5 grid gap-4 text-sm sm:grid-cols-2">
                          <div>
                            <dt className="text-muted-foreground">
                              {copy.community.reportReasonLabel}
                            </dt>
                            <dd className="mt-1 text-foreground">{report.reason}</dd>
                          </div>
                          <div>
                            <dt className="text-muted-foreground">
                              {copy.community.reportTargetLabel}
                            </dt>
                            <dd className="mt-1 text-foreground">
                              {report.target_type} #{report.target_id}
                            </dd>
                          </div>
                        </dl>

                        {report.public_note ? (
                          <div className="mt-5 rounded-2xl bg-background px-4 py-3">
                            <p className="text-xs font-medium uppercase tracking-[0.14em] text-muted-foreground">
                              {copy.community.reportPublicNoteLabel}
                            </p>
                            <p className="mt-2 whitespace-pre-wrap text-sm leading-relaxed text-foreground">
                              {report.public_note}
                            </p>
                          </div>
                        ) : null}

                        {report.resolution_action === "action_taken" ? (
                          <p className="mt-4 text-sm text-muted-foreground">
                            {copy.community.reportPrivacyNotice}
                          </p>
                        ) : null}

                        <div className="mt-5 flex flex-wrap gap-3 text-xs text-muted-foreground">
                          {timeline.map((item) => (
                            <span key={item.label}>
                              {item.label}: {formatAccountDate(locale, item.value)}
                            </span>
                          ))}
                        </div>
                      </article>
                    )
                  })
                ) : (
                  <AccountEmptyState
                    title={copy.community.reportsTitle}
                    description={copy.community.reportsEmpty}
                  />
                )}
              </div>
            </AccountPanel>
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
