"use client"

import Link from "next/link"
import { useEffect, useState, useTransition } from "react"

import { Button } from "@/components/ui/button"
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetHeader,
  SheetTitle,
} from "@/components/ui/sheet"
import { getErrorMessage } from "@/lib/api/client"
import {
  getUserComments,
  getUserFollowers,
  getUserFollowing,
  getUserPosts,
  getUserProfile,
  toggleFollowUser,
} from "@/lib/api/users"
import { getIntlLocale, getLocalizedHref, type Locale } from "@/lib/i18n"
import type {
  CommunityComment,
  CommunityPost,
  CommunityUser,
} from "@/lib/types"

type CommunityUserSheetProps = {
  locale: Locale
  userId: number | null
  currentUserId?: number | null
  token?: string | null
  open: boolean
  onOpenChange: (open: boolean) => void
}

function formatDate(locale: Locale, value?: string | null) {
  if (!value) {
    return null
  }

  return new Intl.DateTimeFormat(getIntlLocale(locale), {
    dateStyle: "medium",
  }).format(new Date(value))
}

function getPostPreview(post: CommunityPost) {
  const preview = (post.excerpt || post.content).trim()

  if (preview.length <= 120) {
    return preview
  }

  return `${preview.slice(0, 120)}...`
}

function getCommentPreview(comment: CommunityComment) {
  const preview = comment.content.trim()

  if (preview.length <= 140) {
    return preview
  }

  return `${preview.slice(0, 140)}...`
}

