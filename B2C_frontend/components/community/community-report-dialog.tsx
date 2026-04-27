"use client"

import { useState, useTransition } from "react"

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
import { Input } from "@/components/ui/input"
import { Textarea } from "@/components/ui/textarea"
import { getErrorMessage } from "@/lib/api/client"
import { submitReport } from "@/lib/api/interactions"
import { getMessages, type Locale } from "@/lib/i18n"

type CommunityReportDialogProps = {
  locale: Locale
  token?: string | null
  targetType: "post" | "comment"
  targetId: number
  onReported: (message: string) => void
}

export function CommunityReportDialog({
  locale,
  token,
  targetType,
  targetId,
  onReported,
}: CommunityReportDialogProps) {
  const t = getMessages(locale).community.report
  const [open, setOpen] = useState(false)
  const [reason, setReason] = useState("")
  const [description, setDescription] = useState("")
  const [errorMessage, setErrorMessage] = useState<string | null>(null)
  const [isPending, startTransition] = useTransition()

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button type="button" variant="ghost" size="sm" disabled={!token}>
          {t.button}
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>{t.title.replace("{targetType}", targetType)}</DialogTitle>
          <DialogDescription>{t.description}</DialogDescription>
        </DialogHeader>

        <div className="space-y-4">
          <label className="space-y-2">
            <span className="text-sm text-foreground">{t.reasonLabel}</span>
            <Input
              value={reason}
              onChange={(event) => setReason(event.target.value)}
              placeholder={t.reasonPlaceholder}
            />
          </label>
          <label className="space-y-2">
            <span className="text-sm text-foreground">{t.detailsLabel}</span>
            <Textarea
              value={description}
              onChange={(event) => setDescription(event.target.value)}
              className="min-h-28"
              placeholder={t.detailsPlaceholder}
            />
          </label>
          {errorMessage ? (
            <p className="text-sm text-destructive">{errorMessage}</p>
          ) : null}
        </div>

        <DialogFooter>
          <Button
            type="button"
            disabled={!token || !reason.trim() || isPending}
            onClick={() => {
              if (!token) {
                return
              }

              setErrorMessage(null)

              startTransition(() => {
                void submitReport(
                  {
                    target_type: targetType,
                    target_id: targetId,
                    reason: reason.trim(),
                    description: description.trim() || undefined,
                  },
                  token,
                )
                  .then(() => {
                    setOpen(false)
                    setReason("")
                    setDescription("")
                    onReported(t.reportedSuccess)
                  })
                  .catch((error) => {
                    setErrorMessage(getErrorMessage(error))
                  })
              })
            }}
          >
            {isPending ? t.submitting : t.submitReport}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
