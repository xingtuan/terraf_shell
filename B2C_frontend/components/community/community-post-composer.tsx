"use client"

import dynamic from "next/dynamic"
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
import type { CommunityCategory, CommunityPost, CommunityTag } from "@/lib/types"

const RichPostEditor = dynamic(
  () =>
    import("@/components/community/RichPostEditor").then(
      (module) => module.RichPostEditor,
    ),
  {
    ssr: false,
    loading: () => (
      <div className="min-h-48 animate-pulse rounded-2xl border border-border/70 bg-muted/30" />
    ),
  },
)

type CommunityPostComposerProps = {
  token?: string | null
  onCreated: (post: CommunityPost) => void
  onMessage: (message: string | null) => void
}

type ComposerErrors = Partial<
  Record<"title" | "content" | "funding_url", string>
>

export function CommunityPostComposer({
  token,
  onCreated,
  onMessage,
}: CommunityPostComposerProps) {
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
  const [fundingUrl, setFundingUrl] = useState("")
  const [errors, setErrors] = useState<ComposerErrors>({})
  const [isPending, startTransition] = useTransition()

  useEffect(() => {
    let isCancelled = false

    async function loadTaxonomy() {
      try {
        const [nextCategories, nextTags] = await Promise.all([
          listCategories(),
          listTags(),
        ])

        if (isCancelled) return

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
    return () => { isCancelled = true }
  }, [])

  function resetForm() {
    setTitle("")
    setCategoryId("")
    setSelectedTagIds([])
    setContent("")
    setContentJson(createRichTextDocumentFromText("") as Record<string, unknown>)
    setCoverImageUrl("")
    setCoverImagePath("")
    setFundingUrl("")
    setErrors({})
  }

  if (!token) return null

  return (
    <div className="rounded-3xl border border-border/60 bg-card p-7">
      <p className="text-sm uppercase tracking-[0.18em] text-primary">
        Share your idea
      </p>
      <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
        Post a design concept, material experiment, or product proposal.
      </p>

      <div className="mt-6 space-y-5">
        {/* Title */}
        <div className="space-y-1.5">
          <label className="text-sm font-medium text-foreground">Title</label>
          <Input
            value={title}
            onChange={(e) => setTitle(e.target.value)}
            maxLength={100}
            placeholder="What are you sharing or proposing?"
          />
          {errors.title ? <p className="text-xs text-destructive">{errors.title}</p> : null}
        </div>

        {/* Category + Funding URL */}
        <div className="grid gap-4 sm:grid-cols-2">
          {categories.length > 0 ? (
            <div className="space-y-1.5">
              <label className="text-sm font-medium text-foreground">Category</label>
              <Select value={categoryId} onValueChange={setCategoryId}>
                <SelectTrigger className="w-full">
                  <SelectValue placeholder="Select category" />
                </SelectTrigger>
                <SelectContent>
                  {categories.map((cat) => (
                    <SelectItem key={cat.id} value={String(cat.id)}>
                      {cat.name}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          ) : null}

          <div className="space-y-1.5">
            <label className="text-sm font-medium text-foreground">
              Funding link{" "}
              <span className="font-normal text-muted-foreground">(optional)</span>
            </label>
            <Input
              value={fundingUrl}
              onChange={(e) => setFundingUrl(e.target.value)}
              placeholder="https://gofundme.com/your-project"
              type="url"
            />
            {errors.funding_url ? <p className="text-xs text-destructive">{errors.funding_url}</p> : null}
          </div>
        </div>

        {/* Tags */}
        {tags.length > 0 ? (
          <div className="space-y-2">
            <span className="text-sm font-medium text-foreground">Tags</span>
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
                        isSelected ? ids.filter((id) => id !== tag.id) : [...ids, tag.id],
                      )
                    }
                  >
                    #{tag.name}
                  </button>
                )
              })}
            </div>
          </div>
        ) : null}

        {/* Rich text editor */}
        <div className="space-y-1.5">
          <label className="text-sm font-medium text-foreground">Content</label>
          <RichPostEditor
            content={contentJson}
            placeholder="Describe your concept, material experiment, or progress update…"
            coverImageUrl={coverImageUrl}
            coverImagePath={coverImagePath}
            onChange={(nextJson, plainText) => {
              setContentJson(nextJson)
              setContent(plainText)
            }}
            onCoverImageChange={(url, path) => {
              setCoverImageUrl(url)
              setCoverImagePath(path)
            }}
          />
          {errors.content ? <p className="text-xs text-destructive">{errors.content}</p> : null}
        </div>

        {/* Submit */}
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
                nextErrors.title = "Title is required."
              } else if (trimmedTitle.length > 100) {
                nextErrors.title = "Title must be 100 characters or fewer."
              }

              if (!trimmedContent) {
                nextErrors.content = "Content is required."
              } else if (trimmedContent.length < 20) {
                nextErrors.content = "Content must be at least 20 characters."
              }

              if (trimmedFundingUrl) {
                try { new URL(trimmedFundingUrl) } catch {
                  nextErrors.funding_url = "Please enter a valid URL."
                }
              }

              setErrors(nextErrors)
              if (Object.keys(nextErrors).length > 0) return

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
                    funding_url: trimmedFundingUrl || null,
                  },
                  token,
                )
                  .then((post) => {
                    resetForm()
                    onCreated(post)
                    onMessage("Post submitted — it will appear once approved.")
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
            {isPending ? "Publishing…" : "Publish post"}
          </Button>
        </div>
      </div>
    </div>
  )
}
