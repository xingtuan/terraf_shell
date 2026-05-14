"use client"

import Link from "next/link"

import { CommunityUserAvatar } from "@/components/community/CommunityUserAvatar"
import { Button } from "@/components/ui/button"
import type { AccountCopy } from "@/lib/account-copy"
import { getLocalizedHref, type Locale, type SiteMessages } from "@/lib/i18n"
import type {
  CommunityComment,
  CommunityUser,
  ReportRecord,
} from "@/lib/types"
import { formatAccountDate } from "@/components/account/account-utils"

type CommunityCopy = AccountCopy["community"]
type ReportStatusLabels = CommunityCopy["reportStatusLabels"]

function reportStatusClass(status: string) {
  switch (status) {
    case "resolved":
      return "border-emerald-200 bg-emerald-50 text-emerald-700"
    case "reviewed":
      return "border-sky-200 bg-sky-50 text-sky-700"
    case "dismissed":
      return "border-stone-200 bg-stone-50 text-stone-700"
    default:
      return "border-amber-200 bg-amber-50 text-amber-700"
  }
}

function reportStatusLabel(labels: ReportStatusLabels, status: string) {
  return labels[status as keyof typeof labels] ?? status
}

function reportTargetSummary(report: ReportRecord) {
  const target = report.target

  if (target && "title" in target && target.title) {
    return target.title
  }

  if (target && "content" in target && target.content) {
    return target.content
  }

  if (target && "username" in target && target.username) {
    return `${target.name} (@${target.username})`
  }

  return `${report.target_type} #${report.target_id}`
}

type AccountCommunityCommentCardProps = {
  locale: Locale
  messages: SiteMessages["community"]
  comment: CommunityComment
}

export function AccountCommunityCommentCard({
  locale,
  messages,
  comment,
}: AccountCommunityCommentCardProps) {
  const commentHref = comment.post?.slug
    ? getLocalizedHref(locale, `community/${comment.post.slug}#comment-${comment.id}`)
    : null

  return (
    <article className="rounded-[1.5rem] border border-border/60 bg-card p-5">
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div className="space-y-2">
          {commentHref ? (
            <Link
              href={commentHref}
              className="font-medium text-foreground transition-colors hover:text-primary"
            >
              {comment.post?.title}
            </Link>
          ) : (
            <p className="font-medium text-foreground">
              {messages.profile.commentWithoutPost}
            </p>
          )}
          <div className="flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
            <span>{formatAccountDate(locale, comment.created_at)}</span>
            <span>
              {messages.post.likesLabel.replace(
                "{count}",
                String(comment.likes_count),
              )}
            </span>
          </div>
        </div>
        {commentHref ? (
          <Button asChild variant="ghost" size="sm">
            <Link href={commentHref}>{messages.profile.openComment}</Link>
          </Button>
        ) : null}
      </div>
      <p className="mt-4 whitespace-pre-wrap leading-relaxed text-foreground">
        {comment.content}
      </p>
    </article>
  )
}

type AccountCommunityUserRowProps = {
  locale: Locale
  user: CommunityUser
}

export function AccountCommunityUserRow({
  locale,
  user,
}: AccountCommunityUserRowProps) {
  return (
    <Link
      href={getLocalizedHref(locale, `community/u/${user.username}`)}
      className="flex items-center gap-4 rounded-[1.5rem] border border-border/60 bg-card p-4 transition-colors hover:border-border hover:bg-background"
    >
      <CommunityUserAvatar
        user={user}
        className="size-12 border border-border/60"
        sizes="48px"
      />
      <div className="min-w-0">
        <p className="truncate font-medium text-foreground">{user.name}</p>
        <p className="mt-1 truncate text-sm text-muted-foreground">
          @{user.username}
        </p>
      </div>
    </Link>
  )
}

type AccountCommunityReportCardProps = {
  locale: Locale
  copy: CommunityCopy
  report: ReportRecord
}

export function AccountCommunityReportCard({
  locale,
  copy,
  report,
}: AccountCommunityReportCardProps) {
  const timeline = [
    {
      label: copy.reportDateLabels.created,
      value: report.created_at,
    },
    {
      label: copy.reportDateLabels.updated,
      value: report.updated_at,
    },
    {
      label: copy.reportDateLabels.reviewed,
      value: report.reviewed_at,
    },
    {
      label: copy.reportDateLabels.resolved,
      value: report.resolved_at,
    },
    {
      label: copy.reportDateLabels.dismissed,
      value: report.dismissed_at,
    },
  ].filter((item) => item.value)

  return (
    <article className="rounded-[1.5rem] border border-border/60 bg-card p-5">
      <div className="flex flex-wrap items-start justify-between gap-4">
        <div className="min-w-0 space-y-2">
          <div className="flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
            <span>
              {copy.reportIdLabel}: {report.id}
            </span>
            <span className="capitalize">{report.target_type}</span>
          </div>
          <h3 className="text-lg font-medium text-foreground">
            {reportTargetSummary(report)}
          </h3>
        </div>
        <span
          className={`rounded-full border px-3 py-1 text-xs font-medium ${reportStatusClass(report.status)}`}
        >
          {reportStatusLabel(copy.reportStatusLabels, report.status)}
        </span>
      </div>

      <dl className="mt-5 grid gap-4 text-sm sm:grid-cols-2">
        <div>
          <dt className="text-muted-foreground">{copy.reportReasonLabel}</dt>
          <dd className="mt-1 text-foreground">{report.reason}</dd>
        </div>
        <div>
          <dt className="text-muted-foreground">{copy.reportTargetLabel}</dt>
          <dd className="mt-1 text-foreground">
            {report.target_type} #{report.target_id}
          </dd>
        </div>
      </dl>

      {report.public_note ? (
        <div className="mt-5 rounded-2xl bg-background px-4 py-3">
          <p className="text-xs font-medium uppercase tracking-[0.14em] text-muted-foreground">
            {copy.reportPublicNoteLabel}
          </p>
          <p className="mt-2 whitespace-pre-wrap text-sm leading-relaxed text-foreground">
            {report.public_note}
          </p>
        </div>
      ) : null}

      {report.resolution_action === "action_taken" ? (
        <p className="mt-4 text-sm text-muted-foreground">
          {copy.reportPrivacyNotice}
        </p>
      ) : null}

      <div className="mt-5 flex flex-wrap gap-3 text-xs text-muted-foreground">
        {timeline.map((item) => (
          <span key={item.label}>
            {item.label}: {formatAccountDate(locale, item.value)}
          </span>
        ))}
      </div>
    </article>
  )
}
