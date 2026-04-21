"use client"

import { useEffect, useEffectEvent, useState, useTransition } from "react"

import { PostCard } from "@/components/community/PostCard"
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
import { Button } from "@/components/ui/button"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { getErrorMessage } from "@/lib/api/client"
import { listPosts } from "@/lib/api/posts"
import {
  formatCommunityDate,
  getCommunityUserInitials,
} from "@/lib/community-ui"
import { type Locale, type SiteMessages } from "@/lib/i18n"
import { followUser, getUserPosts, getUserProfile, unfollowUser } from "@/lib/api/users"
import type { CommunityPost, CommunityUser } from "@/lib/types"
import { useAuthSession } from "@/hooks/use-auth-session"

type CommunityProfilePageProps = {
  locale: Locale
  username: string
  messages: SiteMessages["community"]
}

export function CommunityProfilePage({
  locale,
  username,
  messages,
}: CommunityProfilePageProps) {
  const session = useAuthSession()
  const [user, setUser] = useState<CommunityUser | null>(null)
  const [posts, setPosts] = useState<CommunityPost[]>([])
  const [likedPosts, setLikedPosts] = useState<CommunityPost[]>([])
  const [canShowLiked, setCanShowLiked] = useState(true)
  const [message, setMessage] = useState<string | null>(null)
  const [isLoading, setIsLoading] = useState(false)
  const [isPending, startTransition] = useTransition()

  const loadProfile = useEffectEvent(async () => {
    if (!session.isReady) {
      return
    }

    setIsLoading(true)
    setMessage(null)

    try {
      const [profile, userPosts] = await Promise.all([
        getUserProfile(username, session.token),
        getUserPosts(username, { per_page: 12 }, session.token),
      ])

      setUser(profile)
      setPosts(userPosts.items)

      try {
        const likedResponse = await listPosts(
          {
            liked_by: username,
            per_page: 12,
          },
          session.token,
        )

        setLikedPosts(likedResponse.posts)
        setCanShowLiked(true)
      } catch {
        setLikedPosts([])
        setCanShowLiked(false)
      }
    } catch (error) {
      setUser(null)
      setPosts([])
      setLikedPosts([])
      setMessage(getErrorMessage(error))
    } finally {
      setIsLoading(false)
    }
  })

  useEffect(() => {
    if (!session.isReady) {
      return
    }

    void loadProfile()
  }, [session.isReady, username])

  const isOwnProfile = session.user?.username === username

  return (
    <section className="bg-background py-14 lg:py-16">
      <div className="mx-auto max-w-7xl px-6 lg:px-8">
        {message ? (
          <div className="mb-8 rounded-2xl border border-border/60 bg-card px-5 py-4 text-sm text-foreground">
            {message}
          </div>
        ) : null}

        {isLoading && !user ? (
          <div className="rounded-[2rem] border border-border/60 bg-card p-8 text-muted-foreground">
            {messages.profile.loading}
          </div>
        ) : null}

        {!isLoading && !user ? (
          <div className="rounded-[2rem] border border-border/60 bg-card p-8">
            <h1 className="font-serif text-3xl text-foreground">
              {messages.profile.notFound}
            </h1>
          </div>
        ) : null}

        {user ? (
          <>
            <div className="rounded-[2rem] border border-border/60 bg-card p-8">
              <div className="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                <div className="flex flex-col gap-5 sm:flex-row sm:items-start">
                  <Avatar className="size-24 border border-border/60">
                    <AvatarImage src={user.avatar_url ?? undefined} />
                    <AvatarFallback className="text-xl">
                      {getCommunityUserInitials(user)}
                    </AvatarFallback>
                  </Avatar>
                  <div>
                    <p className="text-sm uppercase tracking-[0.2em] text-primary">
                      @{user.username}
                    </p>
                    <h1 className="mt-3 font-serif text-4xl text-foreground">
                      {user.name}
                    </h1>
                    {user.profile?.bio ? (
                      <p className="mt-4 max-w-2xl leading-relaxed text-muted-foreground">
                        {user.profile.bio}
                      </p>
                    ) : null}
                    <div className="mt-4 flex flex-wrap gap-x-6 gap-y-2 text-sm text-muted-foreground">
                      {user.profile?.school_or_company ? (
                        <span>{user.profile.school_or_company}</span>
                      ) : null}
                      {user.profile?.location ? <span>{user.profile.location}</span> : null}
                      {user.created_at ? (
                        <span>
                          {messages.profile.joined}{" "}
                          {formatCommunityDate(locale, user.created_at, {
                            dateStyle: "medium",
                            timeStyle: undefined,
                          }) ?? ""}
                        </span>
                      ) : null}
                    </div>
                  </div>
                </div>

                {!isOwnProfile && session.user ? (
                  <Button
                    type="button"
                    variant={user.is_following ? "outline" : "default"}
                    disabled={isPending}
                    onClick={() => {
                      startTransition(() => {
                        void (user.is_following
                          ? unfollowUser(username, session.token!)
                          : followUser(username, session.token!))
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
                    {user.is_following
                      ? messages.profile.unfollow
                      : messages.profile.follow}
                  </Button>
                ) : null}
              </div>

              <div className="mt-8 grid gap-4 sm:grid-cols-3">
                <div className="rounded-[1.5rem] bg-background p-5">
                  <p className="text-sm text-muted-foreground">
                    {messages.profile.postsCount}
                  </p>
                  <p className="mt-2 text-2xl text-foreground">
                    {user.posts_count ?? 0}
                  </p>
                </div>
                <div className="rounded-[1.5rem] bg-background p-5">
                  <p className="text-sm text-muted-foreground">
                    {messages.profile.followers}
                  </p>
                  <p className="mt-2 text-2xl text-foreground">
                    {user.followers_count ?? 0}
                  </p>
                </div>
                <div className="rounded-[1.5rem] bg-background p-5">
                  <p className="text-sm text-muted-foreground">
                    {messages.profile.following}
                  </p>
                  <p className="mt-2 text-2xl text-foreground">
                    {user.following_count ?? 0}
                  </p>
                </div>
              </div>
            </div>

            <Tabs defaultValue="posts" className="mt-10">
              <TabsList>
                <TabsTrigger value="posts">{messages.profile.posts}</TabsTrigger>
                {canShowLiked ? (
                  <TabsTrigger value="liked">{messages.profile.liked}</TabsTrigger>
                ) : null}
              </TabsList>

              <TabsContent value="posts" className="mt-6">
                {posts.length > 0 ? (
                  <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    {posts.map((post) => (
                      <PostCard
                        key={post.id}
                        locale={locale}
                        post={post}
                        messages={messages}
                        token={session.token}
                        currentUserId={session.user?.id}
                        onUpdated={(updatedPost) => {
                          setPosts((currentPosts) =>
                            currentPosts.map((currentPost) =>
                              currentPost.id === updatedPost.id
                                ? updatedPost
                                : currentPost,
                            ),
                          )
                        }}
                        onDeleted={(postId) => {
                          setPosts((currentPosts) =>
                            currentPosts.filter((currentPost) => currentPost.id !== postId),
                          )
                          setLikedPosts((currentPosts) =>
                            currentPosts.filter((currentPost) => currentPost.id !== postId),
                          )
                          setUser((currentUser) =>
                            currentUser
                              ? {
                                  ...currentUser,
                                  posts_count: Math.max(
                                    0,
                                    (currentUser.posts_count ?? 0) - 1,
                                  ),
                                }
                              : currentUser,
                          )
                        }}
                      />
                    ))}
                  </div>
                ) : (
                  <div className="rounded-[2rem] border border-border/60 bg-card p-8 text-muted-foreground">
                    {messages.profile.noPosts}
                  </div>
                )}
              </TabsContent>

              {canShowLiked ? (
                <TabsContent value="liked" className="mt-6">
                  {likedPosts.length > 0 ? (
                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                      {likedPosts.map((post) => (
                        <PostCard
                          key={post.id}
                          locale={locale}
                          post={post}
                          messages={messages}
                          token={session.token}
                          currentUserId={session.user?.id}
                          onUpdated={(updatedPost) => {
                            setLikedPosts((currentPosts) =>
                              currentPosts.map((currentPost) =>
                                currentPost.id === updatedPost.id
                                  ? updatedPost
                                  : currentPost,
                              ),
                            )
                          }}
                          onDeleted={(postId) => {
                            setLikedPosts((currentPosts) =>
                              currentPosts.filter((currentPost) => currentPost.id !== postId),
                            )
                          }}
                        />
                      ))}
                    </div>
                  ) : (
                    <div className="rounded-[2rem] border border-border/60 bg-card p-8 text-muted-foreground">
                      {messages.profile.noLikedPosts}
                    </div>
                  )}
                </TabsContent>
              ) : null}
            </Tabs>
          </>
        ) : null}
      </div>
    </section>
  )
}
