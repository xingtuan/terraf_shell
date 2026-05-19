"use client"

import dynamic from "next/dynamic"
import { useParams } from "next/navigation"
import { useEffect, useState, useTransition } from "react"

import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import { ApiError, getErrorMessage } from "@/lib/api/client"
import { createPost, listCategories, listTags } from "@/lib/api/posts"
import { createRichTextDocumentFromText } from "@/lib/community-rich-text"
import { getTagName } from "@/lib/community-ui"
import {
  defaultLocale,
  getMessages,
  isValidLocale,
  type Locale,
} from "@/lib/i18n"
import type { CommunityCategory, CommunityPost, CommunityTag } from "@/lib/types"

const RichPostEditor = dynamic(
  () =>
    import("@/components/community/RichPostEditor").then(
      (module) => module.RichPostEditor,
    ),
  {
    ssr: false,
    loading: () => (
      <div className="min-h-48 animate-pulse rounded-xl border border-border/70 bg-muted/30" />
    ),
  },
)

const MAX_CONTENT_CHARACTERS = 10000

type CommunityPostComposerProps = {
  token?: string | null
  onCreated: (post: CommunityPost) => void
  onMessage: (message: string | null) => void
}

type ComposerErrors = Partial<
  Record<"title" | "content" | "funding_url", string>
>

function resolveLocale(value: unknown): Locale {
  const candidate = Array.isArray(value) ? value[0] : value

  return typeof candidate === "string" && isValidLocale(candidate)
    ? candidate
    : defaultLocale
}

function formatMessage(template: string, values: Record<string, string | number>) {
  return Object.entries(values).reduce(
    (message, [key, value]) => message.replace(`{${key}}`, String(value)),
    template,
  )
}

