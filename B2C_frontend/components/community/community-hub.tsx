"use client"

import Link from "next/link"
import { useEffect, useState } from "react"

import { CommunityAuthPanel } from "@/components/community/community-auth-panel"
import { Button } from "@/components/ui/button"
import { getErrorMessage } from "@/lib/api/client"
import { togglePostFavorite, togglePostLike } from "@/lib/api/interactions"
import { listPosts } from "@/lib/api/posts"
import type { CommunityCopy } from "@/lib/community-copy"
import { getLocalizedHref, getIntlLocale, type Locale } from "@/lib/i18n"
import type { ApiPaginationMeta, CommunityPost } from "@/lib/types"
import { useAuthSession } from "@/hooks/use-auth-session"

type CommunityHubProps = {
  locale: Locale
  copy: CommunityCopy
}

function formatDate(locale: Locale, value?: string | null) {
  if (!value) {
    return "Unknown date"
  }

  return new Intl.DateTimeFormat(getIntlLocale(locale), {
    dateStyle: "medium",
    timeStyle: "short",
  }).format(new Date(value))
}

function getPostPreview(post: CommunityPost) {
  const rawPreview = (post.excerpt ?? post.content ?? "").trim()

  if (rawPreview.length <= 220) {
    return rawPreview
  }

  return `${rawPreview.slice(0, 220)}...`
}

function getAuthorName(post: CommunityPost) {
  return post.user?.name ?? post.user?.username ?? "Community member"
}

function getCoverImage(post: CommunityPost) {
  return post.images[0]?.url ?? "/placeholder.jpg"
}

