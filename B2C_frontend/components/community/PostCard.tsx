"use client"

import Image from "next/image"
import Link from "next/link"
import { MoreHorizontal } from "lucide-react"
import { useState } from "react"

import { CommunityUserAvatar } from "@/components/community/CommunityUserAvatar"
import { CreatePostPanel } from "@/components/community/CreatePostPanel"
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from "@/components/ui/alert-dialog"
import { Button } from "@/components/ui/button"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import { getErrorMessage } from "@/lib/api/client"
import { togglePostFavorite, togglePostLike } from "@/lib/api/interactions"
import { deletePost } from "@/lib/api/posts"
import {
  formatCommunityDate,
  getCommunityPostCoverImage,
  getCommunityPostPreview,
  getCommunitySupportUrl,
  getCommunityUserName,
} from "@/lib/community-ui"
import { getLocalizedHref, type Locale, type SiteMessages } from "@/lib/i18n"
import type { CommunityPost } from "@/lib/types"
import { toast } from "@/hooks/use-toast"

type PostCardProps = {
  locale: Locale
  post: CommunityPost
  messages: SiteMessages["community"]
  token?: string | null
  currentUserId?: number | null
  onUpdated: (post: CommunityPost) => void
  onDeleted: (postId: number) => void
}

export function PostCard({
  locale,
  post,
  messages,
  token,
  currentUserId,
  onUpdated,
  onDeleted,
}: PostCardProps) {
  const [isEditOpen, setIsEditOpen] = useState(false)
  const [isDeleteOpen, setIsDeleteOpen] = useState(false)
  const [activeAction, setActiveAction] = useState<string | null>(null)

  const isOwner = currentUserId === post.user?.id
  const supportUrl = getCommunitySupportUrl(post)

  async function handleLikeToggle() {
    if (!token) {
      toast({
        title: messages.post.signInToInteract,
      })
      return
    }

    setActiveAction("like")

    try {
      const payload = await togglePostLike(post.id, post.is_liked, token)
      onUpdated({
        ...post,
        likes_count: payload.likes_count,
        is_liked: payload.is_liked,
      })
    } catch (error) {
      toast({
        title: getErrorMessage(error),
      })
    } finally {
      setActiveAction(null)
    }
  }

  async function handleFavoriteToggle() {
    if (!token) {
      toast({
        title: messages.post.signInToInteract,
      })
      return
    }

    setActiveAction("favorite")

    try {
      const payload = await togglePostFavorite(post.id, post.is_favorited, token)
      onUpdated({
        ...post,
        favorites_count: payload.favorites_count,
        is_favorited: payload.is_favorited,
      })
    } catch (error) {
      toast({
        title: getErrorMessage(error),
      })
    } finally {
      setActiveAction(null)
    }
  }

  return (
    <>
      <article className="overflow-hidden rounded-[2rem] border border-border/60 bg-card">
        <Link
          href={getLocalizedHref(locale, `community/${post.slug}`)}
          className="relative block aspect-[16/9] w-full overflow-hidden bg-muted"
        >
          <Image
            src={getCommunityPostCoverImage(post)}
            alt={post.images[0]?.alt_text ?? post.title}
            fill
            unoptimized
            sizes="(min-width: 1024px) 40vw, 100vw"
            className="object-cover transition-transform duration-500 hover:scale-[1.03]"
          />
        </Link>

        <div className="space-y-5 p-6">
          <div className="flex items-start justify-between gap-4">
            <div className="flex flex-wrap items-center gap-2">
              {post.category ? (
                <span className="rounded-full border border-border/70 px-3 py-1 text-xs uppercase tracking-[0.18em] text-primary">
                  {post.category.name}
                </span>
              ) : null}
              {post.is_featured ? (
                <span className="rounded-full bg-primary/10 px-3 py-1 text-xs text-primary">
                  {messages.post.featured}
                </span>
              ) : null}
              {post.is_pinned ? (
                <span className="rounded-full bg-primary/10 px-3 py-1 text-xs text-primary">
                  {messages.post.pinned}
                </span>
              ) : null}
            </div>

            {isOwner ? (
              <DropdownMenu>
                <DropdownMenuTrigger asChild>
                  <button
                    type="button"
                    className="rounded-full border border-border/60 p-2 text-muted-foreground transition-colors hover:bg-muted hover:text-foreground"
                    aria-label={messages.post.edit}
                  >
                    <MoreHorizontal className="size-4" />
                  </button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                  <DropdownMenuItem onSelect={() => setIsEditOpen(true)}>
                    {messages.post.edit}
                  </DropdownMenuItem>
                  <DropdownMenuItem
                    variant="destructive"
                    onSelect={() => setIsDeleteOpen(true)}
                  >
                    {messages.post.delete}
                  </DropdownMenuItem>
                </DropdownMenuContent>
              </DropdownMenu>
            ) : null}
          </div>

          <div className="flex items-center gap-3">
            <Link
              href={getLocalizedHref(
                locale,
                `community/u/${post.user?.username ?? "member"}`,
              )}
              className="flex items-center gap-3"
            >
              <CommunityUserAvatar
                user={post.user}
                className="size-11 border border-border/60"
                sizes="44px"
              />
              <div>
                <p className="text-sm font-medium text-foreground">
                  {getCommunityUserName(post.user)}
                </p>
                <p className="text-xs text-muted-foreground">
                  {formatCommunityDate(locale, post.created_at) ?? " "}
                </p>
              </div>
            </Link>
          </div>

          <div className="space-y-3">
            <Link
              href={getLocalizedHref(locale, `community/${post.slug}`)}
              className="block font-serif text-2xl leading-tight text-foreground transition-colors hover:text-primary"
            >
              {post.title}
            </Link>
            <p className="leading-relaxed text-muted-foreground">
              {getCommunityPostPreview(post)}
            </p>
          </div>

          {post.tags.length > 0 ? (
            <div className="flex flex-wrap gap-2">
              {post.tags.map((tag) => (
                <span
                  key={tag.id}
                  className="rounded-full border border-border/70 px-3 py-1 text-xs text-muted-foreground"
                >
                  #{tag.name}
                </span>
              ))}
            </div>
          ) : null}

          <div className="flex flex-wrap items-center gap-3 border-t border-border/70 pt-5">
            <Button
              type="button"
              variant={post.is_liked ? "default" : "outline"}
              size="sm"
              disabled={activeAction === "like"}
              onClick={() => {
                void handleLikeToggle()
              }}
            >
              {post.is_liked ? messages.post.unlike : messages.post.like} ·{" "}
              {post.likes_count}
            </Button>
            <Button
              type="button"
              variant={post.is_favorited ? "default" : "outline"}
              size="sm"
              disabled={activeAction === "favorite"}
              onClick={() => {
                void handleFavoriteToggle()
              }}
            >
              {post.is_favorited
                ? messages.post.unfavorite
                : messages.post.favorite}{" "}
              · {post.favorites_count}
            </Button>
            <span className="text-sm text-muted-foreground">
              {messages.post.comments}: {post.comments_count}
            </span>
            {supportUrl ? (
              <Button asChild size="sm">
                <a href={supportUrl} target="_blank" rel="noreferrer">
                  {messages.post.supportIdea}
                </a>
              </Button>
            ) : null}
            <Button asChild variant="ghost" size="sm">
              <Link href={getLocalizedHref(locale, `community/${post.slug}`)}>
                {messages.feed.readMore}
              </Link>
            </Button>
          </div>
        </div>
      </article>

      <CreatePostPanel
        locale={locale}
        messages={messages}
        token={token}
        open={isEditOpen}
        onOpenChange={setIsEditOpen}
        initialData={post}
        onSuccess={(updatedPost) => {
          onUpdated(updatedPost)
          toast({
            title: messages.post.updatedPost,
          })
        }}
      />

      <AlertDialog open={isDeleteOpen} onOpenChange={setIsDeleteOpen}>
        <AlertDialogContent>
          <AlertDialogHeader>
            <AlertDialogTitle>{messages.post.deletePostTitle}</AlertDialogTitle>
            <AlertDialogDescription>
              {messages.post.deletePostDescription}
            </AlertDialogDescription>
          </AlertDialogHeader>
          <AlertDialogFooter>
            <AlertDialogCancel>{messages.post.cancel}</AlertDialogCancel>
            <AlertDialogAction
              onClick={() => {
                if (!token) {
                  return
                }

                void deletePost(post.id, token)
                  .then(() => {
                    onDeleted(post.id)
                    toast({
                      title: messages.post.deletedPost,
                    })
                  })
                  .catch((error) => {
                    toast({
                      title: getErrorMessage(error),
                    })
                  })
              }}
            >
              {messages.post.delete}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </>
  )
}