export function CommunityPostComposer({
  token,
  onCreated,
  onMessage,
}: CommunityPostComposerProps) {
  const params = useParams()
  const locale = resolveLocale(params.locale)
  const messages = getMessages(locale).community
  const form = messages.form
  const [categories, setCategories] = useState<CommunityCategory[]>([])
  const [tags, setTags] = useState<CommunityTag[]>([])
  const [title, setTitle] = useState("")
  const [categoryId, setCategoryId] = useState("")
  const [selectedTagIds, setSelectedTagIds] = useState<number[]>([])
  const [content, setContent] = useState("")
  const [contentJson, setContentJson] = useState<Record<string, unknown>>(
    createRichTextDocumentFromText("") as Record<string, unknown>,
  )
  const [coverImageUrl, setCoverImageUrl] = useState("")
  const [coverImagePath, setCoverImagePath] = useState("")
  const [coverImageDisk, setCoverImageDisk] = useState<string | null>(null)
  const [fundingUrl, setFundingUrl] = useState("")
  const [errors, setErrors] = useState<ComposerErrors>({})
  const [isPending, startTransition] = useTransition()

  useEffect(() => {
    let isCancelled = false

    async function loadTaxonomy() {
      try {
        const [nextCategories, nextTags] = await Promise.all([
          listCategories(locale),
          listTags(locale),
        ])

        if (isCancelled) {
          return
        }

        setCategories(nextCategories)
        setTags(nextTags.slice(0, 10))
      } catch {
        if (!isCancelled) {
          setCategories([])
          setTags([])
        }
      }
    }

    void loadTaxonomy()

    return () => {
      isCancelled = true
    }
  }, [locale])

  function resetForm() {
    setTitle("")
    setCategoryId("")
    setSelectedTagIds([])
    setContent("")
    setContentJson(createRichTextDocumentFromText("") as Record<string, unknown>)
    setCoverImageUrl("")
    setCoverImagePath("")
    setCoverImageDisk(null)
    setFundingUrl("")
    setErrors({})
  }

  if (!token) {
    return null
  }

  return (
    <div className="rounded-3xl border border-border/60 bg-card p-7">
      <p className="text-sm uppercase tracking-[0.18em] text-primary">
        {form.createTitle}
      </p>
      <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
        {form.createDescription}
      </p>

      <div className="mt-6 space-y-5">
        <div className="space-y-1.5">
          <label className="text-sm font-medium text-foreground">
            {form.titleLabel}
          </label>
          <Input
            value={title}
            onChange={(event) => setTitle(event.target.value)}
            maxLength={100}
            placeholder={form.titlePlaceholder}
          />
          {errors.title ? (
            <p className="text-xs text-destructive">{errors.title}</p>
          ) : null}
        </div>

        <div className="grid gap-4 sm:grid-cols-2">
          {categories.length > 0 ? (
            <div className="space-y-1.5">
              <label className="text-sm font-medium text-foreground">
                {form.categoryLabel}
              </label>
              <Select value={categoryId} onValueChange={setCategoryId}>
                <SelectTrigger className="w-full">
                  <SelectValue placeholder={form.categoryPlaceholder} />
                </SelectTrigger>
                <SelectContent>
                  {categories.map((category) => (
                    <SelectItem key={category.id} value={String(category.id)}>
                      {category.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          ) : null}

          <div className="space-y-1.5">
            <label className="text-sm font-medium text-foreground">
              {form.fundingLabel}
            </label>
            <Input
              value={fundingUrl}
              onChange={(event) => setFundingUrl(event.target.value)}
              placeholder={form.fundingPlaceholder}
              type="url"
            />
            {errors.funding_url ? (
              <p className="text-xs text-destructive">{errors.funding_url}</p>
            ) : null}
          </div>
        </div>

        {tags.length > 0 ? (
          <div className="space-y-2">
            <span className="text-sm font-medium text-foreground">
              {form.tagsLabel}
            </span>
            <div className="flex flex-wrap gap-2">
              {tags.map((tag) => {
                const isSelected = selectedTagIds.includes(tag.id)

                return (
                  <button
                    key={tag.id}
                    type="button"
                    className={`rounded-full border px-3 py-1 text-xs transition-colors ${
                      isSelected
                        ? "border-primary/40 bg-primary/10 text-primary"
                        : "border-border/70 text-muted-foreground hover:border-border"
                    }`}
                    onClick={() =>
                      setSelectedTagIds((ids) =>
                        isSelected
                          ? ids.filter((id) => id !== tag.id)
                          : [...ids, tag.id],
                      )
                    }
                  >
                    #{getTagName(tag, locale)}
                  </button>
                )
              })}
            </div>
          </div>
        ) : null}

        <div className="space-y-1.5">
          <label className="text-sm font-medium text-foreground">
            {form.contentLabel}
          </label>
          <RichPostEditor
            content={contentJson}
            placeholder={form.contentPlaceholder}
            maxCharacters={MAX_CONTENT_CHARACTERS}
            disabled={isPending}
            coverImageUrl={coverImageUrl}
            coverImagePath={coverImagePath}
            onChange={(nextJson, plainText) => {
              setContentJson(nextJson)
              setContent(plainText)
            }}
            onCoverImageChange={(url, path, disk) => {
              setCoverImageUrl(url)
              setCoverImagePath(path)
              setCoverImageDisk(disk ?? null)
            }}
          />
          {errors.content ? (
            <p className="text-xs text-destructive">{errors.content}</p>
          ) : null}
        </div>

        <div className="flex justify-end">
          <Button
            type="button"
            disabled={isPending}
            onClick={() => {
              onMessage(null)

              const nextErrors: ComposerErrors = {}
              const trimmedTitle = title.trim()
              const trimmedContent = content.trim()
              const trimmedFundingUrl = fundingUrl.trim()

              if (!trimmedTitle) {
                nextErrors.title = form.titleRequired
              } else if (trimmedTitle.length > 100) {
                nextErrors.title = form.titleMax
              }

              if (!trimmedContent) {
                nextErrors.content = form.contentRequired
              } else if (trimmedContent.length < 20) {
                nextErrors.content = form.contentMin
              } else if (trimmedContent.length > MAX_CONTENT_CHARACTERS) {
                nextErrors.content = formatMessage(form.contentMax, {
                  max: MAX_CONTENT_CHARACTERS,
                })
              }

              if (trimmedFundingUrl) {
                try {
                  new URL(trimmedFundingUrl)
                } catch {
                  nextErrors.funding_url = form.fundingInvalid
                }
              }

              setErrors(nextErrors)

              if (Object.keys(nextErrors).length > 0) {
                return
              }

              startTransition(() => {
                void createPost(
                  {
                    title: trimmedTitle,
                    content: trimmedContent,
                    content_json: JSON.stringify(contentJson),
                    category_id: categoryId ? Number(categoryId) : undefined,
                    tag_ids: selectedTagIds.length ? selectedTagIds : undefined,
                    cover_image_url: coverImageUrl || null,
                    cover_image_path: coverImagePath || null,
                    cover_image_disk: coverImageDisk,
                    funding_url: trimmedFundingUrl || null,
                  },
                  token,
                )
                  .then((post) => {
                    resetForm()
                    onCreated(post)
                    onMessage(
                      post.status === "approved"
                        ? form.approvedSuccess
                        : form.pendingSuccess,
                    )
                  })
                  .catch((error) => {
                    if (error instanceof ApiError) {
                      setErrors({
                        title: error.errors?.title?.[0],
                        content:
                          error.errors?.content?.[0] ??
                          error.errors?.content_json?.[0],
                        funding_url: error.errors?.funding_url?.[0],
                      })
                    }

                    onMessage(getErrorMessage(error))
                  })
              })
            }}
          >
            {isPending ? form.publishPending : form.publish}
          </Button>
        </div>
      </div>
    </div>
  )
}