export function CommunityHub({ locale, copy }: CommunityHubProps) {
  const session = useAuthSession()
  const [posts, setPosts] = useState<CommunityPost[]>([])
  const [meta, setMeta] = useState<ApiPaginationMeta | null>(null)
  const [sort, setSort] = useState<"latest" | "hot">("latest")
  const [refreshTick, setRefreshTick] = useState(0)
  const [isLoadingPosts, setIsLoadingPosts] = useState(false)
  const [activeAction, setActiveAction] = useState<string | null>(null)
  const [message, setMessage] = useState<string | null>(null)

  useEffect(() => {
    if (!session.isReady) {
      return
    }

    let isCancelled = false

    async function loadFeed() {
      setIsLoadingPosts(true)
      setMessage(null)

      try {
        const response = await listPosts(
          {
            sort,
            per_page: 12,
          },
          session.token,
        )

        if (isCancelled) {
          return
        }

        setPosts(response.posts)
        setMeta(response.meta)
      } catch (error) {
        if (!isCancelled) {
          setMessage(getErrorMessage(error))
        }
      } finally {
        if (!isCancelled) {
          setIsLoadingPosts(false)
        }
      }
    }

    void loadFeed()

    return () => {
      isCancelled = true
    }
  }, [sort, refreshTick, session.isReady, session.token, session.user?.id])

  async function handleLikeToggle(post: CommunityPost) {
    if (!session.token) {
      setMessage(copy.actions.signInToInteract)
      return
    }

    setActiveAction(`like-${post.id}`)
    setMessage(null)

    try {
      const payload = await togglePostLike(post.id, post.is_liked, session.token)

      setPosts((currentPosts) =>
        currentPosts.map((currentPost) =>
          currentPost.id === post.id
            ? {
                ...currentPost,
                likes_count: payload.likes_count,
                is_liked: payload.is_liked,
              }
            : currentPost,
        ),
      )
    } catch (error) {
      setMessage(getErrorMessage(error))
    } finally {
      setActiveAction(null)
    }
  }

  async function handleFavoriteToggle(post: CommunityPost) {
    if (!session.token) {
      setMessage(copy.actions.signInToInteract)
      return
    }

    setActiveAction(`favorite-${post.id}`)
    setMessage(null)

    try {
      const payload = await togglePostFavorite(
        post.id,
        post.is_favorited,
        session.token,
      )

      setPosts((currentPosts) =>
        currentPosts.map((currentPost) =>
          currentPost.id === post.id
            ? {
                ...currentPost,
                favorites_count: payload.favorites_count,
                is_favorited: payload.is_favorited,
              }
            : currentPost,
        ),
      )
    } catch (error) {
      setMessage(getErrorMessage(error))
    } finally {
      setActiveAction(null)
    }
  }

  return (
    <section id="posts" className="bg-background py-24 lg:py-28">
      <div className="mx-auto max-w-7xl px-6 lg:px-8">
        <div className="grid grid-cols-1 gap-8 lg:grid-cols-[1.3fr_0.7fr]">
          <div>
            <p className="mb-4 text-sm uppercase tracking-[0.2em] text-primary">
              {copy.hub.eyebrow}
            </p>
            <h2 className="mb-5 font-serif text-3xl leading-tight text-foreground md:text-4xl lg:text-5xl">
              {copy.hub.title}
            </h2>
            <p className="max-w-3xl text-lg leading-relaxed text-muted-foreground">
              {copy.hub.description}
            </p>
            <div className="mt-8 flex flex-wrap gap-3">
              <Button
                type="button"
                variant={sort === "latest" ? "default" : "outline"}
                onClick={() => setSort("latest")}
              >
                {copy.hub.latestSort}
              </Button>
              <Button
                type="button"
                variant={sort === "hot" ? "default" : "outline"}
                onClick={() => setSort("hot")}
              >
                {copy.hub.hotSort}
              </Button>
              <Button
                type="button"
                variant="ghost"
                onClick={() => setRefreshTick((currentTick) => currentTick + 1)}
              >
                {copy.hub.refresh}
              </Button>
            </div>
          </div>

          <div className="space-y-6">
            <div id="community-access">
              <CommunityAuthPanel
                copy={copy.auth}
                user={session.user}
                isReady={session.isReady}
                isLoadingUser={session.isLoadingUser}
                onLogin={session.login}
                onRegister={session.register}
                onLogout={session.logout}
                onRefresh={session.refreshUser}
              />
            </div>

            <div className="rounded-3xl border border-border/60 bg-card p-7">
              <p className="text-sm uppercase tracking-[0.18em] text-primary">
                {copy.hub.backendStatusTitle}
              </p>
              <p className="mt-4 text-sm leading-relaxed text-muted-foreground">
                {copy.hub.backendStatusDescription}
              </p>
              <div className="mt-5 space-y-2 text-sm text-muted-foreground">
                <p>
                  {copy.hub.totalLabel}: {meta?.total ?? posts.length}
                </p>
                <p>{copy.hub.loginHint}</p>
              </div>
            </div>
          </div>
        </div>

        {message ? (
          <div className="mt-10 rounded-2xl border border-border/60 bg-card px-5 py-4 text-sm text-foreground">
            {message}
          </div>
        ) : null}

        {session.isReady && isLoadingPosts ? (
          <div className="mt-12 rounded-3xl border border-border/60 bg-card p-7 text-muted-foreground">
            {copy.hub.loading}
          </div>
        ) : null}

        {session.isReady && !isLoadingPosts && posts.length === 0 ? (
          <div className="mt-12 rounded-3xl border border-border/60 bg-card p-8">
            <h3 className="font-serif text-2xl text-foreground">
              {copy.hub.emptyTitle}
            </h3>
            <p className="mt-3 max-w-2xl text-muted-foreground">
              {copy.hub.emptyDescription}
            </p>
          </div>
        ) : null}

        {session.isReady && posts.length > 0 ? (
          <div className="mt-12 grid grid-cols-1 gap-6 lg:grid-cols-2">
            {posts.map((post) => (
              <article
                key={post.id}
                className="overflow-hidden rounded-3xl border border-border/60 bg-card"
              >
                <div className="aspect-[16/9] w-full overflow-hidden bg-muted">
                  <img
                    src={getCoverImage(post)}
                    alt={post.images[0]?.alt_text ?? post.title}
                    className="h-full w-full object-cover"
                    loading="lazy"
                  />
                </div>
                <div className="p-7">
                  <div className="mb-4 flex flex-wrap items-center gap-2">
                    {post.category ? (
                      <span className="rounded-full border border-border/70 px-3 py-1 text-xs uppercase tracking-[0.18em] text-primary">
                        {post.category.name}
                      </span>
                    ) : null}
                    {post.is_featured ? (
                      <span className="rounded-full bg-primary/10 px-3 py-1 text-xs text-primary">
                        Featured
                      </span>
                    ) : null}
                    {post.is_pinned ? (
                      <span className="rounded-full bg-primary/10 px-3 py-1 text-xs text-primary">
                        Pinned
                      </span>
                    ) : null}
                  </div>

                  <Link
                    href={getLocalizedHref(locale, `community/${post.slug}`)}
                    className="font-serif text-2xl text-foreground transition-colors hover:text-primary"
                  >
                    {post.title}
                  </Link>

                  <p className="mt-3 text-sm text-muted-foreground">
                    {getAuthorName(post)} - {formatDate(locale, post.created_at)}
                  </p>

                  <p className="mt-5 leading-relaxed text-muted-foreground">
                    {getPostPreview(post)}
                  </p>

                  <div className="mt-6 flex flex-wrap gap-2">
                    {post.tags.map((tag) => (
                      <span
                        key={tag.id}
                        className="rounded-full border border-border/70 px-3 py-1 text-xs text-muted-foreground"
                      >
                        #{tag.name}
                      </span>
                    ))}
                  </div>

                  <div className="mt-8 flex flex-wrap items-center gap-3 border-t border-border/70 pt-6">
                    <Button
                      type="button"
                      variant={post.is_liked ? "default" : "outline"}
                      size="sm"
                      disabled={
                        !session.user || activeAction === `like-${post.id}`
                      }
                      onClick={() => {
                        void handleLikeToggle(post)
                      }}
                    >
                      {post.is_liked ? copy.actions.unlike : copy.actions.like} -{" "}
                      {post.likes_count}
                    </Button>
                    <Button
                      type="button"
                      variant={post.is_favorited ? "default" : "outline"}
                      size="sm"
                      disabled={
                        !session.user ||
                        activeAction === `favorite-${post.id}`
                      }
                      onClick={() => {
                        void handleFavoriteToggle(post)
                      }}
                    >
                      {post.is_favorited
                        ? copy.actions.unfavorite
                        : copy.actions.favorite}{" "}
                      - {post.favorites_count}
                    </Button>
                    <span className="text-sm text-muted-foreground">
                      {copy.actions.comments}: {post.comments_count}
                    </span>
                    <Button asChild variant="ghost" size="sm">
                      <Link href={getLocalizedHref(locale, `community/${post.slug}`)}>
                        {copy.hub.readMore}
                      </Link>
                    </Button>
                  </div>
                </div>
              </article>
            ))}
          </div>
        ) : null}
      </div>
    </section>
  )
}
