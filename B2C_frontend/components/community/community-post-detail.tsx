"use client"

import Link from "next/link"
import { MoreHorizontal } from "lucide-react"
import { useEffect, useEffectEvent, useState, type FormEvent } from "react"
import { useRouter } from "next/navigation"

import { CommentThread } from "@/components/community/comment-thread"
import { CreatePostPanel } from "@/components/community/CreatePostPanel"
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from "@/components/ui/alert-dialog"
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar"
import { Button } from "@/components/ui/button"
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu"
import { Textarea } from "@/components/ui/textarea"
import {
  createComment,
  deleteComment,
  listComments,
  replyToComment,
  updateComment,
} from "@/lib/api/comments"
import { getErrorMessage } from "@/lib/api/client"
import {
  toggleCommentLike,
  togglePostFavorite,
  togglePostLike,
} from "@/lib/api/interactions"
import { deletePost, getPost } from "@/lib/api/posts"
import {
  formatCommunityDate,
  getCommunityPostCoverImage,
  getCommunitySupportUrl,
  getCommunityUserInitials,
  getCommunityUserName,
} from "@/lib/community-ui"
import { getLocalizedHref, type Locale, type SiteMessages } from "@/lib/i18n"
import type { CommunityComment, CommunityPost } from "@/lib/types"
import { useAuthSession } from "@/hooks/use-auth-session"

type CommunityPostDetailProps = {
  locale: Locale
  slug: string
  messages: SiteMessages["community"]
}

function updateCommentTree(
  comments: CommunityComment[],
  commentId: number,
  updater: (comment: CommunityComment) => CommunityComment,
): CommunityComment[] {
  return comments.map((comment) => {
    if (comment.id === commentId) {
      return updater(comment)
    }

    return {
      ...comment,
      replies: updateCommentTree(comment.replies, commentId, updater),
    }
  })
}

