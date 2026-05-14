"use client"

import { useEffect, useState } from "react"
import Link from "next/link"

import {
  AccountCommunityCommentCard,
  AccountCommunityReportCard,
  AccountCommunityUserRow,
} from "@/components/account/account-community-cards"
import { AccountCommunitySection } from "@/components/account/account-community-section"
import {
  AccountEmptyState,
  AccountPageHeader,
  AccountPanel,
} from "@/components/account/account-ui"
import { PostCard } from "@/components/community/PostCard"
import { Button } from "@/components/ui/button"
import { getAccountCopy } from "@/lib/account-copy"
import { getErrorMessage } from "@/lib/api/client"
import { listMyReports } from "@/lib/api/reports"
import {
  getUserComments,
  getUserFavorites,
  getUserFollowers,
  getUserFollowing,
  getUserPosts,
} from "@/lib/api/users"
import { getLocalizedHref, getMessages, type Locale } from "@/lib/i18n"
import type {
  ApiPaginationMeta,
  CommunityComment,
  CommunityPost,
  CommunityUser,
  PaginatedResult,
  ReportRecord,
} from "@/lib/types"
import { useAuthSession } from "@/hooks/use-auth-session"

export type AccountCommunityListKind =
  | "posts"
  | "saved"
  | "comments"
  | "followers"
  | "following"
  | "reports"

type AccountCommunityListPageProps = {
  locale: Locale
  kind: AccountCommunityListKind
}

type CommunityListItem =
  | CommunityPost
  | CommunityComment
  | CommunityUser
  | ReportRecord

const LIST_PER_PAGE: Record<AccountCommunityListKind, number> = {
  posts: 12,
  saved: 12,
  comments: 12,
  followers: 18,
  following: 18,
  reports: 10,
}

function emptyMeta(perPage: number): ApiPaginationMeta {
  return {
    current_page: 1,
    per_page: perPage,
    total: 0,
    last_page: 1,
  }
}

function emptyResult<T>(perPage: number): PaginatedResult<T> {
  return {
    items: [],
    meta: emptyMeta(perPage),
  }
}

function appendResult<T extends { id: number | string }>(
  current: PaginatedResult<T>,
  next: PaginatedResult<T>,
): PaginatedResult<T> {
  const existingIds = new Set(current.items.map((item) => item.id))
  const nextItems = next.items.filter((item) => !existingIds.has(item.id))

  return {
    items: [...current.items, ...nextItems],
    meta: next.meta,
  }
}

