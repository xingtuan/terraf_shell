"use client"

import Link from "next/link"
import { useEffect, useState, type FormEvent } from "react"
import { useRouter } from "next/navigation"

import { CommentThread } from "@/components/community/comment-thread"
import { CommunityAuthPanel } from "@/components/community/community-auth-panel"
import { CommunityNotificationsPanel } from "@/components/community/community-notifications-panel"
import { CommunityPostEditorDialog } from "@/components/community/community-post-editor-dialog"
import { CommunityReportDialog } from "@/components/community/community-report-dialog"
import { CommunityUserSheet } from "@/components/community/community-user-sheet"
import { Button } from "@/components/ui/button"
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
import type { CommunityCopy } from "@/lib/community-copy"
import { getLocalizedHref, getIntlLocale, type Locale } from "@/lib/i18n"
import type { CommunityComment, CommunityPost } from "@/lib/types"
import { useAuthSession } from "@/hooks/use-auth-session"

type CommunityPostDetailProps = {
  locale: Locale
  slug: string
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

function getAuthorName(post: CommunityPost) {
  return post.user?.name ?? post.user?.username ?? "Community member"
}

function getCoverImage(post: CommunityPost) {
  return post.images[0]?.url ?? "/placeholder.jpg"
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
  copy,
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
  const [selectedUserId, setSelectedUserId] = useState<number | null>(null)

  useEffect(() => {
    if (!session.isReady) {
      return
    }

    let isCancelled = false

    async function loadPostAndComments() {
      setIsLoadingPost(true)
      setMessage(null)

      try {
        const nextPost = await getPost(slug, session.token)

        if (isCancelled) {
          return
        }

        setPost(nextPost)
        setIsLoadingComments(true)

        try {
          const nextComments = await listComments(nextPost.id, session.token)

          if (!isCancelled) {
            setComments(nextComments)
          }
        } catch (error) {
          if (!isCancelled) {
            setMessage(getErrorMessage(error))
          }
        } finally {
          if (!isCancelled) {
            setIsLoadingComments(false)
          }
        }
      } catch (error) {
        if (!isCancelled) {
          setPost(null)
          setComments([])
          setMessage(getErrorMessage(error))
        }
      } finally {
        if (!isCancelled) {
          setIsLoadingPost(false)
        }
      }
    }

    void loadPostAndComments()

    return () => {
      isCancelled = true
    }
  }, [slug, session.isReady, session.token, session.user?.id])

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

  async function handleLikeToggle() {
    if (!post) {
      return
    }

    if (!session.token) {
      setMessage(copy.actions.signInToInteract)
      return
    }

    setActiveAction("like")
    setMessage(null)

    try {
      const payload = await togglePostLike(post.id, post.is_liked, session.token)

      setPost((currentPost) =>
        currentPost
          ? {
              ...currentPost,
              likes_count: payload.likes_count,
              is_liked: payload.is_liked,
            }
          : currentPost,
      )
    } catch (error) {
      setMessage(getErrorMessage(error))
    } finally {
      setActiveAction(null)
    }
  }

  async function handleFavoriteToggle() {
    if (!post) {
      return
    }

    if (!session.token) {
      setMessage(copy.actions.signInToInteract)
      return
    }

    setActiveAction("favorite")
    setMessage(null)

    try {
      const payload = await togglePostFavorite(
        post.id,
        post.is_favorited,
        session.token,
      )

      setPost((currentPost) =>
        currentPost
          ? {
              ...currentPost,
              favorites_count: payload.favorites_count,
              is_favorited: payload.is_favorited,
            }
          : currentPost,
      )
    } catch (error) {
      setMessage(getErrorMessage(error))
    } finally {
      setActiveAction(null)
    }
  }

  async function handleCommentSubmit(event: FormEvent<HTMLFormElement>) {
    event.preventDefault()

    if (!post) {
      return
    }

    if (!session.token) {
      setMessage(copy.detail.loginToComment)
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
      await refreshComments(post.id)
      setCommentText("")
      setPost((currentPost) =>
        currentPost
          ? {
              ...currentPost,
              comments_count:
                createdComment.status === "approved"
                  ? currentPost.comments_count + 1
                  : currentPost.comments_count,
            }
          : currentPost,
      )
      setMessage(
        createdComment.status === "approved"
          ? copy.detail.commentPosted
          : copy.detail.commentPending,
      )
    } catch (error) {
      setMessage(getErrorMessage(error))
    } finally {
      setIsSubmittingComment(false)
    }
  }

  return (
    <section className="bg-background py-24 lg:py-28">
      <div className="mx-auto max-w-7xl px-6 lg:px-8">
        <div className="mb-8">
          <Button asChild variant="ghost">
            <Link href={getLocalizedHref(locale, "community")}>
              {copy.detail.backToFeed}
            </Link>
          </Button>
        </div>

        {message ? (
          <div className="mb-8 rounded-2xl border border-border/60 bg-card px-5 py-4 text-sm text-foreground">
            {message}
          </div>
        ) : null}

        {(!session.isReady || isLoadingPost) && !post ? (
          <div className="rounded-3xl border border-border/60 bg-card p-8 text-muted-foreground">
            {copy.detail.loadingPost}
          </div>
        ) : null}

        {session.isReady && !isLoadingPost && !post ? (
          <div className="rounded-3xl border border-border/60 bg-card p-8">
            <h2 className="font-serif text-3xl text-foreground">
              {copy.detail.loadingPost}
            </h2>
            <p className="mt-3 text-muted-foreground">
              The requested post is not available to the current viewer.
            </p>
          </div>
        ) : null}

        {post ? (
          <>
            <div className="grid grid-cols-1 gap-8 lg:grid-cols-[1.35fr_0.65fr]">
              <article className="overflow-hidden rounded-3xl border border-border/60 bg-card">
                <div className="aspect-[16/9] w-full overflow-hidden bg-muted">
                  <img
                    src={getCoverImage(post)}
                    alt={post.images[0]?.alt_text ?? post.title}
                    className="h-full w-full object-cover"
                  />
                </div>
                <div className="p-8">
                  <div className="mb-4 flex flex-wrap gap-2">
                    {post.category ? (
                      <span className="rounded-full border border-border/70 px-3 py-1 text-xs uppercase tracking-[0.18em] text-primary">
                        {post.category.name}
                      </span>
                    ) : null}
                    <span className="rounded-full bg-primary/10 px-3 py-1 text-xs text-primary">
                      {post.status}
                    </span>
                  </div>

                  <h1 className="font-serif text-4xl leading-tight text-foreground md:text-5xl">
                    {post.title}
                  </h1>

                  <div className="mt-6 grid grid-cols-1 gap-4 rounded-3xl border border-border/60 bg-background p-5 text-sm text-muted-foreground sm:grid-cols-2">
                    <p>
                      <span className="text-foreground">{copy.detail.authorLabel}:</span>{" "}
                      <button
                        type="button"
                        className="font-medium text-foreground transition-colors hover:text-primary"
                        onClick={() => setSelectedUserId(post.user?.id ?? null)}
                      >
                        {getAuthorName(post)}
                      </button>
                    </p>
                    <p>
                      <span className="text-foreground">
                        {copy.detail.categoryLabel}:
                      </span>{" "}
                      {post.category?.name ?? "Uncategorized"}
                    </p>
                    <p>
                      <span className="text-foreground">
                        {copy.detail.publishedLabel}:
                      </span>{" "}
                      {formatDate(locale, post.published_at ?? post.created_at)}
                    </p>
                    <p>
                      <span className="text-foreground">{copy.detail.statusLabel}:</span>{" "}
                      {post.status}
                    </p>
                  </div>

                  <div className="mt-8 whitespace-pre-wrap leading-8 text-foreground">
                    {post.content}
                  </div>

                  <div className="mt-8 flex flex-wrap gap-2">
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
                      disabled={!session.user || activeAction === "like"}
                      onClick={() => {
                        void handleLikeToggle()
                      }}
                    >
                      {post.is_liked ? copy.actions.unlike : copy.actions.like} -{" "}
                      {post.likes_count}
                    </Button>
                    <Button
                      type="button"
                      variant={post.is_favorited ? "default" : "outline"}
                      size="sm"
                      disabled={!session.user || activeAction === "favorite"}
                      onClick={() => {
                        void handleFavoriteToggle()
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
                    {session.token ? (
                      <CommunityReportDialog
                        token={session.token}
                        targetType="post"
                        targetId={post.id}
                        onReported={setMessage}
                      />
                    ) : null}
                    {session.token && post.can_edit ? (
                      <CommunityPostEditorDialog
                        post={post}
                        token={session.token}
                        onSaved={(updatedPost) => {
                          setPost(updatedPost)
                          setMessage("Post updated successfully.")
                        }}
                      />
                    ) : null}
                    {session.token && post.can_delete ? (
                      <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={() => {
                          if (!session.token || !window.confirm("Delete this post?")) {
                            return
                          }

                          void deletePost(post.id, session.token)
                            .then(() => {
                              setMessage("Post deleted successfully.")
                              router.push(getLocalizedHref(locale, "community"))
                            })
                            .catch((error) => {
                              setMessage(getErrorMessage(error))
                            })
                        }}
                      >
                        Delete
                      </Button>
                    ) : null}
                  </div>
                </div>
              </article>

              <div className="space-y-6">
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

                <CommunityNotificationsPanel locale={locale} token={session.token} />

                <div className="rounded-3xl border border-border/60 bg-card p-7">
                  <p className="text-sm uppercase tracking-[0.18em] text-primary">
                    {copy.detail.tagsLabel}
                  </p>
                  <div className="mt-4 flex flex-wrap gap-2">
                    {post.tags.map((tag) => (
                      <span
                        key={tag.id}
                        className="rounded-full border border-border/70 px-3 py-1 text-xs text-muted-foreground"
                      >
                        {tag.name}
                      </span>
                    ))}
                  </div>
                </div>
              </div>
            </div>

            <div
              id="comments"
              className="mt-12 rounded-3xl border border-border/60 bg-card p-8"
            >
              <div className="max-w-3xl">
                <p className="text-sm uppercase tracking-[0.18em] text-primary">
                  {copy.detail.commentsTitle}
                </p>
                <h2 className="mt-4 font-serif text-3xl text-foreground">
                  {copy.detail.commentsTitle}
                </h2>
                <p className="mt-3 text-muted-foreground">
                  {copy.detail.commentsDescription}
                </p>
              </div>

              <form className="mt-8" onSubmit={handleCommentSubmit}>
                <Textarea
                  value={commentText}
                  onChange={(event) => setCommentText(event.target.value)}
                  placeholder={copy.detail.commentPlaceholder}
                  className="min-h-32"
                  disabled={!session.user || isSubmittingComment}
                />
                <div className="mt-4 flex flex-wrap items-center justify-between gap-4">
                  <p className="text-sm text-muted-foreground">
                    {session.user
                      ? "You can reply, edit, delete, like, and report comments from this thread."
                      : copy.detail.loginToComment}
                  </p>
                  <Button
                    type="submit"
                    disabled={
                      !session.user || isSubmittingComment || !commentText.trim()
                    }
                  >
                    {isSubmittingComment
                      ? `${copy.detail.submitComment}...`
                      : copy.detail.submitComment}
                  </Button>
                </div>
              </form>

              {isLoadingComments ? (
                <div className="mt-8 text-sm text-muted-foreground">
                  {copy.detail.loadingComments}
                </div>
              ) : null}

              {!isLoadingComments && comments.length === 0 ? (
                <div className="mt-8 rounded-3xl border border-border/60 bg-background p-6">
                  <h3 className="font-serif text-2xl text-foreground">
                    {copy.detail.noCommentsTitle}
                  </h3>
                  <p className="mt-3 text-muted-foreground">
                    {copy.detail.noCommentsDescription}
                  </p>
                </div>
              ) : null}

              {comments.length > 0 ? (
                <div className="mt-8">
                  <CommentThread
                    comments={comments}
                    copy={copy.detail}
                    locale={locale}
                    token={session.token}
                    activeAction={activeAction}
                    onReply={async (commentId, content) => {
                      if (!post || !session.token) {
                        setMessage(copy.detail.loginToComment)
                        throw new Error(copy.detail.loginToComment)
                      }

                      setActiveAction(`comment-reply-${commentId}`)

                      try {
                        await replyToComment(commentId, content, session.token)
                        await refreshComments(post.id)
                        setMessage("Reply added successfully.")
                      } catch (error) {
                        setMessage(getErrorMessage(error))
                        throw error
                      } finally {
                        setActiveAction(null)
                      }
                    }}
                    onUpdate={async (commentId, content) => {
                      if (!session.token) {
                        setMessage(copy.detail.loginToComment)
                        throw new Error(copy.detail.loginToComment)
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
                        setMessage("Comment updated successfully.")
                      } catch (error) {
                        setMessage(getErrorMessage(error))
                        throw error
                      } finally {
                        setActiveAction(null)
                      }
                    }}
                    onDelete={async (commentId) => {
                      if (!post || !session.token) {
                        setMessage(copy.detail.loginToComment)
                        return
                      }

                      setActiveAction(`comment-delete-${commentId}`)

                      try {
                        await deleteComment(commentId, session.token)
                        await refreshComments(post.id)
                        setMessage("Comment deleted successfully.")
                      } catch (error) {
                        setMessage(getErrorMessage(error))
                      } finally {
                        setActiveAction(null)
                      }
                    }}
                    onLike={async (commentId, isLiked) => {
                      if (!session.token) {
                        setMessage(copy.actions.signInToInteract)
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
                    onOpenUser={setSelectedUserId}
                  />
                </div>
              ) : null}
            </div>
          </>
        ) : null}
      </div>

      <CommunityUserSheet
        locale={locale}
        userId={selectedUserId}
        currentUserId={session.user?.id}
        token={session.token}
        open={selectedUserId !== null}
        onOpenChange={(open) => {
          if (!open) {
            setSelectedUserId(null)
          }
        }}
      />
    </section>
  )
}
