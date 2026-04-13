"use client"

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
import { Textarea } from "@/components/ui/textarea"
import { getErrorMessage } from "@/lib/api/client"
import { listCategories, listTags, updatePost } from "@/lib/api/posts"
import type { CommunityCategory, CommunityPost, CommunityTag } from "@/lib/types"

type CommunityPostEditorDialogProps = {
  post: CommunityPost
  token: string
  onSaved: (post: CommunityPost) => void
}

export function CommunityPostEditorDialog({
  post,
  token,
  onSaved,
}: CommunityPostEditorDialogProps) {
  const [open, setOpen] = useState(false)
  const [title, setTitle] = useState(post.title)
  const [content, setContent] = useState(post.content)
  const [excerpt, setExcerpt] = useState(post.excerpt ?? "")
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
  }, [open])

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        <Button type="button" variant="outline" size="sm">
          Edit
        </Button>
      </DialogTrigger>
      <DialogContent className="sm:max-w-2xl">
        <DialogHeader>
          <DialogTitle>Edit post</DialogTitle>
          <DialogDescription>
            Update the title, category, tags, and body through the backend patch endpoint.
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-4">
          <label className="space-y-2">
            <span className="text-sm text-foreground">Title</span>
            <Input value={title} onChange={(event) => setTitle(event.target.value)} />
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
                            : [...currentTagIds, tag.id],
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
              className="min-h-40"
            />
          </label>

          {message ? <p className="text-sm text-destructive">{message}</p> : null}
        </div>

        <DialogFooter>
          <Button
            type="button"
            disabled={!title.trim() || !content.trim() || isPending}
            onClick={() => {
              setMessage(null)

              startTransition(() => {
                void updatePost(
                  post.id,
                  {
                    title: title.trim(),
                    content: content.trim(),
                    excerpt: excerpt.trim() || undefined,
                    category_id: categoryId ? Number(categoryId) : null,
                    tag_ids: selectedTagIds,
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
            {isPending ? "Saving..." : "Save changes"}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  )
}
