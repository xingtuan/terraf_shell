"use client"

import Link from "next/link"
import Image from "next/image"
import { Download, ExternalLink, MoreHorizontal } from "lucide-react"
import { useEffect, useEffectEvent, useState, type FormEvent, type ReactNode } from "react"
import { useRouter } from "next/navigation"

import { CommunityUserAvatar } from "@/components/community/CommunityUserAvatar"
import { CommentThread } from "@/components/community/comment-thread"
import { CreatePostPanel } from "@/components/community/CreatePostPanel"
import { AlertDialog, AlertDialogAction, AlertDialogCancel, AlertDialogContent, AlertDialogDescription, AlertDialogFooter, AlertDialogHeader, AlertDialogTitle } from "@/components/ui/alert-dialog"
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
  updateComment,
} from "@/lib/api/comments"
import { PostRenderer } from "@/components/community/PostRenderer"
import { getErrorMessage } from "@/lib/api/client"
import {
  toggleCommentLike,
  togglePostFavorite,
  togglePostLike,
} from "@/lib/api/interactions"
import { deletePost, getPost } from "@/lib/api/posts"
import {
  formatCommunityDate,
  formatCommunityFileSize,
  getCommunityPostCoverImage,
  getCommunitySupportUrl,
  getCommunityUserName,
} from "@/lib/community-ui"
import { getLocalizedHref, type Locale, type SiteMessages } from "@/lib/i18n"
import type { CommunityComment, CommunityMedia, CommunityPost } from "@/lib/types"
import { useAuthSession } from "@/hooks/use-auth-session"
import { toast } from "@/hooks/use-toast"

type CommunityPostDetailProps = {
  locale: Locale
  slug: string
  messages: SiteMessages["community"]
  initialPost?: CommunityPost | null
  children?: ReactNode
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
      replies: updateCommentTree(comment.replies ?? [], commentId, updater),
    }
  })
}

function getAttachmentName(media: CommunityMedia) {
  return media.title ?? media.original_name ?? media.file_name ?? "Attachment"
}

function getAttachmentDownloadName(media: CommunityMedia) {
  return media.original_name ?? media.file_name ?? getAttachmentName(media)
}

function getAttachmentType(media: CommunityMedia) {
  return (
    media.extension?.toUpperCase() ??
    media.mime_type?.split("/")[1]?.toUpperCase() ??
    "FILE"
  )
}

