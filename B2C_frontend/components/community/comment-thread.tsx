import type { CommunityCopy } from "@/lib/community-copy"
import { getIntlLocale, type Locale } from "@/lib/i18n"
import type { CommunityComment } from "@/lib/types"

type CommentThreadProps = {
  comments: CommunityComment[]
  copy: CommunityCopy["detail"]
  locale: Locale
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
  depth = 0,
}: CommentThreadProps) {
  return (
    <div className="space-y-4">
      {comments.map((comment) => (
        <div
          key={comment.id}
          className={depth > 0 ? "border-l border-border/60 pl-5" : undefined}
        >
          <article className="rounded-3xl border border-border/60 bg-card p-5">
            <div className="flex flex-wrap items-center gap-3 text-sm text-muted-foreground">
              <span className="font-medium text-foreground">
                {getAuthorName(comment)}
              </span>
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
            <p className="mt-4 whitespace-pre-wrap leading-relaxed text-foreground">
              {comment.content}
            </p>
          </article>

          {comment.replies.length > 0 ? (
            <div className="mt-4">
              <CommentThread
                comments={comment.replies}
                copy={copy}
                locale={locale}
                depth={depth + 1}
              />
            </div>
          ) : null}
        </div>
      ))}
    </div>
  )
}