export function CommunityPostDetail({
  locale,
  slug,
  messages,
}: CommunityPostDetailProps) {
  const router = useRouter()
  const session = useAuthSession()
  const [post, setPost] = useState<CommunityPost | null>(null)
  const [comments, setComments] = useState<CommunityComment[]>([])
  const [commentText, setCommentText] = useState("")
  const [message, setMessage] = useState<string | null>(null)
  const [isLoadingPost, setIsLoadingPost] = useState(false)
  const [isLoadingComments, setIsLoadingComments] = useState(false)
  const [isSubmittingComment, setIsSubmittingComment] = useState(false)
  const [activeAction, setActiveAction] = useState<string | null>(null)
  const [isEditOpen, setIsEditOpen] = useState(false)
  const [isDeleteOpen, setIsDeleteOpen] = useState(false)

  const loadDetail = useEffectEvent(async () => {
    if (!session.isReady) {
      return
    }

    setIsLoadingPost(true)
    setMessage(null)

    try {
      const nextPost = await getPost(slug, session.token)
      setPost(nextPost)
      setIsLoadingComments(true)

      try {
        const nextComments = await listComments(nextPost.id, session.token)
        setComments(nextComments)
      } catch (error) {
        setComments([])
        setMessage(getErrorMessage(error))
      } finally {
        setIsLoadingComments(false)
      }
    } catch (error) {
      setPost(null)
      setComments([])
      setMessage(getErrorMessage(error))
    } finally {
      setIsLoadingPost(false)
    }
  })

  useEffect(() => {
    void loadDetail()
  }, [loadDetail, session.isReady, session.token, session.user?.id, slug])

  async function refreshComments(postId: number) {
    setIsLoadingComments(true)

    try {
      const nextComments = await listComments(postId, session.token)
      setComments(nextComments)
    } catch (error) {
      setMessage(getErrorMessage(error))
    } finally {
      setIsLoadingComments(false)
    }
  }

  async function handleCommentSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()

    if (!post) {
      return
    }

    if (!session.token) {
      setMessage(messages.post.loginToComment)
      return
    }

    const content = commentText.trim()

    if (!content) {
      return
    }

    setIsSubmittingComment(true)
    setMessage(null)

    try {
      const createdComment = await createComment(post.id, content, session.token)
      await loadDetail()
      setCommentText("")
      setMessage(
        createdComment.status === "approved"
          ? messages.post.commentPosted
          : messages.post.commentPending,
      )
    } catch (error) {
      setMessage(getErrorMessage(error))
    } finally {
      setIsSubmittingComment(false)
    }
  }

  const supportUrl = post ? getCommunitySupportUrl(post) : null
  const isOwner = session.user?.id === post?.user?.id

  return (
    <section className="bg-background py-14 lg:py-16">
      <div className="mx-auto max-w-7xl px-6 lg:px-8">
        <div className="mb-8">
          <Button asChild variant="ghost">
            <Link href={getLocalizedHref(locale, "community")}>
              {messages.post.backToFeed}
            </Link>
          </Button>
        </div>

        {message ? (
          <div className="mb-8 rounded-2xl border border-border/60 bg-card px-5 py-4 text-sm text-foreground">
            {message}
          </div>
        ) : null}

        {(!session.isReady || isLoadingPost) && !post ? (
          <div className="rounded-[2rem] border border-border/60 bg-card p-8 text-muted-foreground">
            {messages.post.loading}
          </div>
        ) : null}

        {session.isReady && !isLoadingPost && !post ? (
          <div className="rounded-[2rem] border border-border/60 bg-card p-8">
            <h1 className="font-serif text-3xl text-foreground">
              {messages.post.unavailable}
            </h1>
          </div>
        ) : null}

        {post ? (
          <>
            <article className="overflow-hidden rounded-[2rem] border border-border/60 bg-card">
              <div className="aspect-[16/9] w-full overflow-hidden bg-muted">
                <img
                  src={getCommunityPostCoverImage(post)}
                  alt={post.images[0]?.alt_text ?? post.title}
                  className="h-full w-full object-cover"
                />
              </div>
              <div className="space-y-8 p-8">
                <div className="flex flex-col gap-6 lg:flex-row lg:items-start lg:justify-between">
                  <div className="space-y-5">
                    <div className="flex flex-wrap gap-2">
                      {post.category ? (
                        <span className="rounded-full border border-border/70 px-3 py-1 text-xs uppercase tracking-[0.18em] text-primary">
                          {post.category.name}
                        </span>
                      ) : null}
                      <span className="rounded-full bg-primary/10 px-3 py-1 text-xs text-primary">
                        {post.status}
                      </span>
                    </div>

                    <h1 className="max-w-4xl font-serif text-4xl leading-tight text-foreground md:text-5xl">
                      {post.title}
                    </h1>

                    <Link
                      href={getLocalizedHref(
                        locale,
                        `community/profile/${post.user?.username ?? "member"}`,
                      )}
                      className="inline-flex items-center gap-3"
                    >
                      <Avatar className="size-12 border border-border/60">
                        <AvatarImage src={post.user?.avatar_url ?? undefined} />
                        <AvatarFallback>
                          {getCommunityUserInitials(post.user)}
                        </AvatarFallback>
                      </Avatar>
                      <div>
                        <p className="text-sm font-medium text-foreground">
                          {getCommunityUserName(post.user)}
                        </p>
                        <p className="text-xs text-muted-foreground">
                          @{post.user?.username ?? "member"}
                        </p>
                      </div>
                    </Link>
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

                <div className="grid gap-4 rounded-[1.5rem] border border-border/60 bg-background p-5 text-sm text-muted-foreground sm:grid-cols-2">
                  <p>
                    <span className="text-foreground">{messages.post.author}:</span>{" "}
                    {getCommunityUserName(post.user)}
                  </p>
                  <p>
                    <span className="text-foreground">{messages.post.category}:</span>{" "}
                    {post.category?.name ?? messages.post.uncategorized}
                  </p>
                  <p>
                    <span className="text-foreground">{messages.post.published}:</span>{" "}
                    {formatCommunityDate(
                      locale,
                      post.published_at ?? post.created_at,
                    ) ?? ""}
                  </p>
                  <p>
                    <span className="text-foreground">{messages.post.status}:</span>{" "}
                    {post.status}
                  </p>
                </div>

                <div className="whitespace-pre-wrap leading-8 text-foreground">
                  {post.content}
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

                <div className="flex flex-wrap items-center gap-3 border-t border-border/70 pt-6">
                  <Button
                    type="button"
                    variant={post.is_liked ? "default" : "outline"}
                    size="sm"
                    disabled={activeAction === "like"}
                    onClick={() => {
                      if (!session.token) {
                        setMessage(messages.post.signInToInteract)
                        return
                      }

                      setActiveAction("like")

                      void togglePostLike(post.id, post.is_liked, session.token)
                        .then((payload) => {
                          setPost((currentPost) =>
                            currentPost
                              ? {
                                  ...currentPost,
                                  likes_count: payload.likes_count,
                                  is_liked: payload.is_liked,
                                }
                              : currentPost,
                          )
                        })
                        .catch((error) => {
                          setMessage(getErrorMessage(error))
                        })
                        .finally(() => {
                          setActiveAction(null)
                        })
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
                      if (!session.token) {
                        setMessage(messages.post.signInToInteract)
                        return
                      }

                      setActiveAction("favorite")

                      void togglePostFavorite(
                        post.id,
                        post.is_favorited,
                        session.token,
                      )
                        .then((payload) => {
                          setPost((currentPost) =>
                            currentPost
                              ? {
                                  ...currentPost,
                                  favorites_count: payload.favorites_count,
                                  is_favorited: payload.is_favorited,
                                }
                              : currentPost,
                          )
                        })
                        .catch((error) => {
                          setMessage(getErrorMessage(error))
                        })
                        .finally(() => {
                          setActiveAction(null)
                        })
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
                </div>
              </div>
            </article>

            <div
              id="comments"
              className="mt-10 rounded-[2rem] border border-border/60 bg-card p-8"
            >
              <div className="max-w-3xl">
                <p className="text-sm uppercase tracking-[0.18em] text-primary">
                  {messages.post.comments}
                </p>
                <h2 className="mt-4 font-serif text-3xl text-foreground">
                  {messages.post.comments}
                </h2>
              </div>

              <form className="mt-8" onSubmit={handleCommentSubmit}>
                <Textarea
                  value={commentText}
                  onChange={(event) => setCommentText(event.target.value)}
                  placeholder={messages.post.commentPlaceholder}
                  className="min-h-32"
                  disabled={!session.user || isSubmittingComment}
                />
                <div className="mt-4 flex flex-wrap items-center justify-between gap-4">
                  <p className="text-sm text-muted-foreground">
                    {session.user
                      ? messages.post.commentHint
                      : messages.post.loginToComment}
                  </p>
                  <Button
                    type="submit"
                    disabled={
                      !session.user || isSubmittingComment || !commentText.trim()
                    }
                  >
                    {isSubmittingComment
                      ? messages.post.submittingComment
                      : messages.post.submitComment}
                  </Button>
                </div>
              </form>

              {isLoadingComments ? (
                <div className="mt-8 text-sm text-muted-foreground">
                  {messages.post.loadingComments}
                </div>
              ) : null}

              {!isLoadingComments && comments.length === 0 ? (
                <div className="mt-8 rounded-[1.5rem] border border-border/60 bg-background p-6">
                  <h3 className="font-serif text-2xl text-foreground">
                    {messages.post.noCommentsTitle}
                  </h3>
                  <p className="mt-3 text-muted-foreground">
                    {messages.post.noCommentsDescription}
                  </p>
                </div>
              ) : null}

              {comments.length > 0 ? (
                <div className="mt-8">
                  <CommentThread
                    comments={comments}
                    locale={locale}
                    messages={messages.post}
                    token={session.token}
                    currentUserId={session.user?.id}
                    activeAction={activeAction}
                    onReply={async (commentId, content) => {
                      if (!post || !session.token) {
                        setMessage(messages.post.loginToComment)
                        throw new Error(messages.post.loginToComment)
                      }

                      setActiveAction(`comment-reply-${commentId}`)

                      try {
                        await replyToComment(commentId, content, session.token)
                        await loadDetail()
                        setMessage(messages.post.replyAdded)
                      } catch (error) {
                        setMessage(getErrorMessage(error))
                        throw error
                      } finally {
                        setActiveAction(null)
                      }
                    }}
                    onUpdate={async (commentId, content) => {
                      if (!session.token) {
                        setMessage(messages.post.loginToComment)
                        throw new Error(messages.post.loginToComment)
                      }

                      setActiveAction(`comment-update-${commentId}`)

                      try {
                        await updateComment(commentId, content, session.token)
                        setComments((currentComments) =>
                          updateCommentTree(currentComments, commentId, (comment) => ({
                            ...comment,
                            content,
                          })),
                        )
                        setMessage(messages.post.updatedComment)
                      } catch (error) {
                        setMessage(getErrorMessage(error))
                        throw error
                      } finally {
                        setActiveAction(null)
                      }
                    }}
                    onDelete={async (commentId) => {
                      if (!post || !session.token) {
                        setMessage(messages.post.loginToComment)
                        return
                      }

                      setActiveAction(`comment-delete-${commentId}`)

                      try {
                        await deleteComment(commentId, session.token)
                        await refreshComments(post.id)
                        await loadDetail()
                        setMessage(messages.post.deletedComment)
                      } catch (error) {
                        setMessage(getErrorMessage(error))
                      } finally {
                        setActiveAction(null)
                      }
                    }}
                    onLike={async (commentId, isLiked) => {
                      if (!session.token) {
                        setMessage(messages.post.signInToInteract)
                        return
                      }

                      setActiveAction(`comment-like-${commentId}`)

                      try {
                        const payload = await toggleCommentLike(
                          commentId,
                          isLiked,
                          session.token,
                        )

                        setComments((currentComments) =>
                          updateCommentTree(currentComments, commentId, (comment) => ({
                            ...comment,
                            likes_count: payload.likes_count,
                            is_liked: payload.is_liked,
                          })),
                        )
                      } catch (error) {
                        setMessage(getErrorMessage(error))
                      } finally {
                        setActiveAction(null)
                      }
                    }}
                    onMessage={setMessage}
                  />
                </div>
              ) : null}
            </div>
          </>
        ) : null}
      </div>

      <CreatePostPanel
        locale={locale}
        messages={messages}
        token={session.token}
        open={isEditOpen}
        onOpenChange={setIsEditOpen}
        initialData={post}
        onSuccess={(updatedPost) => {
          setPost(updatedPost)
          setMessage(messages.post.updatedPost)
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
                if (!post || !session.token) {
                  return
                }

                void deletePost(post.id, session.token)
                  .then(() => {
                    setMessage(messages.post.deletedPost)
                    router.push(getLocalizedHref(locale, "community"))
                  })
                  .catch((error) => {
                    setMessage(getErrorMessage(error))
                  })
              }}
            >
              {messages.post.delete}
            </AlertDialogAction>
          </AlertDialogFooter>
        </AlertDialogContent>
      </AlertDialog>
    </section>
  )
}
