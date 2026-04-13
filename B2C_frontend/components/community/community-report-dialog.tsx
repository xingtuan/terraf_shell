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

type CommunityReportDialogProps = {
  token?: string | null
  targetType: "post" | "comment"
  targetId: number
  onReported: (message: string) => void
}

export function CommunityReportDialog({
  token,
  targetType,
  targetId,
  onReported,
}: CommunityReportDialogProps) {
  const [open, setOpen] = useState(false)
  const [reason, setReason] = useState("")
  const [description, setDescription] = useState("")
  const [errorMessage, setErrorMessage] = useState<string | null>(null)
  const [isPending, startTransition] = useTransition()

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button type="button" variant="ghost" size="sm" disabled={!token}>
          Report
        </Button>
      </DialogTrigger>
      <DialogContent>
        <DialogHeader>
          <DialogTitle>Report {targetType}</DialogTitle>
          <DialogDescription>
            Submit a moderation report through the backend reporting endpoint.
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-4">
          <label className="space-y-2">
            <span className="text-sm text-foreground">Reason</span>
            <Input
              value={reason}
              onChange={(event) => setReason(event.target.value)}
              placeholder="Spam, abusive content, plagiarism..."
            />
          </label>
          <label className="space-y-2">
            <span className="text-sm text-foreground">Details</span>
            <Textarea
              value={description}
              onChange={(event) => setDescription(event.target.value)}
              className="min-h-28"
              placeholder="Optional details for the moderation team."
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
                    onReported("Report submitted successfully.")
                  })
                  .catch((error) => {
                    setErrorMessage(getErrorMessage(error))
                  })
              })
            }}
          >
            {isPending ? "Submitting..." : "Submit report"}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
