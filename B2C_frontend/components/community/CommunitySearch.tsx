"use client"

import Link from "next/link"
import { Search } from "lucide-react"
import { useDeferredValue, useEffect, useState } from "react"
import { usePathname, useRouter, useSearchParams } from "next/navigation"

import { searchPosts } from "@/lib/api/search"
import { getLocalizedHref, type Locale, type SiteMessages } from "@/lib/i18n"
import type { CommunityPost } from "@/lib/types"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"

type CommunitySearchProps = {
  locale: Locale
  messages: SiteMessages["community"]["search"]
}

export function CommunitySearch({ locale, messages }: CommunitySearchProps) {
  const router = useRouter()
  const pathname = usePathname()
  const searchParams = useSearchParams()
  const [query, setQuery] = useState(searchParams.get("q") ?? "")
  const [suggestions, setSuggestions] = useState<CommunityPost[]>([])
  const [isLoading, setIsLoading] = useState(false)
  const deferredQuery = useDeferredValue(query)

  useEffect(() => {
    setQuery(searchParams.get("q") ?? "")
  }, [pathname, searchParams])

  useEffect(() => {
    const trimmedQuery = deferredQuery.trim()

    if (trimmedQuery.length < 2) {
      setSuggestions([])
      setIsLoading(false)
      return
    }

    let isCancelled = false
    const timeoutId = window.setTimeout(() => {
      setIsLoading(true)

      void searchPosts({
        q: trimmedQuery,
        type: "posts",
        per_page: 5,
      })
        .then((response) => {
          if (!isCancelled) {
            setSuggestions(response.posts)
          }
        })
        .catch(() => {
          if (!isCancelled) {
            setSuggestions([])
          }
        })
        .finally(() => {
          if (!isCancelled) {
            setIsLoading(false)
          }
        })
    }, 400)

    return () => {
      isCancelled = true
      window.clearTimeout(timeoutId)
    }
  }, [deferredQuery])

  function submitSearch(nextQuery = query) {
    const trimmedQuery = nextQuery.trim()
    const href = trimmedQuery
      ? `${getLocalizedHref(locale, "community")}?q=${encodeURIComponent(trimmedQuery)}`
      : getLocalizedHref(locale, "community")

    router.push(href)
  }

  return (
    <div className="relative w-full sm:max-w-md">
      <form
        className="flex items-center gap-2"
        onSubmit={(event) => {
          event.preventDefault()
          submitSearch()
        }}
      >
        <div className="relative flex-1">
          <Search className="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-muted-foreground" />
          <Input
            value={query}
            onChange={(event) => setQuery(event.target.value)}
            placeholder={messages.placeholder}
            className="pl-9"
          />
        </div>
        <Button type="submit" variant="outline">
          {messages.submit}
        </Button>
      </form>

      {(isLoading || suggestions.length > 0 || deferredQuery.trim().length >= 2) && (
        <div className="absolute left-0 right-0 top-[calc(100%+0.5rem)] z-50 overflow-hidden rounded-2xl border border-border/60 bg-background shadow-xl">
          <div className="border-b border-border/60 px-4 py-3 text-xs uppercase tracking-[0.18em] text-primary">
            {messages.suggestions}
          </div>
          <div className="max-h-80 overflow-y-auto p-2">
            {isLoading ? (
              <div className="px-3 py-4 text-sm text-muted-foreground">
                {messages.loading}
              </div>
            ) : suggestions.length > 0 ? (
              suggestions.map((post) => (
                <Link
                  key={post.id}
                  href={getLocalizedHref(locale, `community/${post.slug}`)}
                  className="block rounded-xl px-3 py-3 text-sm text-foreground transition-colors hover:bg-muted"
                >
                  {post.title}
                </Link>
              ))
            ) : (
              <div className="px-3 py-4 text-sm text-muted-foreground">
                {messages.noSuggestions}
              </div>
            )}
            {!isLoading && deferredQuery.trim().length >= 2 ? (
              <button
                type="button"
                className="mt-1 block w-full rounded-xl px-3 py-3 text-left text-sm text-primary transition-colors hover:bg-muted"
                onClick={() => submitSearch()}
              >
                {messages.viewResults}
              </button>
            ) : null}
          </div>
        </div>
      )}
    </div>
  )
}
