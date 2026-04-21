"use client"

import Link from "next/link"
import { MoreHorizontal } from "lucide-react"
import { useState } from "react"

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
  formatCommunityDate,
  getCommunityUserInitials,
  getCommunityUserName,
} from "@/lib/community-ui"
import { getLocalizedHref, type Locale, type SiteMessages } from "@/lib/i18n"
import type { CommunityComment } from "@/lib/types"

type CommentThreadProps = {
  comments: CommunityComment[]
  locale: Locale
  messages: SiteMessages["community"]["post"]
  token?: string | null
  currentUserId?: number | null
  activeAction?: string | null
  onReply: (commentId: number, content: string) => Promise<void>
  onUpdate: (commentId: number, content: string) => Promise<void>
  onDelete: (commentId: number) => Promise<void>
  onLike: (commentId: number, isLiked: boolean) => Promise<void>
  onMessage: (message: string) => void
  depth?: number
}

export function CommentThread({
  comments,
  locale,
  messages,
  token,
  currentUserId,
  activeAction,
  onReply,
  onUpdate,
  onDelete,
  onLike,
  onMessage,
  depth = 0,
}: CommentThreadProps) {
  const [replyToId, setReplyToId] = useState<number | null>(null)
  const [editId, setEditId] = useState<number | null>(null)
  const [deleteId, setDeleteId] = useState<number | null>(null)
  const [drafts, setDrafts] = useState<Record<string, string>>({})

  return (
    <div className="space-y-4">
      {comments.map((comment) => {
        const isOwner = currentUserId === comment.user?.id

        return (
          <div
            key={comment.id}
            id={`comment-${comment.id}`}
            className={depth > 0 ? "border-l border-border/60 pl-5" : undefined}
          >
            <article className="group rounded-[1.75rem] border border-border/60 bg-card p-5">
              <div className="flex items-start justify-between gap-4">
                <Link
                  href={getLocalizedHref(
                    locale,
                    `community/profile/${comment.user?.username ?? "member"}`,
                  )}
                  className="flex items-center gap-3"
                >
                  <Avatar className="size-10 border border-border/60">
                    <AvatarImage src={comment.user?.avatar_url ?? undefined} />
                    <AvatarFallback>
                      {getCommunityUserInitials(comment.user)}
                    </AvatarFallback>
                  </Avatar>
                  <div>
                    <p className="text-sm font-medium text-foreground">
                      {getCommunityUserName(comment.user)}
                    </p>
                    <div className="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                      <span>
                        {formatCommunityDate(locale, comment.created_at) ?? ""}
                      </span>
                      <span>
                        {messages.comments}: {comment.replies.length}
                      </span>
                      <span>
                        {messages.likesLabel.replace(
                          "{count}",
                          String(comment.likes_count),
                        )}
                      </span>
                      {comment.status !== "approved" ? (
                        <span className="rounded-full bg-primary/10 px-2 py-1 text-[10px] uppercase tracking-[0.14em] text-primary">
                          {messages.pendingBadge}
                        </span>
                      ) : null}
                    </div>
                  </div>
                </Link>

                {isOwner ? (
                  <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                      <button
                        type="button"
                        className="rounded-full border border-border/60 p-2 text-muted-foreground opacity-100 transition-[color,background-color,opacity] hover:bg-muted hover:text-foreground sm:opacity-0 sm:group-hover:opacity-100 sm:group-focus-within:opacity-100"
                        aria-label={messages.edit}
                      >
                        <MoreHorizontal className="size-4" />
                      </button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                      <DropdownMenuItem
                        onSelect={() => {
                          setDrafts((currentDrafts) => ({
                            ...currentDrafts,
                            [comment.id]: comment.content,
                          }))
                          setEditId(comment.id)
                        }}
                      >
                        {messages.edit}
                      </DropdownMenuItem>
                      <DropdownMenuItem
                        variant="destructive"
                        onSelect={() => setDeleteId(comment.id)}
                      >
                        {messages.delete}
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                ) : null}
              </div>

              {editId === comment.id ? (
                <div className="mt-4 space-y-3">
                  <Textarea
                    value={drafts[comment.id] ?? comment.content}
                    onChange={(event) =>
                      setDrafts((currentDrafts) => ({
                        ...currentDrafts,
                        [comment.id]: event.target.value,
                      }))
                    }
                    className="min-h-28"
                  />
                  <div className="flex flex-wrap gap-3">
                    <Button
                      type="button"
                      size="sm"
                      disabled={activeAction === `comment-update-${comment.id}`}
                      onClick={() => {
                        const nextContent = (drafts[comment.id] ?? "").trim()

                        if (!nextContent) {
                          return
                        }

                        void onUpdate(comment.id, nextContent)
                          .then(() => {
                            setEditId(null)
                          })
                          .catch(() => undefined)
                      }}
                    >
                      {messages.save}
                    </Button>
                    <Button
                      type="button"
                      variant="outline"
                      size="sm"
                      onClick={() => setEditId(null)}
                    >
                      {messages.cancel}
                    </Button>
                  </div>
                </div>
              ) : (
                <p className="mt-4 whitespace-pre-wrap leading-relaxed text-foreground">
                  {comment.content}
                </p>
              )}

              <div className="mt-4 flex flex-wrap gap-2">
                <Button
                  type="button"
                  variant={comment.is_liked ? "default" : "outline"}
                  size="sm"
                  disabled={activeAction === `comment-like-${comment.id}`}
                  onClick={() => {
                    if (!token) {
                      onMessage(messages.loginToComment)
                      return
                    }

                    void onLike(comment.id, comment.is_liked)
                  }}
                >
                  {comment.is_liked ? messages.unlike : messages.like}{" "}
                  {comment.likes_count}
                </Button>

                <Button
                  type="button"
                  variant="ghost"
                  size="sm"
                  onClick={() => {
                    if (!token) {
                      onMessage(messages.loginToComment)
                      return
                    }

                    setReplyToId((currentReplyToId) =>
                      currentReplyToId === comment.id ? null : comment.id,
                    )
                  }}
                >
                  {messages.reply}
                </Button>
              </div>

              {replyToId === comment.id ? (
                <div className="mt-4 space-y-3 rounded-2xl bg-background p-4">
                  <Textarea
                    value={drafts[`reply-${comment.id}`] ?? ""}
                    onChange={(event) =>
                      setDrafts((currentDrafts) => ({
                        ...currentDrafts,
                        [`reply-${comment.id}`]: event.target.value,
                      }))
                    }
                    placeholder={messages.commentPlaceholder}
                  />
                  <div className="flex flex-wrap gap-3">
                    <Button
                      type="button"
                      size="sm"
                      disabled={activeAction === `comment-reply-${comment.id}`}
                      onClick={() => {
                        const replyDraft = (drafts[`reply-${comment.id}`] ?? "").trim()

                        if (!replyDraft) {
                          return
                        }

                        void onReply(comment.id, replyDraft)
                          .then(() => {
                            setReplyToId(null)
                            setDrafts((currentDrafts) => ({
                              ...currentDrafts,
                              [`reply-${comment.id}`]: "",
                            }))
                          })
                          .catch(() => undefined)
                      }}
                    >
                      {messages.reply}
                    </Button>
                    <Button
                      type="button"
                      variant="outline"
                      size="sm"
                      onClick={() => setReplyToId(null)}
                    >
                      {messages.cancel}
                    </Button>
                  </div>
                </div>
              ) : null}
            </article>

            <AlertDialog
              open={deleteId === comment.id}
              onOpenChange={(nextOpen) => {
                if (!nextOpen) {
                  setDeleteId(null)
                }
              }}
            >
              <AlertDialogContent>
                <AlertDialogHeader>
                  <AlertDialogTitle>{messages.deleteCommentTitle}</AlertDialogTitle>
                  <AlertDialogDescription>
                    {messages.deleteCommentDescription}
                  </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                  <AlertDialogCancel>{messages.cancel}</AlertDialogCancel>
                  <AlertDialogAction
                    onClick={() => {
                      void onDelete(comment.id).finally(() => {
                        setDeleteId(null)
                      })
                    }}
                  >
                    {messages.delete}
                  </AlertDialogAction>
                </AlertDialogFooter>
              </AlertDialogContent>
            </AlertDialog>

            {comment.replies.length > 0 ? (
              <div className="mt-4">
                <CommentThread
                  comments={comment.replies}
                  locale={locale}
                  messages={messages}
                  token={token}
                  currentUserId={currentUserId}
                  activeAction={activeAction}
                  onReply={onReply}
                  onUpdate={onUpdate}
                  onDelete={onDelete}
                  onLike={onLike}
                  onMessage={onMessage}
                  depth={depth + 1}
                />
              </div>
            ) : null}
          </div>
        )
      })}
    </div>
  )
}
