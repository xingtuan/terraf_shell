"use client"

import dynamic from "next/dynamic"
import { useParams } from "next/navigation"
import { useEffect, useState, useTransition } from "react"

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
import { getErrorMessage } from "@/lib/api/client"
import { listCategories, listTags, updatePost } from "@/lib/api/posts"
import {
  createRichTextDocumentFromText,
  isRichTextDocument,
} from "@/lib/community-rich-text"
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

type CommunityPostEditorDialogProps = {
  post: CommunityPost
  token: string
  onSaved: (post: CommunityPost) => void
}

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

function getInitialContentJson(post: CommunityPost): Record<string, unknown> {
  return (
    isRichTextDocument(post.content_json)
      ? post.content_json
      : createRichTextDocumentFromText(post.content)
  ) as Record<string, unknown>
}

export function CommunityPostEditorDialog({
  post,
  token,
  onSaved,
}: CommunityPostEditorDialogProps) {
  const params = useParams()
  const locale = resolveLocale(params.locale)
  const messages = getMessages(locale).community
  const form = messages.form
  const [open, setOpen] = useState(false)
  const [title, setTitle] = useState(post.title)
  const [content, setContent] = useState(post.content)
  const [contentJson, setContentJson] = useState<Record<string, unknown>>(
    getInitialContentJson(post),
  )
  const [excerpt, setExcerpt] = useState(post.excerpt ?? "")
  const [fundingUrl, setFundingUrl] = useState(post.funding_url ?? "")
  const [categoryId, setCategoryId] = useState(
    post.category_id ? String(post.category_id) : "",
  )
  const [selectedTagIds, setSelectedTagIds] = useState<number[]>(
    post.tags.map((tag) => tag.id),
  )
  const [categories, setCategories] = useState<CommunityCategory[]>([])
  const [tags, setTags] = useState<CommunityTag[]>([])
  const [message, setMessage] = useState<string | null>(null)
  const [isPending, startTransition] = useTransition()

  useEffect(() => {
    if (!open) {
      return
    }

    setTitle(post.title)
    setContent(post.content)
    setContentJson(getInitialContentJson(post))
    setExcerpt(post.excerpt ?? "")
    setFundingUrl(post.funding_url ?? "")
    setCategoryId(post.category_id ? String(post.category_id) : "")
    setSelectedTagIds(post.tags.map((tag) => tag.id))
    setMessage(null)
  }, [open, post])

  useEffect(() => {
    if (!open) {
      return
    }

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
        setTags(nextTags.slice(0, 8))
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
  }, [locale, open])

  function validate() {
    const trimmedTitle = title.trim()
    const trimmedContent = content.trim()
    const trimmedFundingUrl = fundingUrl.trim()

    if (!trimmedTitle) {
      return form.titleRequired
    }

    if (trimmedTitle.length > 100) {
      return form.titleMax
    }

    if (!trimmedContent) {
      return form.contentRequired
    }

    if (trimmedContent.length < 20) {
      return form.contentMin
    }

    if (trimmedContent.length > MAX_CONTENT_CHARACTERS) {
      return formatMessage(form.contentMax, { max: MAX_CONTENT_CHARACTERS })
    }

    if (trimmedFundingUrl) {
      try {
        new URL(trimmedFundingUrl)
      } catch {
        return form.fundingInvalid
      }
    }

    return null
  }

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button type="button" variant="outline" size="sm">
          {messages.post.edit}
        </Button>
      </DialogTrigger>
      <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-3xl">
        <DialogHeader>
          <DialogTitle>{form.editTitle}</DialogTitle>
          <DialogDescription>{form.editDescription}</DialogDescription>
        </DialogHeader>

        <div className="space-y-4">
          <label className="space-y-2">
            <span className="text-sm text-foreground">{form.titleLabel}</span>
            <Input
              value={title}
              maxLength={100}
              disabled={isPending}
              onChange={(event) => setTitle(event.target.value)}
            />
          </label>

          <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <label className="space-y-2">
              <span className="text-sm text-foreground">
                {form.categoryLabel}
              </span>
              <select
                value={categoryId}
                disabled={isPending}
                onChange={(event) => setCategoryId(event.target.value)}
                className="flex h-11 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
              >
                <option value="">{form.categoryPlaceholder}</option>
                {categories.map((category) => (
                  <option key={category.id} value={category.id}>
                    {category.name}
                  </option>
                ))}
              </select>
            </label>

            <label className="space-y-2">
              <span className="text-sm text-foreground">{form.excerptLabel}</span>
              <Input
                value={excerpt}
                disabled={isPending}
                maxLength={500}
                placeholder={form.excerptPlaceholder}
                onChange={(event) => setExcerpt(event.target.value)}
              />
            </label>
          </div>

          <label className="space-y-2">
            <span className="text-sm text-foreground">{form.fundingLabel}</span>
            <Input
              value={fundingUrl}
              disabled={isPending}
              placeholder={form.fundingPlaceholder}
              type="url"
              onChange={(event) => setFundingUrl(event.target.value)}
            />
          </label>

          {tags.length > 0 ? (
            <div className="space-y-2">
              <span className="text-sm text-foreground">{form.tagsLabel}</span>
              <div className="flex flex-wrap gap-2">
                {tags.map((tag) => {
                  const isSelected = selectedTagIds.includes(tag.id)

                  return (
                    <button
                      key={tag.id}
                      type="button"
                      disabled={isPending}
                      className={`rounded-full border px-3 py-1 text-xs transition-colors disabled:cursor-not-allowed disabled:opacity-60 ${
                        isSelected
                          ? "border-primary/40 bg-primary/10 text-primary"
                          : "border-border/70 text-muted-foreground"
                      }`}
                      onClick={() => {
                        setSelectedTagIds((currentTagIds) =>
                          isSelected
                            ? currentTagIds.filter((id) => id !== tag.id)
                            : [...currentTagIds, tag.id],
                        )
                      }}
                    >
                      #{getTagName(tag, locale)}
                    </button>
                  )
                })}
              </div>
            </div>
          ) : null}

          <div className="space-y-2">
            <span className="text-sm text-foreground">{form.contentLabel}</span>
            <RichPostEditor
              content={contentJson}
              placeholder={form.contentPlaceholder}
              maxCharacters={MAX_CONTENT_CHARACTERS}
              disabled={isPending}
              showCoverImage={false}
              onChange={(nextJson, plainText) => {
                setContentJson(nextJson)
                setContent(plainText)
              }}
            />
          </div>

          {message ? <p className="text-sm text-destructive">{message}</p> : null}
        </div>

        <DialogFooter>
          <Button
            type="button"
            disabled={isPending}
            onClick={() => {
              const validationMessage = validate()

              if (validationMessage) {
                setMessage(validationMessage)
                return
              }

              setMessage(null)

              startTransition(() => {
                void updatePost(
                  post.id,
                  {
                    title: title.trim(),
                    content: content.trim(),
                    content_json: JSON.stringify(contentJson),
                    excerpt: excerpt.trim() || undefined,
                    category_id: categoryId ? Number(categoryId) : null,
                    tag_ids: selectedTagIds,
                    funding_url: fundingUrl.trim() || null,
                  },
                  token,
                )
                  .then((updatedPost) => {
                    onSaved(updatedPost)
                    setOpen(false)
                  })
                  .catch((error) => {
                    setMessage(getErrorMessage(error))
                  })
              })
            }}
          >
            {isPending ? form.savePending : form.save}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