export function CommunityUserSheet({
  locale,
  userId,
  currentUserId,
  token,
  open,
  onOpenChange,
}: CommunityUserSheetProps) {
  const [activeUserId, setActiveUserId] = useState<number | null>(userId)
  const [user, setUser] = useState<CommunityUser | null>(null)
  const [posts, setPosts] = useState<CommunityPost[]>([])
  const [comments, setComments] = useState<CommunityComment[]>([])
  const [followers, setFollowers] = useState<CommunityUser[]>([])
  const [following, setFollowing] = useState<CommunityUser[]>([])
  const [message, setMessage] = useState<string | null>(null)
  const [isLoading, setIsLoading] = useState(false)
  const [isPending, startTransition] = useTransition()

  useEffect(() => {
    if (open) {
      setActiveUserId(userId)
    }
  }, [open, userId])

  useEffect(() => {
    if (!open || activeUserId === null) {
      if (!open) {
        setUser(null)
        setPosts([])
        setComments([])
        setFollowers([])
        setFollowing([])
      }

      return
    }

    const selectedUserId = activeUserId
    let isCancelled = false

    async function loadUser() {
      setIsLoading(true)
      setMessage(null)

      try {
        const [
          nextUser,
          nextPosts,
          nextComments,
          nextFollowers,
          nextFollowing,
        ] = await Promise.all([
          getUserProfile(selectedUserId, token),
          getUserPosts(selectedUserId, { per_page: 3 }, token),
          getUserComments(selectedUserId, { per_page: 3 }, token),
          getUserFollowers(selectedUserId, { per_page: 4 }, token),
          getUserFollowing(selectedUserId, { per_page: 4 }, token),
        ])

        if (isCancelled) {
          return
        }

        setUser(nextUser)
        setPosts(nextPosts.items)
        setComments(nextComments.items)
        setFollowers(nextFollowers.items)
        setFollowing(nextFollowing.items)
      } catch (error) {
        if (!isCancelled) {
          setUser(null)
          setPosts([])
          setComments([])
          setFollowers([])
          setFollowing([])
          setMessage(getErrorMessage(error))
        }
      } finally {
        if (!isCancelled) {
          setIsLoading(false)
        }
      }
    }

    void loadUser()

    return () => {
      isCancelled = true
    }
  }, [activeUserId, open, token])

  const canFollow = Boolean(
    token && user && currentUserId && user.id !== currentUserId,
  )

  return (
    <Sheet open={open} onOpenChange={onOpenChange}>
      <SheetContent side="right" className="overflow-y-auto">
        <SheetHeader className="border-b border-border/60 pb-5">
          <SheetTitle>Community profile</SheetTitle>
          <SheetDescription>
            Public profile details, follow state, and recent posts from the backend user directory.
          </SheetDescription>
        </SheetHeader>

        <div className="space-y-6 p-4">
          {message ? (
            <div className="rounded-2xl border border-border/60 bg-card px-4 py-3 text-sm text-foreground">
              {message}
            </div>
          ) : null}

          {isLoading ? (
            <div className="rounded-2xl border border-border/60 bg-card px-4 py-6 text-sm text-muted-foreground">
              Loading profile...
            </div>
          ) : null}

          {user ? (
            <>
              <div className="rounded-3xl border border-border/60 bg-card p-6">
                <p className="text-sm uppercase tracking-[0.18em] text-primary">
                  @{user.username}
                </p>
                <h3 className="mt-3 font-serif text-3xl text-foreground">
                  {user.name}
                </h3>
                <div className="mt-4 space-y-2 text-sm text-muted-foreground">
                  {user.profile?.bio ? <p>{user.profile.bio}</p> : null}
                  {user.profile?.school_or_company ? (
                    <p>{user.profile.school_or_company}</p>
                  ) : null}
                  {user.profile?.location ? <p>{user.profile.location}</p> : null}
                  {user.profile?.website ? (
                    <Link
                      href={user.profile.website}
                      className="text-primary transition-colors hover:text-foreground"
                    >
                      {user.profile.website}
                    </Link>
                  ) : null}
                </div>

                <div className="mt-6 grid grid-cols-2 gap-3 text-sm">
                  <div className="rounded-2xl bg-background p-4">
                    <p className="text-muted-foreground">Followers</p>
                    <p className="mt-1 text-xl text-foreground">
                      {user.followers_count ?? 0}
                    </p>
                  </div>
                  <div className="rounded-2xl bg-background p-4">
                    <p className="text-muted-foreground">Following</p>
                    <p className="mt-1 text-xl text-foreground">
                      {user.following_count ?? 0}
                    </p>
                  </div>
                  <div className="rounded-2xl bg-background p-4">
                    <p className="text-muted-foreground">Posts</p>
                    <p className="mt-1 text-xl text-foreground">
                      {user.posts_count ?? 0}
                    </p>
                  </div>
                  <div className="rounded-2xl bg-background p-4">
                    <p className="text-muted-foreground">Comments</p>
                    <p className="mt-1 text-xl text-foreground">
                      {user.comments_count ?? 0}
                    </p>
                  </div>
                </div>

                {canFollow ? (
                  <div className="mt-6">
                    <Button
                      type="button"
                      className="w-full"
                      variant={user.is_following ? "outline" : "default"}
                      disabled={isPending}
                      onClick={() => {
                        if (!token) {
                          return
                        }

                        startTransition(() => {
                          void toggleFollowUser(user.id, user.is_following, token)
                            .then((payload) => {
                              setUser((currentUser) =>
                                currentUser
                                  ? {
                                      ...currentUser,
                                      is_following: payload.is_following,
                                      followers_count: Math.max(
                                        0,
                                        (currentUser.followers_count ?? 0) +
                                          (payload.is_following ? 1 : -1),
                                      ),
                                    }
                                  : currentUser,
                              )
                            })
                            .catch((error) => {
                              setMessage(getErrorMessage(error))
                            })
                        })
                      }}
                    >
                      {user.is_following ? "Following" : "Follow"}
                    </Button>
                  </div>
                ) : null}
              </div>

              <div className="rounded-3xl border border-border/60 bg-card p-6">
                <p className="text-sm uppercase tracking-[0.18em] text-primary">
                  Recent posts
                </p>
                <div className="mt-4 space-y-4">
                  {posts.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                      No visible posts for this profile yet.
                    </p>
                  ) : (
                    posts.map((post) => (
                      <Link
                        key={post.id}
                        href={getLocalizedHref(locale, `community/${post.slug}`)}
                        className="block rounded-2xl bg-background p-4 transition-colors hover:bg-secondary/40"
                        onClick={() => onOpenChange(false)}
                      >
                        <p className="font-medium text-foreground">{post.title}</p>
                        <p className="mt-2 text-sm text-muted-foreground">
                          {getPostPreview(post)}
                        </p>
                      </Link>
                    ))
                  )}
                </div>
              </div>

              <div className="rounded-3xl border border-border/60 bg-card p-6">
                <p className="text-sm uppercase tracking-[0.18em] text-primary">
                  Recent comments
                </p>
                <div className="mt-4 space-y-4">
                  {comments.length === 0 ? (
                    <p className="text-sm text-muted-foreground">
                      No visible comments for this profile yet.
                    </p>
                  ) : (
                    comments.map((comment) =>
                      comment.post ? (
                        <Link
                          key={comment.id}
                          href={`${getLocalizedHref(locale, `community/${comment.post.slug}`)}#comment-${comment.id}`}
                          className="block rounded-2xl bg-background p-4 transition-colors hover:bg-secondary/40"
                          onClick={() => onOpenChange(false)}
                        >
                          <p className="font-medium text-foreground">
                            {comment.post.title}
                          </p>
                          <p className="mt-2 text-sm text-muted-foreground">
                            {getCommentPreview(comment)}
                          </p>
                          {comment.created_at ? (
                            <p className="mt-3 text-xs uppercase tracking-[0.14em] text-muted-foreground">
                              {formatDate(locale, comment.created_at)}
                            </p>
                          ) : null}
                        </Link>
                      ) : null,
                    )
                  )}
                </div>
              </div>

              <div className="rounded-3xl border border-border/60 bg-card p-6">
                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                  <div>
                    <p className="text-sm uppercase tracking-[0.18em] text-primary">
                      Followers
                    </p>
                    <div className="mt-4 space-y-3">
                      {followers.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                          No followers to show yet.
                        </p>
                      ) : (
                        followers.map((listedUser) => (
                          <button
                            key={listedUser.id}
                            type="button"
                            className="block w-full rounded-2xl bg-background p-4 text-left transition-colors hover:bg-secondary/40"
                            onClick={() => {
                              setActiveUserId(listedUser.id)
                              setMessage(null)
                            }}
                          >
                            <p className="font-medium text-foreground">
                              {listedUser.name}
                            </p>
                            <p className="mt-1 text-sm text-muted-foreground">
                              @{listedUser.username}
                            </p>
                          </button>
                        ))
                      )}
                    </div>
                  </div>

                  <div>
                    <p className="text-sm uppercase tracking-[0.18em] text-primary">
                      Following
                    </p>
                    <div className="mt-4 space-y-3">
                      {following.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                          This profile is not following anyone yet.
                        </p>
                      ) : (
                        following.map((listedUser) => (
                          <button
                            key={listedUser.id}
                            type="button"
                            className="block w-full rounded-2xl bg-background p-4 text-left transition-colors hover:bg-secondary/40"
                            onClick={() => {
                              setActiveUserId(listedUser.id)
                              setMessage(null)
                            }}
                          >
                            <p className="font-medium text-foreground">
                              {listedUser.name}
                            </p>
                            <p className="mt-1 text-sm text-muted-foreground">
                              @{listedUser.username}
                            </p>
                          </button>
                        ))
                      )}
                    </div>
                  </div>
                </div>
              </div>
            </>
          ) : null}
        </div>
      </SheetContent>
    </Sheet>
  )
}
