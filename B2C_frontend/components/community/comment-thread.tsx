"use client"

import { useState } from "react"

import { CommunityReportDialog } from "@/components/community/community-report-dialog"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Textarea } from "@/components/ui/textarea"
import type { CommunityCopy } from "@/lib/community-copy"
import { getIntlLocale, type Locale } from "@/lib/i18n"
import type { CommunityComment } from "@/lib/types"

type CommentThreadProps = {
  comments: CommunityComment[]
  copy: CommunityCopy["detail"]
  locale: Locale
  token?: string | null
  activeAction?: string | null
  onReply: (commentId: number, content: string) => Promise<void>
  onUpdate: (commentId: number, content: string) => Promise<void>
  onDelete: (commentId: number) => Promise<void>
  onLike: (commentId: number, isLiked: boolean) => Promise<void>
  onMessage: (message: string) => void
  onOpenUser: (userId: number | null) => void
  depth?: number
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

function getAuthorName(comment: CommunityComment) {
  return comment.user?.name ?? comment.user?.username ?? "Community member"
}

export function CommentThread({
  comments,
  copy,
  locale,
  token,
  activeAction,
  onReply,
  onUpdate,
  onDelete,
  onLike,
  onMessage,
  onOpenUser,
  depth = 0,
}: CommentThreadProps) {
  const [replyToId, setReplyToId] = useState<number | null>(null)
  const [editId, setEditId] = useState<number | null>(null)
  const [drafts, setDrafts] = useState<Record<string, string>>({})

  return (
    <div className="space-y-4">
      {comments.map((comment) => (
        <div
          key={comment.id}
          className={depth > 0 ? "border-l border-border/60 pl-5" : undefined}
        >
          <article className="rounded-3xl border border-border/60 bg-card p-5">
            <div className="flex flex-wrap items-center gap-3 text-sm text-muted-foreground">
              <button
                type="button"
                className="font-medium text-foreground transition-colors hover:text-primary"
                onClick={() => onOpenUser(comment.user?.id ?? null)}
              >
                {getAuthorName(comment)}
              </button>
              <span>{formatDate(locale, comment.created_at)}</span>
              <span>
                {copy.repliesLabel}: {comment.replies.length}
              </span>
              <span>Likes: {comment.likes_count}</span>
              {comment.status !== "approved" ? (
                <span className="rounded-full bg-primary/10 px-3 py-1 text-xs text-primary">
                  {copy.pendingBadge}
                </span>
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
                      void onUpdate(
                        comment.id,
                        (drafts[comment.id] ?? comment.content).trim(),
                      )
                        .then(() => {
                          setEditId(null)
                        })
                        .catch(() => undefined)
                    }}
                  >
                    Save
                  </Button>
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() => setEditId(null)}
                  >
                    Cancel
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
                    onMessage(copy.loginToComment)
                    return
                  }

                  void onLike(comment.id, comment.is_liked)
                }}
              >
                {comment.is_liked ? "Unlike" : "Like"} - {comment.likes_count}
              </Button>

              <Button
                type="button"
                variant="ghost"
                size="sm"
                onClick={() => {
                  if (!token) {
                    onMessage(copy.loginToComment)
                    return
                  }

                  setReplyToId((currentReplyToId) =>
                    currentReplyToId === comment.id ? null : comment.id,
                  )
                }}
              >
                Reply
              </Button>

              {comment.can_edit ? (
                <Button
                  type="button"
                  variant="ghost"
                  size="sm"
                  onClick={() => {
                    setDrafts((currentDrafts) => ({
                      ...currentDrafts,
                      [comment.id]: comment.content,
                    }))
                    setEditId(comment.id)
                  }}
                >
                  Edit
                </Button>
              ) : null}

              {comment.can_delete ? (
                <Button
                  type="button"
                  variant="ghost"
                  size="sm"
                  onClick={() => {
                    if (!window.confirm("Delete this comment?")) {
                      return
                    }

                    void onDelete(comment.id)
                  }}
                >
                  Delete
                </Button>
              ) : null}

              {token ? (
                <CommunityReportDialog
                  token={token}
                  targetType="comment"
                  targetId={comment.id}
                  onReported={onMessage}
                />
              ) : null}
            </div>

            {replyToId === comment.id ? (
              <div className="mt-4 space-y-3 rounded-2xl bg-background p-4">
                <Input
                  value={drafts[`reply-${comment.id}`] ?? ""}
                  onChange={(event) =>
                    setDrafts((currentDrafts) => ({
                      ...currentDrafts,
                      [`reply-${comment.id}`]: event.target.value,
                    }))
                  }
                  placeholder="Write a reply..."
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
                    Submit reply
                  </Button>
                  <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={() => setReplyToId(null)}
                  >
                    Cancel
                  </Button>
                </div>
              </div>
            ) : null}
          </article>

          {comment.replies.length > 0 ? (
            <div className="mt-4">
              <CommentThread
                comments={comment.replies}
                copy={copy}
                locale={locale}
                token={token}
                activeAction={activeAction}
                onReply={onReply}
                onUpdate={onUpdate}
                onDelete={onDelete}
                onLike={onLike}
                onMessage={onMessage}
                onOpenUser={onOpenUser}
                depth={depth + 1}
              />
            </div>
          ) : null}
        </div>
      ))}
    </div>
  )
}
