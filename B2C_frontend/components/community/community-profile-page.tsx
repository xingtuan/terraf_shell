"use client"

import { useEffect, useEffectEvent, useState } from "react"
import { useRouter } from "next/navigation"
import Link from "next/link"

import { deleteAddress, listAddresses } from "@/lib/api/addresses"
import { getOrders } from "@/lib/api/orders"
import { CommunityUserAvatar } from "@/components/community/CommunityUserAvatar"
import { EditProfileModal } from "@/components/community/EditProfileModal"
import { FollowButton } from "@/components/community/FollowButton"
import { PostCard } from "@/components/community/PostCard"
import { Button } from "@/components/ui/button"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { getErrorMessage } from "@/lib/api/client"
import {
  getUserComments,
  getUserFavorites,
  getUserPosts,
  getUserProfile,
} from "@/lib/api/users"
import { COMMUNITY_POSTS_REFRESH_EVENT } from "@/lib/community-events"
import { getIntlLocale, getLocalizedHref, type Locale, type SiteMessages } from "@/lib/i18n"
import type {
  Address,
  CommunityComment,
  CommunityPost,
  StoreOrder,
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

  return new Intl.DateTimeFormat(getIntlLocale(locale), {
    month: "long",
    year: "numeric",
  }).format(new Date(value))
}

