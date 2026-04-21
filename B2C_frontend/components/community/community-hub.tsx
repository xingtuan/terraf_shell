"use client"

import { useEffect, useEffectEvent, useState } from "react"
import { useSearchParams } from "next/navigation"

import { PostCard } from "@/components/community/PostCard"
import { Button } from "@/components/ui/button"
import { checkThrottle, ThrottleError } from "@/lib/api/request-throttle"
import { getErrorMessage } from "@/lib/api/client"
import { listPosts } from "@/lib/api/posts"
import { COMMUNITY_POSTS_REFRESH_EVENT } from "@/lib/community-events"
import { type Locale, type SiteMessages } from "@/lib/i18n"
import type { ApiPaginationMeta, CommunityPost } from "@/lib/types"
import { useAuthSession } from "@/hooks/use-auth-session"

type CommunityHubProps = {
  locale: Locale
  messages: SiteMessages["community"]
  initialQuery?: string
}

export function CommunityHub({
  locale,
  messages,
  initialQuery,
}: CommunityHubProps) {
  const searchParams = useSearchParams()
  const session = useAuthSession()
  const [posts, setPosts] = useState<CommunityPost[]>([])
  const [meta, setMeta] = useState<ApiPaginationMeta | null>(null)
  const [sort, setSort] = useState<"latest" | "hot">("latest")
  const [isLoading, setIsLoading] = useState(false)
  const [message, setMessage] = useState<string | null>(null)
  const [throttleCountdown, setThrottleCountdown] = useState<number | null>(null)

  const query = (searchParams.get("q") ?? initialQuery ?? "").trim()

  const loadPosts = useEffectEvent(async () => {
    if (!session.isReady) {
      return
    }

    // Check throttle before making request
    const throttleCheck = checkThrottle("/posts")
    if (!throttleCheck.allowed) {
      const waitSeconds = Math.ceil(throttleCheck.waitTime / 1000)
      setMessage(messages.feed.requestTooFast)
      setThrottleCountdown(waitSeconds)
      
      // Countdown timer
      const interval = setInterval(() => {
        setThrottleCountdown((prev) => {
          if (prev === null || prev <= 1) {
            clearInterval(interval)
            setThrottleCountdown(null)
            return null
          }
          return prev - 1
        })
      }, 1000)
      
      return
    }

    setIsLoading(true)
    setMessage(null)
    setThrottleCountdown(null)

    try {
      const response = await listPosts(
        query
          ? {
              search: query,
              sort,
              per_page: 12,
            }
          : {
              sort,
              per_page: 12,
            },
        session.token,
      )

      setPosts(response.posts)
      setMeta(response.meta)
    } catch (error) {
      const errorMessage = error instanceof ThrottleError
        ? messages.feed.requestTooFast
        : getErrorMessage(error)
      setMessage(errorMessage)
      setPosts([])
      setMeta(null)
    } finally {
      setIsLoading(false)
    }
  })

  useEffect(() => {
    if (!session.isReady) {
      return
    }

    void loadPosts()
  }, [query, sort, session.isReady])

  useEffect(() => {
    const handleRefresh = () => {
      void loadPosts()
    }

    window.addEventListener(COMMUNITY_POSTS_REFRESH_EVENT, handleRefresh)

    return () => {
      window.removeEventListener(COMMUNITY_POSTS_REFRESH_EVENT, handleRefresh)
    }
  }, [loadPosts])

  return (
    <section className="bg-background py-14 lg:py-16">
      <div className="mx-auto max-w-7xl px-6 lg:px-8">
        <div className="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
          <div>
            <p className="text-sm uppercase tracking-[0.2em] text-primary">
              {messages.feed.eyebrow}
            </p>
            <h1 className="mt-4 font-serif text-3xl leading-tight text-foreground md:text-4xl">
              {query
                ? `${messages.feed.resultsTitlePrefix} ${query}`
                : messages.feed.title}
            </h1>
            <p className="mt-3 max-w-3xl text-lg text-muted-foreground">
              {query ? messages.feed.resultsDescription : messages.feed.description}
            </p>
          </div>

          <div className="flex flex-wrap gap-3">
            <Button
              type="button"
              variant={sort === "latest" ? "default" : "outline"}
              onClick={() => setSort("latest")}
            >
              {messages.feed.latest}
            </Button>
            <Button
              type="button"
              variant={sort === "hot" ? "default" : "outline"}
              onClick={() => setSort("hot")}
            >
              {messages.feed.hot}
            </Button>
            <Button
              type="button"
              variant="ghost"
              onClick={() => {
                void loadPosts()
              }}
            >
              {messages.feed.refresh}
            </Button>
          </div>
        </div>

        {meta ? (
          <p className="mt-6 text-sm text-muted-foreground">
            {messages.feed.total.replace("{count}", String(meta.total))}
          </p>
        ) : null}

        {message ? (
          <div className="mt-8 rounded-2xl border border-border/60 bg-card px-5 py-4 text-sm text-foreground">
            <p>{message}</p>
            {throttleCountdown !== null && (
              <p className="mt-2 text-xs text-muted-foreground">
                {messages.feed.retryIn.replace(
                  "{seconds}",
                  String(throttleCountdown),
                ).replace(
                  "{plural}",
                  throttleCountdown === 1 ? "" : "s",
                )}
              </p>
            )}
          </div>
        ) : null}

        {isLoading ? (
          <div className="mt-10 rounded-[2rem] border border-border/60 bg-card p-8 text-muted-foreground">
            {messages.feed.loading}
          </div>
        ) : null}

        {!isLoading && posts.length === 0 ? (
          <div className="mt-10 rounded-[2rem] border border-border/60 bg-card p-8">
            <h2 className="font-serif text-2xl text-foreground">
              {query ? messages.feed.noResultsTitle : messages.feed.emptyTitle}
            </h2>
            <p className="mt-3 max-w-2xl text-muted-foreground">
              {query ? messages.feed.noResults : messages.feed.emptyDescription}
            </p>
          </div>
        ) : null}

        {posts.length > 0 ? (
          <div className="mt-10 grid grid-cols-1 gap-6 lg:grid-cols-2">
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
                      currentPost.id === updatedPost.id ? updatedPost : currentPost,
                    ),
                  )
                }}
                onDeleted={(postId) => {
                  setPosts((currentPosts) =>
                    currentPosts.filter((currentPost) => currentPost.id !== postId),
                  )
                  setMeta((currentMeta) =>
                    currentMeta
                      ? {
                          ...currentMeta,
                          total: Math.max(0, currentMeta.total - 1),
                        }
                      : currentMeta,
                  )
                }}
              />
            ))}
          </div>
        ) : null}
      </div>
    </section>
  )
}
