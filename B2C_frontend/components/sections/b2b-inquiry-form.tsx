"use client"

import { useState, useTransition } from "react"

import { getErrorMessage } from "@/lib/api/client"
import { submitB2BInquiry } from "@/lib/api/inquiries"
import type { Locale, SiteMessages } from "@/lib/i18n"
import { Input } from "@/components/ui/input"
import { Textarea } from "@/components/ui/textarea"
import { Button } from "@/components/ui/button"

type B2BInquiryFormSectionProps = {
  locale: Locale
  content: SiteMessages["b2bPage"]["form"]
  id?: string
  sourcePage?: string
}

type SubmissionState = {
  reference: string
  success: boolean
} | null

export function B2BInquiryFormSection({
  locale,
  content,
  id = "inquiry",
  sourcePage = "b2b",
}: B2BInquiryFormSectionProps) {
  const [isPending, startTransition] = useTransition()
  const [submission, setSubmission] = useState<SubmissionState>(null)
  const [errorMessage, setErrorMessage] = useState<string | null>(null)

  const fields = content.fields
  const placeholders = content.placeholders

  return (
    <section id={id} className="bg-card py-24 lg:py-28">
      <div className="mx-auto max-w-7xl px-6 lg:px-8">
        <div className="mb-14 max-w-3xl">
          <p className="mb-4 text-sm uppercase tracking-[0.2em] text-primary">
            {content.eyebrow}
          </p>
          <h2 className="mb-5 font-serif text-3xl leading-tight text-foreground md:text-4xl lg:text-5xl">
            {content.title}
          </h2>
          <p className="text-lg leading-relaxed text-muted-foreground">
            {content.description}
          </p>
        </div>

        <div className="grid grid-cols-1 gap-8 lg:grid-cols-[0.9fr_1.1fr]">
          <div className="rounded-3xl border border-border/60 bg-background p-8">
            <p className="mb-6 text-sm uppercase tracking-[0.18em] text-primary">
              Shellfin
            </p>
            <div className="space-y-4 text-muted-foreground">
              <p>Pellet supply for raw material buying and pilot programs.</p>
              <p>Compress-moulded product development for tableware and objects.</p>
              <p>Sample handling, technical support, and future certification workflows.</p>
            </div>
            <p className="mt-8 text-sm text-muted-foreground">
              {content.disclaimer}
            </p>
          </div>

          <form
            className="rounded-3xl border border-border/60 bg-background p-8"
            onSubmit={(event) => {
              event.preventDefault()
              const form = event.currentTarget
              setSubmission(null)
              setErrorMessage(null)

              const formData = new FormData(form)
              const payload = {
                name: String(formData.get("name") ?? ""),
                company: String(formData.get("company") ?? ""),
                email: String(formData.get("email") ?? ""),
                phone: String(formData.get("phone") ?? ""),
                country: "",
                application: String(formData.get("application") ?? ""),
                volume: String(formData.get("volume") ?? ""),
                timeline: String(formData.get("timeline") ?? ""),
                message: String(formData.get("message") ?? ""),
                locale,
                sourcePage,
              }

              startTransition(() => {
                void submitB2BInquiry(payload)
                  .then((result) => {
                    setSubmission(result)
                    form.reset()
                  })
                  .catch((error) => {
                    setErrorMessage(getErrorMessage(error))
                  })
              })
            }}
          >
            <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
              <label className="space-y-2">
                <span className="text-sm text-foreground">{fields.name}</span>
                <Input name="name" placeholder={placeholders.name} required />
              </label>
              <label className="space-y-2">
                <span className="text-sm text-foreground">{fields.company}</span>
                <Input name="company" placeholder={placeholders.company} required />
              </label>
              <label className="space-y-2">
                <span className="text-sm text-foreground">{fields.email}</span>
                <Input
                  name="email"
                  type="email"
                  placeholder={placeholders.email}
                  required
                />
              </label>
              <label className="space-y-2">
                <span className="text-sm text-foreground">{fields.phone}</span>
                <Input name="phone" placeholder={placeholders.phone} />
              </label>
              <label className="space-y-2 sm:col-span-2">
                <span className="text-sm text-foreground">{fields.application}</span>
                <Input
                  name="application"
                  placeholder={placeholders.application}
                  required
                />
              </label>
              <label className="space-y-2">
                <span className="text-sm text-foreground">{fields.volume}</span>
                <Input name="volume" placeholder={placeholders.volume} required />
              </label>
              <label className="space-y-2">
                <span className="text-sm text-foreground">{fields.timeline}</span>
                <Input name="timeline" placeholder={placeholders.timeline} />
              </label>
              <label className="space-y-2 sm:col-span-2">
                <span className="text-sm text-foreground">{fields.message}</span>
                <Textarea
                  name="message"
                  placeholder={placeholders.message}
                  className="min-h-36"
                  required
                />
              </label>
            </div>

            <div className="mt-8 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
              <Button type="submit" size="lg" disabled={isPending}>
                {isPending ? `${content.submit}...` : content.submit}
              </Button>
              {errorMessage ? (
                <div className="rounded-2xl border border-destructive/30 bg-destructive/8 px-4 py-3 text-sm text-foreground">
                  <p>{errorMessage}</p>
                </div>
              ) : null}
              {submission ? (
                <div className="rounded-2xl bg-primary/8 px-4 py-3 text-sm text-foreground">
                  <p>{content.success}</p>
                  <p className="mt-1 text-muted-foreground">
                    {content.referenceLabel}: {submission.reference}
                  </p>
                </div>
              ) : null}
            </div>
          </form>
        </div>
      </div>
    </section>
  )
}