function formatProfileDate(locale: Locale, value?: string | null) {
  if (!value) {
    return null
  }

  return new Intl.DateTimeFormat(getIntlLocale(locale), {
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

function statusClasses(status: StoreOrder["status"]) {
  switch (status) {
    case "confirmed":
      return "bg-sky-100 text-sky-700"
    case "processing":
      return "bg-amber-100 text-amber-700"
    case "shipped":
      return "bg-violet-100 text-violet-700"
    case "delivered":
      return "bg-emerald-100 text-emerald-700"
    case "cancelled":
      return "bg-red-100 text-red-700"
    default:
      return "bg-muted text-foreground"
  }
}

export function CommunityProfilePage({
  locale,
  username,
  messages,
  initialProfile = null,
}: CommunityProfilePageProps) {
  const router = useRouter()
  const session = useAuthSession()
  const [activeUsername, setActiveUsername] = useState(
    initialProfile?.username ?? username,
  )
  const [profile, setProfile] = useState<UserProfile | null>(initialProfile)
  const [posts, setPosts] = useState<CommunityPost[]>([])
  const [comments, setComments] = useState<CommunityComment[]>([])
  const [favorites, setFavorites] = useState<CommunityPost[]>([])
  const [recentOrders, setRecentOrders] = useState<StoreOrder[]>([])
  const [addresses, setAddresses] = useState<Address[]>([])
  const [postsTotal, setPostsTotal] = useState(initialProfile?.posts_count ?? 0)
  const [commentsTotal, setCommentsTotal] = useState(
    initialProfile?.comments_count ?? 0,
  )
  const [message, setMessage] = useState<string | null>(null)
  const [activeTab, setActiveTab] = useState("posts")
  const [isLoadingProfile, setIsLoadingProfile] = useState(!initialProfile)
  const [isLoadingPosts, setIsLoadingPosts] = useState(true)
  const [isLoadingComments, setIsLoadingComments] = useState(true)
  const [isLoadingFavorites, setIsLoadingFavorites] = useState(true)
  const [isLoadingAccount, setIsLoadingAccount] = useState(false)
  const [isEditOpen, setIsEditOpen] = useState(false)

  const loadProfile = useEffectEvent(async () => {
    setIsLoadingProfile(true)

    try {
      const nextProfile = await getUserProfile(activeUsername, session.token)
      setProfile(nextProfile)
    } catch (error) {
      setProfile(null)
      setMessage(getErrorMessage(error))
    } finally {
      setIsLoadingProfile(false)
    }
  })

  const loadPosts = useEffectEvent(async () => {
    setIsLoadingPosts(true)

    try {
      const nextPosts = await getUserPosts(
        activeUsername,
        { per_page: 12 },
        session.token,
      )
      setPosts(nextPosts.items)
      setPostsTotal(nextPosts.meta.total)
    } catch (error) {
      setPosts([])
      setPostsTotal(0)
      setMessage(getErrorMessage(error))
    } finally {
      setIsLoadingPosts(false)
    }
  })

  const loadFavorites = useEffectEvent(async () => {
    setIsLoadingFavorites(true)

    try {
      const nextFavorites = await getUserFavorites(
        activeUsername,
        { per_page: 12 },
        session.token,
      )
      setFavorites(nextFavorites.items)
    } catch (error) {
      setFavorites([])
      setMessage(getErrorMessage(error))
    } finally {
      setIsLoadingFavorites(false)
    }
  })

  const loadComments = useEffectEvent(async () => {
    setIsLoadingComments(true)

    try {
      const nextComments = await getUserComments(
        activeUsername,
        { per_page: 12 },
        session.token,
      )
      setComments(nextComments.items)
      setCommentsTotal(nextComments.meta.total)
    } catch (error) {
      setComments([])
      setCommentsTotal(0)
      setMessage(getErrorMessage(error))
    } finally {
      setIsLoadingComments(false)
    }
  })

  const loadAccount = useEffectEvent(async () => {
    if (!session.token || !session.user || session.user.username !== activeUsername) {
      setRecentOrders([])
      setAddresses([])
      setIsLoadingAccount(false)
      return
    }

    setIsLoadingAccount(true)

    try {
      const [ordersResponse, nextAddresses] = await Promise.all([
        getOrders(session.token, 1, 3),
        listAddresses(session.token),
      ])
      setRecentOrders(ordersResponse.items)
      setAddresses(nextAddresses)
    } catch (error) {
      setRecentOrders([])
      setAddresses([])
      setMessage(getErrorMessage(error))
    } finally {
      setIsLoadingAccount(false)
    }
  })

  useEffect(() => {
    setActiveUsername(initialProfile?.username ?? username)
    setProfile(initialProfile)
    setPostsTotal(initialProfile?.posts_count ?? 0)
    setCommentsTotal(initialProfile?.comments_count ?? 0)
    setActiveTab("posts")
  }, [initialProfile, username])

  useEffect(() => {
    if (!session.isReady) {
      return
    }

    setMessage(null)
    void Promise.all([
      loadProfile(),
      loadPosts(),
      loadComments(),
      loadFavorites(),
      loadAccount(),
    ])
  }, [session.isReady, session.token, activeUsername])

  useEffect(() => {
    const handleRefresh = () => {
      if (!profile || session.user?.id !== profile.id) {
        return
      }

      void Promise.all([loadProfile(), loadPosts(), loadComments(), loadAccount()])
    }

    window.addEventListener(COMMUNITY_POSTS_REFRESH_EVENT, handleRefresh)

    return () => {
      window.removeEventListener(COMMUNITY_POSTS_REFRESH_EVENT, handleRefresh)
    }
  }, [loadAccount, loadComments, loadPosts, loadProfile, profile, session.user?.id])

  function syncPost(updatedPost: CommunityPost) {
    setPosts((currentPosts) =>
      currentPosts.map((currentPost) =>
        currentPost.id === updatedPost.id ? updatedPost : currentPost,
      ),
    )
    setFavorites((currentPosts) =>
      currentPosts.map((currentPost) =>
        currentPost.id === updatedPost.id ? updatedPost : currentPost,
      ),
    )
  }

  function removePost(postId: number) {
    setPosts((currentPosts) =>
      currentPosts.filter((currentPost) => currentPost.id !== postId),
    )
    setPostsTotal((currentTotal) => Math.max(0, currentTotal - 1))
    setFavorites((currentPosts) =>
      currentPosts.filter((currentPost) => currentPost.id !== postId),
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

  const isOwnProfile =
    session.isReady && Boolean(profile && session.user?.id === profile.id)
  const memberSince = formatMonthYear(locale, profile?.joined_at ?? profile?.created_at)
  const visiblePostsCount = isOwnProfile ? postsTotal : (profile?.posts_count ?? postsTotal)
  const visibleCommentsCount = isOwnProfile
    ? commentsTotal
    : (profile?.comments_count ?? commentsTotal)

  async function handleDeleteAddress(addressId: number) {
    if (!session.token) {
      return
    }

    if (!window.confirm(messages.profile.deleteAddressConfirm)) {
      return
    }

    try {
      await deleteAddress(addressId, session.token)
      setAddresses((currentAddresses) =>
        currentAddresses.filter((address) => address.id !== addressId),
      )
    } catch (error) {
      setMessage(getErrorMessage(error))
    }
  }

  return (
    <>
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
                          <p className="text-sm uppercase tracking-[0.18em] text-primary">
                            @{profile.username}
                          </p>
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
                        <Button
                          type="button"
                          variant="outline"
                          onClick={() => setIsEditOpen(true)}
                        >
                          {messages.profile.editProfile}
                        </Button>
                      ) : (
                        <FollowButton
                          userId={profile.id}
                          initialIsFollowing={profile.is_following}
                          followerCount={profile.followers_count ?? 0}
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

                  <div className="mt-8 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
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
                </div>
              </article>

              <Tabs
                value={activeTab}
                onValueChange={setActiveTab}
                className="mt-10"
              >
                <TabsList>
                  <TabsTrigger value="posts">{messages.profile.posts}</TabsTrigger>
                  <TabsTrigger value="comments">
                    {messages.profile.comments}
                  </TabsTrigger>
                  <TabsTrigger value="favorites">
                    {messages.profile.favorites}
                  </TabsTrigger>
                  {isOwnProfile ? (
                    <TabsTrigger value="account">
                      {messages.profile.account}
                    </TabsTrigger>
                  ) : null}
                </TabsList>

                <TabsContent value="posts" className="mt-6">
                  {isLoadingPosts ? (
                    <div className="rounded-[2rem] border border-border/60 bg-card p-8 text-muted-foreground">
                      {messages.profile.loadingPosts}
                    </div>
                  ) : posts.length > 0 ? (
                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                      {posts.map((post) => (
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
                </TabsContent>

                <TabsContent value="comments" className="mt-6">
                  {isLoadingComments ? (
                    <div className="rounded-[2rem] border border-border/60 bg-card p-8 text-muted-foreground">
                      {messages.profile.loadingComments}
                    </div>
                  ) : comments.length > 0 ? (
                    <div className="space-y-4">
                      {comments.map((comment) => {
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
                                  {comment.created_at
                                    ? formatProfileDate(locale, comment.created_at) ??
                                      " "
                                    : " "}
                                </span>
                                <span>
                                  {messages.post.likesLabel.replace(
                                    "{count}",
                                    String(comment.likes_count),
                                  )}
                                </span>
                                {comment.status !== "approved" ? (
                                  <span className="rounded-full bg-primary/10 px-2 py-1 text-[10px] uppercase tracking-[0.14em] text-primary">
                                    {comment.status === "rejected" ? messages.post.rejectedBadge : messages.post.pendingBadge}
                                  </span>
                                ) : null}
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
                </TabsContent>

                <TabsContent value="favorites" className="mt-6">
                  {isLoadingFavorites ? (
                    <div className="rounded-[2rem] border border-border/60 bg-card p-8 text-muted-foreground">
                      {messages.profile.loadingFavorites}
                    </div>
                  ) : favorites.length > 0 ? (
                    <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                      {favorites.map((post) => (
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
                </TabsContent>

                {isOwnProfile ? (
                  <TabsContent value="account" className="mt-6">
                    {isLoadingAccount ? (
                      <div className="rounded-[2rem] border border-border/60 bg-card p-8 text-muted-foreground">
                        {messages.profile.loadingAccount}
                      </div>
                    ) : (
                      <div className="grid grid-cols-1 gap-8 xl:grid-cols-[1.1fr_0.9fr]">
                        <section className="rounded-[2rem] border border-border/60 bg-card p-8">
                          <div className="flex items-end justify-between gap-4">
                            <div>
                              <p className="text-sm uppercase tracking-[0.18em] text-primary">
                                {messages.profile.recentOrders}
                              </p>
                              <h2 className="mt-3 font-serif text-3xl text-foreground">
                                {messages.profile.latestActivity}
                              </h2>
                            </div>
                            <Button asChild variant="outline">
                              <Link href={getLocalizedHref(locale, "store/orders")}>
                                {messages.profile.viewAllOrders}
                              </Link>
                            </Button>
                          </div>

                          <div className="mt-6 space-y-4">
                            {recentOrders.length === 0 ? (
                              <p className="text-sm text-muted-foreground">
                                {messages.profile.noOrders}
                              </p>
                            ) : (
                              recentOrders.map((order) => (
                                <div
                                  key={order.order_number}
                                  className="rounded-3xl border border-border/60 p-5"
                                >
                                  <div className="flex flex-wrap items-center justify-between gap-4">
                                    <div>
                                      <p className="font-medium text-foreground">
                                        {order.order_number}
                                      </p>
                                      <p className="mt-1 text-sm text-muted-foreground">
                                        {order.created_at
                                          ? new Date(order.created_at).toLocaleDateString(
                                              getIntlLocale(locale),
                                            )
                                          : " "}
                                      </p>
                                    </div>
                                    <span
                                      className={`rounded-full px-3 py-1 text-xs uppercase tracking-[0.18em] ${statusClasses(order.status)}`}
                                    >
                                      {order.status}
                                    </span>
                                  </div>
                                </div>
                              ))
                            )}
                          </div>
                        </section>

                        <div className="space-y-8">
                          <section className="rounded-[2rem] border border-border/60 bg-card p-8">
                            <div className="flex items-end justify-between gap-4">
                              <div>
                                <p className="text-sm uppercase tracking-[0.18em] text-primary">
                                  {messages.profile.account}
                                </p>
                                <h2 className="mt-3 font-serif text-3xl text-foreground">
                                  {messages.profile.accountOverview}
                                </h2>
                              </div>
                            </div>

                            <div className="mt-6 space-y-3 text-sm text-muted-foreground">
                              <p>
                                <span className="text-foreground">
                                  {messages.profile.nameLabel}:
                                </span>{" "}
                                {profile.name}
                              </p>
                              <p>
                                <span className="text-foreground">
                                  {messages.profile.emailLabel}:
                                </span>{" "}
                                {session.user?.email ?? profile.email ?? " "}
                              </p>
                            </div>
                          </section>

                          <section className="rounded-[2rem] border border-border/60 bg-card p-8">
                            <div className="flex items-end justify-between gap-4">
                              <div>
                                <p className="text-sm uppercase tracking-[0.18em] text-primary">
                                  {messages.profile.savedAddresses}
                                </p>
                                <h2 className="mt-3 font-serif text-3xl text-foreground">
                                  {messages.profile.shippingBook}
                                </h2>
                              </div>
                              <Button asChild variant="outline">
                                <Link href={getLocalizedHref(locale, "account/addresses")}>
                                  {messages.profile.addNewAddress}
                                </Link>
                              </Button>
                            </div>

                            <div className="mt-6 space-y-4">
                              {addresses.length === 0 ? (
                                <p className="text-sm text-muted-foreground">
                                  {messages.profile.noAddresses}
                                </p>
                              ) : (
                                addresses.map((address) => (
                                  <div
                                    key={address.id}
                                    className="rounded-3xl border border-border/60 p-5"
                                  >
                                    <div className="flex flex-wrap items-center justify-between gap-4">
                                      <div>
                                        <div className="flex flex-wrap items-center gap-3">
                                          <p className="font-medium text-foreground">
                                            {address.label || address.recipient_name}
                                          </p>
                                          {address.is_default ? (
                                            <span className="rounded-full bg-primary/10 px-2 py-1 text-[10px] uppercase tracking-[0.18em] text-primary">
                                              {messages.profile.defaultAddress}
                                            </span>
                                          ) : null}
                                        </div>
                                        <p className="mt-2 text-sm text-muted-foreground">
                                          {address.recipient_name}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                          {address.city}, {address.country}
                                        </p>
                                      </div>
                                      <div className="flex gap-3">
                                        <Button asChild variant="outline" size="sm">
                                          <Link
                                            href={getLocalizedHref(locale, "account/addresses")}
                                          >
                                            {messages.profile.editAddress}
                                          </Link>
                                        </Button>
                                        <Button
                                          type="button"
                                          variant="ghost"
                                          size="sm"
                                          onClick={() => {
                                            void handleDeleteAddress(address.id)
                                          }}
                                        >
                                          {messages.profile.deleteAddress}
                                        </Button>
                                      </div>
                                    </div>
                                  </div>
                                ))
                              )}
                            </div>
                          </section>
                        </div>
                      </div>
                    )}
                  </TabsContent>
                ) : null}
              </Tabs>
            </>
          ) : null}
        </div>
      </section>

      {profile && isEditOpen ? (
        <EditProfileModal
          user={profile}
          onClose={() => setIsEditOpen(false)}
          onSave={(updatedProfile) => {
            const previousUsername = activeUsername

            setProfile(updatedProfile)
            setActiveUsername(updatedProfile.username)
            setMessage(messages.profile.profileUpdated)
            setIsEditOpen(false)
            void session.refreshUser().catch(() => null)

            if (updatedProfile.username !== previousUsername) {
              router.replace(
                getLocalizedHref(locale, `community/u/${updatedProfile.username}`),
              )
            }
          }}
        />
      ) : null}
    </>
  )
}