export function AccountCommunityListPage({
  locale,
  kind,
}: AccountCommunityListPageProps) {
  const session = useAuthSession()
  const copy = getAccountCopy(locale)
  const messages = getMessages(locale).community
  const perPage = LIST_PER_PAGE[kind]
  const [result, setResult] = useState<PaginatedResult<CommunityListItem>>(
    emptyResult(perPage),
  )
  const [isLoading, setIsLoading] = useState(true)
  const [isLoadingMore, setIsLoadingMore] = useState(false)
  const [error, setError] = useState<string | null>(null)

  const config = {
    posts: {
      title: copy.community.postsTitle,
      description: copy.community.postsDescription,
      emptyDescription: messages.profile.noPosts,
      loadingText: messages.profile.loadingPosts,
      itemClassName: "grid grid-cols-1 gap-6 lg:grid-cols-2",
    },
    saved: {
      title: copy.community.favoritesTitle,
      description: copy.community.favoritesDescription,
      emptyDescription: messages.profile.noFavorites,
      loadingText: messages.profile.loadingFavorites,
      itemClassName: "grid grid-cols-1 gap-6 lg:grid-cols-2",
    },
    comments: {
      title: copy.community.commentsTitle,
      description: copy.community.commentsDescription,
      emptyDescription: messages.profile.noComments,
      loadingText: messages.profile.loadingComments,
      itemClassName: "space-y-4",
    },
    followers: {
      title: copy.community.followersTitle,
      description: copy.community.followersDescription,
      emptyDescription: copy.community.noFollowers,
      loadingText: copy.community.loading,
      itemClassName: "grid gap-3 sm:grid-cols-2",
    },
    following: {
      title: copy.community.followingTitle,
      description: copy.community.followingDescription,
      emptyDescription: copy.community.noFollowing,
      loadingText: copy.community.loading,
      itemClassName: "grid gap-3 sm:grid-cols-2",
    },
    reports: {
      title: copy.community.reportsTitle,
      description: copy.community.reportsDescription,
      emptyDescription: copy.community.reportsEmpty,
      loadingText: copy.community.loading,
      itemClassName: "space-y-4",
    },
  }[kind]

  async function fetchPage(page: number): Promise<PaginatedResult<CommunityListItem>> {
    const username = session.user?.username
    const token = session.token
    const params = { page, per_page: perPage }

    if (!username) {
      return emptyResult(perPage)
    }

    switch (kind) {
      case "posts":
        return getUserPosts(username, params, token)
      case "saved":
        return getUserFavorites(username, params, token)
      case "comments":
        return getUserComments(username, params, token)
      case "followers":
        return getUserFollowers(username, params, token)
      case "following":
        return getUserFollowing(username, params, token)
      case "reports":
        if (!token) {
          return emptyResult(perPage)
        }
        return listMyReports(token, params)
    }
  }

  useEffect(() => {
    if (!session.isReady) {
      return
    }

    let cancelled = false

    setIsLoading(true)
    setError(null)
    setResult(emptyResult(perPage))

    void fetchPage(1)
      .then((nextResult) => {
        if (!cancelled) setResult(nextResult)
      })
      .catch((loadError) => {
        if (!cancelled) setError(getErrorMessage(loadError))
      })
      .finally(() => {
        if (!cancelled) setIsLoading(false)
      })

    return () => {
      cancelled = true
    }
  }, [kind, perPage, session.isReady, session.token, session.user?.username])

  function syncPost(updatedPost: CommunityPost) {
    setResult((current) => ({
      ...current,
      items: current.items.map((item) =>
        item.id === updatedPost.id ? updatedPost : item,
      ),
    }))
  }

  function removePost(postId: number) {
    setResult((current) => ({
      items: current.items.filter((item) => item.id !== postId),
      meta: {
        ...current.meta,
        total: Math.max(0, current.meta.total - 1),
      },
    }))
  }

  function renderItem(item: CommunityListItem) {
    switch (kind) {
      case "posts":
      case "saved": {
        const post = item as CommunityPost

        return (
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
        )
      }
      case "comments":
        return (
          <AccountCommunityCommentCard
            key={item.id}
            locale={locale}
            messages={messages}
            comment={item as CommunityComment}
          />
        )
      case "followers":
      case "following":
        return (
          <AccountCommunityUserRow
            key={item.id}
            locale={locale}
            user={item as CommunityUser}
          />
        )
      case "reports":
        return (
          <AccountCommunityReportCard
            key={item.id}
            locale={locale}
            copy={copy.community}
            report={item as ReportRecord}
          />
        )
    }
  }

  async function loadMore() {
    if (isLoadingMore || result.items.length >= result.meta.total) {
      return
    }

    setIsLoadingMore(true)
    setError(null)

    try {
      const nextResult = await fetchPage(result.meta.current_page + 1)
      setResult((current) => appendResult(current, nextResult))
    } catch (loadError) {
      setError(getErrorMessage(loadError))
    } finally {
      setIsLoadingMore(false)
    }
  }

  return (
    <>
      <AccountPanel>
        <AccountPageHeader
          eyebrow={copy.community.eyebrow}
          title={config.title}
          description={config.description}
          actions={
            <Button asChild variant="outline">
              <Link href={getLocalizedHref(locale, "account/community")}>
                {copy.community.backToCommunityOverview}
              </Link>
            </Button>
          }
        />
      </AccountPanel>

      <AccountCommunitySection
        title={config.title}
        description={config.description}
        items={result.items}
        meta={result.meta}
        isLoading={isLoading}
        loadingText={config.loadingText}
        emptyState={
          <AccountEmptyState
            title={config.title}
            description={config.emptyDescription}
          />
        }
        renderItem={renderItem}
        onLoadMore={loadMore}
        loadingMore={isLoadingMore}
        error={error}
        viewAllLabel={copy.community.viewAll}
        loadMoreLabel={copy.community.loadMore}
        loadingMoreLabel={copy.community.loadingMore}
        showingCountLabel={copy.community.showingCount}
        noMoreItemsLabel={copy.community.noMoreItems}
        itemClassName={config.itemClassName}
        className="bg-background/70 p-6"
      />
    </>
  )
}
