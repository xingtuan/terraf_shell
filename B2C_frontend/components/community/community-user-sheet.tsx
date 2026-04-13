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
  getUserPosts,
  getUserProfile,
  toggleFollowUser,
} from "@/lib/api/users"
import { getLocalizedHref, type Locale } from "@/lib/i18n"
import type { CommunityPost, CommunityUser } from "@/lib/types"

type CommunityUserSheetProps = {
  locale: Locale
  userId: number | null
  currentUserId?: number | null
  token?: string | null
  open: boolean
  onOpenChange: (open: boolean) => void
}

export function CommunityUserSheet({
  locale,
  userId,
  currentUserId,
  token,
  open,
  onOpenChange,
}: CommunityUserSheetProps) {
  const [user, setUser] = useState<CommunityUser | null>(null)
  const [posts, setPosts] = useState<CommunityPost[]>([])
  const [message, setMessage] = useState<string | null>(null)
  const [isLoading, setIsLoading] = useState(false)
  const [isPending, startTransition] = useTransition()

  useEffect(() => {
    if (!open || userId === null) {
      return
    }

    const activeUserId = userId

    let isCancelled = false

    async function loadUser() {
      setIsLoading(true)
      setMessage(null)

      try {
        const [nextUser, nextPosts] = await Promise.all([
          getUserProfile(activeUserId, token),
          getUserPosts(activeUserId, { per_page: 3 }, token),
        ])

        if (isCancelled) {
          return
        }

        setUser(nextUser)
        setPosts(nextPosts.items)
      } catch (error) {
        if (!isCancelled) {
          setUser(null)
          setPosts([])
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
  }, [open, token, userId])

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
                          {post.excerpt || post.content.slice(0, 120)}
                        </p>
                      </Link>
                    ))
                  )}
                </div>
              </div>
            </>
          ) : null}
        </div>
      </SheetContent>
    </Sheet>
  )
}