export function CommunityPostDetail({
  locale,
  slug,
  messages,
  initialPost = null,
  children,
}: CommunityPostDetailProps) {
  const router = useRouter()
  const session = useAuthSession()
  const [post, setPost] = useState<CommunityPost | null>(initialPost)
  const [comments, setComments] = useState<CommunityComment[]>([])
  const [commentText, setCommentText] = useState("")
  const [message, setMessage] = useState<string | null>(null)
  const [isLoadingPost, setIsLoadingPost] = useState(false)
  const [isLoadingComments, setIsLoadingComments] = useState(false)
  const [isSubmittingComment, setIsSubmittingComment] = useState(false)
  const [activeAction, setActiveAction] = useState<string | null>(null)
  const [isEditOpen, setIsEditOpen] = useState(false)
  const [isDeleteOpen, setIsDeleteOpen] = useState(false)

  function notify(title: string, description?: string) {
    toast({
      title,
      ...(description ? { description } : {}),
    })
  }

  const loadDetail = useEffectEvent(async () => {
    if (!session.isReady) {
      return
    }

    setIsLoadingPost(true)
    setMessage(null)

    try {
      const nextPost = await getPost(slug, { token: session.token })
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
    if (!session.isReady) {
      return
    }

    void loadDetail()
  }, [session.isReady, slug])

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
      notify(messages.post.loginToComment)
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
      setCommentText("")
      await refreshComments(post.id)
      notify(
        createdComment.status === "approved"
          ? messages.post.commentPosted
          : messages.post.commentPending,
      )
    } catch (error) {
      notify(getErrorMessage(error))
    } finally {
      setIsSubmittingComment(false)
    }
  }

  const supportUrl = post ? getCommunitySupportUrl(post) : null
  const isOwner = session.user?.id === post?.user?.id
  const downloadableAttachments =
    post?.media?.filter((item) => !item.is_image && !item.is_external) ?? []
  const externalMedia =
    post?.media?.filter((item) => item.is_external) ?? []

  function handleAttachmentDownload(mediaId: number) {
    setPost((currentPost) =>
      currentPost
        ? {
            ...currentPost,
            media: currentPost.media?.map((item) =>
              item.id === mediaId
                ? {
                    ...item,
                    download_count: Number(item.download_count ?? 0) + 1,
                  }
                : item,
            ),
          }
        : currentPost,
    )
  }

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
              {(post.cover_image_url ||
                post.images[0]?.url ||
                post.media?.some((item) => item.media_type === "image")) ? (
                <div className="relative aspect-video w-full overflow-hidden bg-muted">
                  <Image
                    src={getCommunityPostCoverImage(post)}
                    alt={post.images[0]?.alt_text ?? post.title}
                    fill
                    unoptimized
                    sizes="(min-width: 1280px) 1152px, 100vw"
                    className="object-cover"
                  />
                </div>
              ) : null}
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
                        `community/u/${post.user?.username ?? "member"}`,
                      )}
                      className="inline-flex items-center gap-3"
                    >
                      <CommunityUserAvatar
                        user={post.user}
                        className="size-12 border border-border/60"
                        sizes="48px"
                      />
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
                    {formatCommunityDate(locale, post.published_at ?? post.created_at) ?? ""}
                    {post.reading_time ? ` | ${post.reading_time} min read` : ""}
                  </p>
                  <p>
                    <span className="text-foreground">{messages.post.status}:</span>{" "}
                    {post.status}
                  </p>
                </div>

                {children ? (
                  <div className="leading-8 text-foreground">{children}</div>
                ) : (
                  <PostRenderer
                    contentJson={post.content_json}
                    content={post.content}
                  />
                )}

                {/* Additional images gallery (beyond cover) */}
                {post.images.length > 1 ? (
                  <div className="grid grid-cols-2 gap-3 sm:grid-cols-3">
                    {post.images.slice(1).map((image) => (
                      <a
                        key={image.id}
                        href={image.url}
                        target="_blank"
                        rel="noreferrer"
                        className="block overflow-hidden rounded-2xl border border-border/60 bg-muted"
                      >
                        <Image
                          src={image.thumbnail_url ?? image.preview_url ?? image.url}
                          alt={image.alt_text ?? post.title}
                          width={400}
                          height={300}
                          unoptimized
                          className="aspect-[4/3] w-full object-cover transition-opacity hover:opacity-90"
                        />
                      </a>
                    ))}
                  </div>
                ) : null}

                {downloadableAttachments.length > 0 ? (
                  <div className="space-y-3">
                    <div className="flex items-center justify-between gap-3">
                      <h2 className="text-sm font-semibold uppercase tracking-[0.18em] text-foreground">
                        Attachments
                      </h2>
                      <span className="text-xs text-muted-foreground">
                        {downloadableAttachments.length} file
                        {downloadableAttachments.length > 1 ? "s" : ""}
                      </span>
                    </div>
                    <div className="space-y-2">
                      {downloadableAttachments.map((media) => (
                        <a
                          key={media.id}
                          href={media.download_url ?? media.url ?? undefined}
                          target="_blank"
                          rel="noreferrer"
                          download={getAttachmentDownloadName(media)}
                          onClick={() => handleAttachmentDownload(media.id)}
                          className="flex items-center gap-3 rounded-2xl border border-border/60 bg-background px-4 py-3 text-sm transition-colors hover:bg-muted"
                        >
                          <span className="shrink-0 rounded-lg border border-border/60 bg-muted px-2 py-1 text-xs uppercase text-muted-foreground">
                            {getAttachmentType(media)}
                          </span>
                          <span className="min-w-0 flex-1">
                            <span className="block truncate font-medium text-foreground">
                              {getAttachmentName(media)}
                            </span>
                            <span className="mt-1 flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                              {formatCommunityFileSize(media.size_bytes) ? (
                                <span>{formatCommunityFileSize(media.size_bytes)}</span>
                              ) : null}
                              <span>{media.download_count ?? 0} downloads</span>
                            </span>
                          </span>
                          <span className="inline-flex shrink-0 items-center gap-2 text-xs font-medium text-foreground">
                            <Download className="size-4" />
                            Download
                          </span>
                        </a>
                      ))}
                    </div>
                  </div>
                ) : null}

                {externalMedia.length > 0 ? (
                  <div className="space-y-3">
                    <div className="flex items-center justify-between gap-3">
                      <h2 className="text-sm font-semibold uppercase tracking-[0.18em] text-foreground">
                        External Links
                      </h2>
                      <span className="text-xs text-muted-foreground">
                        {externalMedia.length} link{externalMedia.length > 1 ? "s" : ""}
                      </span>
                    </div>
                    <div className="space-y-2">
                      {externalMedia.map((media) => (
                        <a
                          key={media.id}
                          href={media.external_url ?? media.url ?? undefined}
                          target="_blank"
                          rel="noreferrer"
                          className="flex items-center gap-3 rounded-2xl border border-border/60 bg-background px-4 py-3 text-sm transition-colors hover:bg-muted"
                        >
                          <span className="shrink-0 rounded-lg border border-border/60 bg-muted px-2 py-1 text-xs uppercase text-muted-foreground">
                            LINK
                          </span>
                          <span className="min-w-0 flex-1 truncate text-foreground">
                            {getAttachmentName(media)}
                          </span>
                          <span className="inline-flex shrink-0 items-center gap-2 text-xs font-medium text-foreground">
                            <ExternalLink className="size-4" />
                            Open
                          </span>
                        </a>
                      ))}
                    </div>
                  </div>
                ) : null}

                {/* Funding / support banner */}
                {post.funding_url ? (
                  <div className="flex flex-col gap-4 rounded-[1.5rem] border border-primary/30 bg-primary/5 p-5 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                      <p className="text-sm font-medium text-foreground">Support this idea</p>
                      <p className="mt-1 text-xs text-muted-foreground truncate max-w-sm">{post.funding_url}</p>
                    </div>
                    <a
                      href={post.funding_url}
                      target="_blank"
                      rel="noreferrer"
                      className="inline-flex shrink-0 items-center justify-center rounded-xl bg-primary px-5 py-2.5 text-sm font-medium text-primary-foreground transition-opacity hover:opacity-90"
                    >
                      {messages.post.supportIdea}
                    </a>
                  </div>
                ) : null}

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
                        notify(messages.post.loginToComment)
                        throw new Error(messages.post.loginToComment)
                      }

                      setActiveAction(`comment-reply-${commentId}`)

                      try {
                        const createdReply = await createComment(
                          post.id,
                          content,
                          session.token,
                          commentId,
                        )
                        await refreshComments(post.id)
                        notify(
                          createdReply.status === "approved"
                            ? messages.post.replyAdded
                            : messages.post.commentPending,
                        )
                      } catch (error) {
                        notify(getErrorMessage(error))
                        throw error
                      } finally {
                        setActiveAction(null)
                      }
                    }}
                    onUpdate={async (commentId, content) => {
                      if (!session.token) {
                        notify(messages.post.loginToComment)
                        throw new Error(messages.post.loginToComment)
                      }

                      setActiveAction(`comment-update-${commentId}`)

                      try {
                        const updatedComment = await updateComment(
                          commentId,
                          content,
                          session.token,
                        )
                        setComments((currentComments) =>
                          updateCommentTree(currentComments, commentId, (comment) => ({
                            ...comment,
                            body: updatedComment.body,
                            content: updatedComment.content,
                            status: updatedComment.status,
                            updated_at: updatedComment.updated_at,
                          })),
                        )
                        notify(
                          messages.post.updatedComment,
                          updatedComment.status === "approved"
                            ? undefined
                            : messages.post.commentPending,
                        )
                      } catch (error) {
                        notify(getErrorMessage(error))
                        throw error
                      } finally {
                        setActiveAction(null)
                      }
                    }}
                    onDelete={async (commentId) => {
                      if (!post || !session.token) {
                        notify(messages.post.loginToComment)
                        return
                      }

                      setActiveAction(`comment-delete-${commentId}`)

                      try {
                        await deleteComment(commentId, session.token)
                        await refreshComments(post.id)
                        notify(messages.post.deletedComment)
                      } catch (error) {
                        notify(getErrorMessage(error))
                      } finally {
                        setActiveAction(null)
                      }
                    }}
                    onLike={async (commentId, isLiked) => {
                      if (!session.token) {
                        notify(messages.post.signInToInteract)
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
                        notify(getErrorMessage(error))
                      } finally {
                        setActiveAction(null)
                      }
                    }}
                    onMessage={(nextMessage) => {
                      notify(nextMessage)
                    }}
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
          router.refresh()
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
