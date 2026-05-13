"use client"

import { useState, type ComponentProps } from "react"

import { Button } from "@/components/ui/button"
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import { Textarea } from "@/components/ui/textarea"
import { createReport, type ReportTargetType } from "@/lib/api/reports"
import { getErrorMessage } from "@/lib/api/client"
import { dispatchCommunityAuthOpen } from "@/lib/community-events"
import { getMessages, type Locale } from "@/lib/i18n"
import { toast } from "@/hooks/use-toast"

const REPORT_REASONS = [
  "spam",
  "harassment",
  "hate_or_abuse",
  "inappropriate_content",
  "misleading_or_scam",
  "intellectual_property",
  "other",
] as const

type ReportReason = (typeof REPORT_REASONS)[number]

type ReportDialogProps = {
  locale: Locale
  token?: string | null
  targetType: ReportTargetType
  targetId: number
  buttonVariant?: ComponentProps<typeof Button>["variant"]
  buttonSize?: ComponentProps<typeof Button>["size"]
  className?: string
  onReported?: () => void
}

function getReportErrorMessage(
  error: unknown,
  duplicateReportError: string,
  selfReportError: string,
) {
  const message = getErrorMessage(error)
  const normalizedMessage = message.toLowerCase()

  if (normalizedMessage.includes("already reported")) {
    return duplicateReportError
  }

  if (
    normalizedMessage.includes("own content") ||
    normalizedMessage.includes("your own")
  ) {
    return selfReportError
  }

  return message
}

export function ReportDialog({
  locale,
  token,
  targetType,
  targetId,
  buttonVariant = "ghost",
  buttonSize = "sm",
  className,
  onReported,
}: ReportDialogProps) {
  const t = getMessages(locale).community.report
  const reasonLabels = t.reasons as Record<ReportReason, string>
  const targetLabels = t.targetLabels as Record<ReportTargetType, string>
  const [open, setOpen] = useState(false)
  const [reason, setReason] = useState<ReportReason | "">("")
  const [description, setDescription] = useState("")
  const [errorMessage, setErrorMessage] = useState<string | null>(null)
  const [isSubmitting, setIsSubmitting] = useState(false)
  const targetLabel = targetLabels[targetType]

  function handleOpenChange(nextOpen: boolean) {
    if (nextOpen && !token) {
      toast({ title: t.loginRequired })
      dispatchCommunityAuthOpen()
      return
    }

    setOpen(nextOpen)

    if (!nextOpen) {
      setErrorMessage(null)
    }
  }

  async function handleSubmit() {
    if (!token) {
      toast({ title: t.loginRequired })
      dispatchCommunityAuthOpen()
      return
    }

    if (!reason) {
      return
    }

    setIsSubmitting(true)
    setErrorMessage(null)

    try {
      await createReport(
        {
          target_type: targetType,
          target_id: targetId,
          reason,
          description: description.trim() || undefined,
        },
        token,
      )
      setOpen(false)
      setReason("")
      setDescription("")
      toast({ title: t.reportSubmitted })
      onReported?.()
    } catch (error) {
      setErrorMessage(
        getReportErrorMessage(
          error,
          t.duplicateReportError,
          t.selfReportError,
        ),
      )
    } finally {
      setIsSubmitting(false)
    }
  }

  return (
    <Dialog open={open} onOpenChange={handleOpenChange}>
      <DialogTrigger asChild>
        <Button
          type="button"
          variant={buttonVariant}
          size={buttonSize}
          className={className}
        >
          {t.button}
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-lg">
        <DialogHeader>
          <DialogTitle>
            {t.title.replace("{targetType}", targetLabel)}
          </DialogTitle>
          <DialogDescription>{t.description}</DialogDescription>
        </DialogHeader>

        <div className="space-y-4">
          <label className="space-y-2">
            <span className="text-sm font-medium text-foreground">
              {t.reasonLabel}
            </span>
            <Select
              value={reason}
              onValueChange={(value) => setReason(value as ReportReason)}
            >
              <SelectTrigger className="w-full">
                <SelectValue placeholder={t.reasonPlaceholder} />
              </SelectTrigger>
              <SelectContent>
                {REPORT_REASONS.map((option) => (
                  <SelectItem key={option} value={option}>
                    {reasonLabels[option]}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </label>

          <label className="space-y-2">
            <span className="text-sm font-medium text-foreground">
              {t.descriptionLabel}
            </span>
            <Textarea
              value={description}
              onChange={(event) => setDescription(event.target.value)}
              className="min-h-28"
              placeholder={t.descriptionPlaceholder}
              disabled={isSubmitting}
            />
          </label>

          {errorMessage ? (
            <p className="text-sm text-destructive">{errorMessage}</p>
          ) : null}
        </div>

        <DialogFooter>
          <Button
            type="button"
            disabled={isSubmitting || !reason}
            onClick={() => {
              void handleSubmit()
            }}
          >
            {isSubmitting ? t.submitting : t.submitReport}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
