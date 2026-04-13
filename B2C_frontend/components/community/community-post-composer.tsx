"use client"

import { useEffect, useState, useTransition } from "react"

import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Textarea } from "@/components/ui/textarea"
import { ApiError, getErrorMessage } from "@/lib/api/client"
import { createPost, listCategories, listTags } from "@/lib/api/posts"
import type { CommunityCategory, CommunityPost, CommunityTag } from "@/lib/types"

type CommunityPostComposerProps = {
  token?: string | null
  onCreated: (post: CommunityPost) => void
  onMessage: (message: string | null) => void
}

type ComposerErrors = Partial<Record<"title" | "content", string>>

export function CommunityPostComposer({
  token,
  onCreated,
  onMessage,
}: CommunityPostComposerProps) {
  const [categories, setCategories] = useState<CommunityCategory[]>([])
  const [tags, setTags] = useState<CommunityTag[]>([])
  const [title, setTitle] = useState("")
  const [content, setContent] = useState("")
  const [excerpt, setExcerpt] = useState("")
  const [categoryId, setCategoryId] = useState("")
  const [selectedTagIds, setSelectedTagIds] = useState<number[]>([])
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
  }, [])

  if (!token) {
    return null
  }

  return (
    <div className="rounded-3xl border border-border/60 bg-card p-7">
      <p className="text-sm uppercase tracking-[0.18em] text-primary">
        Create post
      </p>
      <p className="mt-3 text-sm leading-relaxed text-muted-foreground">
        Publish a new community post directly to the backend with the existing token-based auth flow.
      </p>

      <form
        className="mt-6 space-y-4"
        onSubmit={(event) => {
          event.preventDefault()
          onMessage(null)

          const nextErrors: ComposerErrors = {}

          if (!title.trim()) {
            nextErrors.title = "Title is required."
          }

          if (!content.trim()) {
            nextErrors.content = "Content is required."
          }

          setErrors(nextErrors)

          if (Object.keys(nextErrors).length > 0) {
            return
          }

          startTransition(() => {
            void createPost(
              {
                title: title.trim(),
                content: content.trim(),
                excerpt: excerpt.trim() || undefined,
                category_id: categoryId ? Number(categoryId) : undefined,
                tag_ids: selectedTagIds.length ? selectedTagIds : undefined,
              },
              token,
            )
              .then((post) => {
                setTitle("")
                setContent("")
                setExcerpt("")
                setCategoryId("")
                setSelectedTagIds([])
                setErrors({})
                onCreated(post)
                onMessage("Post created successfully.")
              })
              .catch((error) => {
                if (error instanceof ApiError) {
                  setErrors({
                    title: error.errors?.title?.[0],
                    content: error.errors?.content?.[0],
                  })
                }

                onMessage(getErrorMessage(error))
              })
          })
        }}
      >
        <label className="space-y-2">
          <span className="text-sm text-foreground">Title</span>
          <Input
            value={title}
            onChange={(event) => setTitle(event.target.value)}
            placeholder="What are you building, testing, or discussing?"
          />
          {errors.title ? (
            <p className="text-sm text-destructive">{errors.title}</p>
          ) : null}
        </label>

        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
          <label className="space-y-2">
            <span className="text-sm text-foreground">Category</span>
            <select
              value={categoryId}
              onChange={(event) => setCategoryId(event.target.value)}
              className="flex h-11 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs"
            >
              <option value="">Select category</option>
              {categories.map((category) => (
                <option key={category.id} value={category.id}>
                  {category.name}
                </option>
              ))}
            </select>
          </label>

          <label className="space-y-2">
            <span className="text-sm text-foreground">Excerpt</span>
            <Input
              value={excerpt}
              onChange={(event) => setExcerpt(event.target.value)}
              placeholder="Optional short summary"
            />
          </label>
        </div>

        {tags.length > 0 ? (
          <div className="space-y-2">
            <span className="text-sm text-foreground">Tags</span>
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
                        : "border-border/70 text-muted-foreground"
                    }`}
                    onClick={() => {
                      setSelectedTagIds((currentTagIds) =>
                        isSelected
                          ? currentTagIds.filter((id) => id !== tag.id)
                          : [...currentTagIds, tag.id]
                      )
                    }}
                  >
                    #{tag.name}
                  </button>
                )
              })}
            </div>
          </div>
        ) : null}

        <label className="space-y-2">
          <span className="text-sm text-foreground">Content</span>
          <Textarea
            value={content}
            onChange={(event) => setContent(event.target.value)}
            placeholder="Describe the concept, challenge, or progress update."
            className="min-h-32"
          />
          {errors.content ? (
            <p className="text-sm text-destructive">{errors.content}</p>
          ) : null}
        </label>

        <div className="flex justify-end">
          <Button type="submit" disabled={isPending}>
            {isPending ? "Publishing..." : "Publish post"}
          </Button>
        </div>
      </form>
    </div>
  )
}
