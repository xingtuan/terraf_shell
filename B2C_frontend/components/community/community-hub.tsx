"use client"

import { useEffect, useEffectEvent, useState } from "react"
import { useSearchParams } from "next/navigation"

import { PostCard } from "@/components/community/PostCard"
import { Button } from "@/components/ui/button"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import { checkThrottle, ThrottleError } from "@/lib/api/request-throttle"
import { getErrorMessage } from "@/lib/api/client"
import { listCategories, listPosts, listTags } from "@/lib/api/posts"
import { COMMUNITY_POSTS_REFRESH_EVENT } from "@/lib/community-events"
import { type Locale, type SiteMessages } from "@/lib/i18n"
import type {
  ApiPaginationMeta,
  CommunityCategory,
  CommunityPost,
  CommunityTag,
} from "@/lib/types"
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
  const [categories, setCategories] = useState<CommunityCategory[]>([])
  const [tags, setTags] = useState<CommunityTag[]>([])
  const [selectedCategory, setSelectedCategory] = useState("all")
  const [selectedTag, setSelectedTag] = useState("all")
  const [perPage, setPerPage] = useState("12")
  const [page, setPage] = useState(1)
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
        {
          ...(query ? { search: query } : {}),
          ...(selectedCategory !== "all" ? { category: selectedCategory } : {}),
          ...(selectedTag !== "all" ? { tag: selectedTag } : {}),
          sort,
          page,
          per_page: Number(perPage),
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
  }, [query, sort, page, perPage, selectedCategory, selectedTag, session.isReady])

  useEffect(() => {
    let isCancelled = false

    void Promise.all([listCategories(), listTags()])
      .then(([nextCategories, nextTags]) => {
        if (isCancelled) {
          return
        }

        setCategories(nextCategories)
        setTags(nextTags)
      })
      .catch(() => {
        if (isCancelled) {
          return
        }

        setCategories([])
        setTags([])
      })

    return () => {
      isCancelled = true
    }
  }, [])

  useEffect(() => {
    const handleRefresh = () => {
      void loadPosts()
    }

    window.addEventListener(COMMUNITY_POSTS_REFRESH_EVENT, handleRefresh)

    return () => {
      window.removeEventListener(COMMUNITY_POSTS_REFRESH_EVENT, handleRefresh)
    }
  }, [loadPosts])

  const pageCount = meta?.last_page ?? 1
  const canGoPrevious = page > 1
  const canGoNext = page < pageCount
  const visiblePages = (() => {
    const pages: number[] = []
    const start = Math.max(1, page - 2)
    const end = Math.min(pageCount, page + 2)

    for (let currentPage = start; currentPage <= end; currentPage += 1) {
      pages.push(currentPage)
    }

    return pages
  })()
  const from =
    meta && meta.total > 0 ? (meta.current_page - 1) * meta.per_page + 1 : 0
  const to =
    meta && meta.total > 0
      ? Math.min(meta.total, meta.current_page * meta.per_page)
      : 0

  return (
    <section className="bg-background py-14 lg:py-16">
      <div className="mx-auto max-w-7xl px-6 lg:px-8">
        <div className="flex flex-col gap-6">
          <div>
            <p className="text-sm uppercase tracking-[0.2em] text-primary">
              {messages.feed.eyebrow}
            </p>
            <h1 className="mt-4 font-serif text-3xl leading-tight text-foreground md:text-4xl">
              {query
                ? `${messages.feed.resultsTitlePrefix} ${query}`
                : messages.feed.title}
            </h1>
          </div>

          <div className="flex flex-wrap gap-3">
            <Button
              type="button"
              variant={sort === "latest" ? "default" : "outline"}
              onClick={() => {
                setSort("latest")
                setPage(1)
              }}
            >
              {messages.feed.latest}
            </Button>
            <Button
              type="button"
              variant={sort === "hot" ? "default" : "outline"}
              onClick={() => {
                setSort("hot")
                setPage(1)
              }}
            >
              {messages.feed.hot}
            </Button>
            <div className="w-full sm:w-48">
              <Select
                value={selectedCategory}
                onValueChange={(value) => {
                  setSelectedCategory(value)
                  setPage(1)
                }}
              >
                <SelectTrigger className="w-full">
                  <SelectValue placeholder={messages.feed.categoryFilter} />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">{messages.feed.allCategories}</SelectItem>
                  {categories.map((category) => (
                    <SelectItem key={category.id} value={category.slug}>
                      {category.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            <div className="w-full sm:w-48">
              <Select
                value={selectedTag}
                onValueChange={(value) => {
                  setSelectedTag(value)
                  setPage(1)
                }}
              >
                <SelectTrigger className="w-full">
                  <SelectValue placeholder={messages.feed.tagFilter} />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="all">{messages.feed.allTags}</SelectItem>
                  {tags.map((tag) => (
                    <SelectItem key={tag.id} value={tag.slug}>
                      {tag.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
            {(selectedCategory !== "all" || selectedTag !== "all") ? (
              <Button
                type="button"
                variant="outline"
                onClick={() => {
                  setSelectedCategory("all")
                  setSelectedTag("all")
                  setPage(1)
                }}
              >
                {messages.feed.clearFilters}
              </Button>
            ) : null}
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
          <div className="mt-6 flex flex-wrap items-center justify-between gap-3 text-sm text-muted-foreground">
            <p>{messages.feed.total.replace("{count}", String(meta.total))}</p>
            <p>
              {messages.feed.showing
                .replace("{from}", String(from))
                .replace("{to}", String(to))
                .replace("{total}", String(meta.total))}
            </p>
          </div>
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
          <>
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

            {meta && pageCount > 1 ? (
              <div className="mt-8 flex flex-wrap items-center justify-center gap-2">
                <div className="w-full sm:w-40">
                  <Select
                    value={perPage}
                    onValueChange={(value) => {
                      setPerPage(value)
                      setPage(1)
                    }}
                  >
                    <SelectTrigger className="w-full">
                      <SelectValue placeholder={messages.feed.perPage} />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectItem value="6">6 / {messages.feed.perPage}</SelectItem>
                      <SelectItem value="12">12 / {messages.feed.perPage}</SelectItem>
                      <SelectItem value="24">24 / {messages.feed.perPage}</SelectItem>
                    </SelectContent>
                  </Select>
                </div>
                <Button
                  type="button"
                  variant="outline"
                  disabled={!canGoPrevious}
                  onClick={() => {
                    if (canGoPrevious) {
                      setPage((currentPage) => currentPage - 1)
                    }
                  }}
                >
                  {messages.feed.previousPage}
                </Button>

                {visiblePages.map((pageNumber) => (
                  <Button
                    key={pageNumber}
                    type="button"
                    variant={pageNumber === page ? "default" : "outline"}
                    className="min-w-10"
                    onClick={() => setPage(pageNumber)}
                  >
                    {pageNumber}
                  </Button>
                ))}

                <Button
                  type="button"
                  variant="outline"
                  disabled={!canGoNext}
                  onClick={() => {
                    if (canGoNext) {
                      setPage((currentPage) => currentPage + 1)
                    }
                  }}
                >
                  {messages.feed.nextPage}
                </Button>
              </div>
            ) : null}
          </>
        ) : null}
      </div>
    </section>
  )
}
