"use client"

import dynamic from "next/dynamic"
import { useEffect, useState, useTransition } from "react"

import { ApiError, getErrorMessage } from "@/lib/api/client"
import {
  createPost,
  getPost,
  listCategories,
  updatePost,
} from "@/lib/api/posts"
import {
  getCommunityPostCoverImage,
  getCommunityTaxonomyName,
} from "@/lib/community-ui"
import { createRichTextDocumentFromText } from "@/lib/community-rich-text"
import type { Locale, SiteMessages } from "@/lib/i18n"
import type { CommunityCategory, CommunityPost } from "@/lib/types"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from "@/components/ui/select"
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetHeader,
  SheetTitle,
} from "@/components/ui/sheet"
import { Textarea } from "@/components/ui/textarea"
import { toast } from "@/hooks/use-toast"

const RichPostEditor = dynamic(
  () =>
    import("@/components/community/RichPostEditor").then(
      (module) => module.RichPostEditor,
    ),
  {
    ssr: false,
    loading: () => (
      <div className="rounded-2xl border border-border/70 bg-muted/20 p-6 text-sm text-muted-foreground">
        Loading editor...
      </div>
    ),
  },
)

type CreatePostPanelProps = {
  locale: Locale
  messages: SiteMessages["community"]
  token?: string | null
  open: boolean
  onOpenChange: (open: boolean) => void
  onSuccess?: (post: CommunityPost) => void
  initialData?: CommunityPost | null
}

type FieldErrors = Partial<
  Record<
    | "title"
    | "category_id"
    | "tags"
    | "content"
    | "excerpt"
    | "funding_url"
    | "images",
    string
  >
>

function getApiFieldError(error: ApiError, keys: string[]) {
  for (const key of keys) {
    const message = error.errors?.[key]?.[0]

    if (message) {
      return message
    }
  }

  return undefined
}

function normalizeTags(value: string) {
  return Array.from(
    new Set(
      value
        .split(",")
        .map((tag) => tag.trim())
        .filter(Boolean),
    ),
  )
}

export function CreatePostPanel({
  locale,
  messages,
  token,
  open,
  onOpenChange,
  onSuccess,
  initialData,
}: CreatePostPanelProps) {
  const isEditing = Boolean(initialData)
  const [categories, setCategories] = useState<CommunityCategory[]>([])
  const [title, setTitle] = useState("")
  const [categoryId, setCategoryId] = useState("")
  const [tags, setTags] = useState("")
  const [content, setContent] = useState("")
  const [contentJson, setContentJson] = useState<Record<string, unknown>>(
    createRichTextDocumentFromText("") as Record<string, unknown>,
  )
  const [excerpt, setExcerpt] = useState("")
  const [fundingUrl, setFundingUrl] = useState("")
  const [coverImageUrl, setCoverImageUrl] = useState("")
  const [coverImagePath, setCoverImagePath] = useState("")
  const [images, setImages] = useState<File[]>([])
  const [previews, setPreviews] = useState<string[]>([])
  const [editingPost, setEditingPost] = useState<CommunityPost | null>(null)
  const [isLoadingPostDetail, setIsLoadingPostDetail] = useState(false)
  const [errors, setErrors] = useState<FieldErrors>({})
  const [formError, setFormError] = useState<string | null>(null)
  const [isPending, startTransition] = useTransition()

  const currentPost = editingPost ?? initialData ?? null

  useEffect(() => {
    if (!open) {
      return
    }

    let isCancelled = false

    void listCategories()
      .then((nextCategories) => {
        if (!isCancelled) {
          setCategories(nextCategories)
        }
      })
      .catch(() => {
        if (!isCancelled) {
          setCategories([])
        }
      })

    return () => {
      isCancelled = true
    }
  }, [open])

  useEffect(() => {
    if (!open) {
      return
    }

    const requiresDetail = Boolean(
      initialData &&
        (initialData.content_json === undefined ||
          initialData.cover_image_path === undefined),
    )

    setErrors({})
    setFormError(null)
    setImages([])
    setEditingPost(initialData && !requiresDetail ? initialData : null)

    if (!initialData || !requiresDetail || !token) {
      setIsLoadingPostDetail(false)
      return
    }

    let isCancelled = false

    setIsLoadingPostDetail(true)

    void getPost(initialData.slug, { token })
      .then((post) => {
        if (!isCancelled) {
          setEditingPost(post)
        }
      })
      .catch((error) => {
        if (!isCancelled) {
          setEditingPost(initialData)
          setFormError(getErrorMessage(error))
        }
      })
      .finally(() => {
        if (!isCancelled) {
          setIsLoadingPostDetail(false)
        }
      })

    return () => {
      isCancelled = true
    }
  }, [initialData, open, token])

  useEffect(() => {
    if (!open) {
      return
    }

    setTitle(currentPost?.title ?? "")
    setCategoryId(currentPost?.category_id ? String(currentPost.category_id) : "")
    setTags(currentPost?.tags.map((tag) => tag.name).join(", ") ?? "")
    setContent(currentPost?.content ?? "")
    setContentJson(
      currentPost?.content_json ??
        (createRichTextDocumentFromText(currentPost?.content ?? "") as Record<string, unknown>),
    )
    setExcerpt(currentPost?.excerpt ?? "")
    setFundingUrl(currentPost?.funding_url ?? "")
    setCoverImageUrl(currentPost?.cover_image_url ?? "")
    setCoverImagePath(currentPost?.cover_image_path ?? "")
  }, [currentPost, open])

  useEffect(() => {
    const nextPreviews = images.map((file) => URL.createObjectURL(file))
    setPreviews(nextPreviews)

    return () => {
      nextPreviews.forEach((preview) => URL.revokeObjectURL(preview))
    }
  }, [images])

  function validate() {
    const nextErrors: FieldErrors = {}
    const trimmedTitle = title.trim()
    const trimmedContent = content.trim()
    const trimmedExcerpt = excerpt.trim()
    const trimmedFundingUrl = fundingUrl.trim()

    if (!trimmedTitle) {
      nextErrors.title = messages.form.titleRequired
    } else if (trimmedTitle.length > 100) {
      nextErrors.title = messages.form.titleMax
    }

    if (!trimmedContent) {
      nextErrors.content = messages.form.contentRequired
    } else if (trimmedContent.length < 20) {
      nextErrors.content = messages.form.contentMin
    }

    if (trimmedExcerpt.length > 500) {
      nextErrors.excerpt = "Excerpt must be 500 characters or fewer."
    }

    if (trimmedFundingUrl) {
      try {
        new URL(trimmedFundingUrl)
      } catch {
        nextErrors.funding_url = messages.form.fundingInvalid
      }
    }

    if (images.length > 4) {
      nextErrors.images = messages.form.imagesMax
    }

    setErrors(nextErrors)

    return Object.keys(nextErrors).length === 0
  }

  return (
    <Sheet open={open} onOpenChange={onOpenChange}>
      <SheetContent side="right" className="w-full overflow-y-auto sm:max-w-2xl">
        <SheetHeader className="border-b border-border/60 pb-5">
          <SheetTitle>
            {isEditing ? messages.form.editTitle : messages.form.createTitle}
          </SheetTitle>
          <SheetDescription>
            {isEditing
              ? messages.form.editDescription
              : messages.form.createDescription}
          </SheetDescription>
        </SheetHeader>

        <form
          className="space-y-6 p-4"
          onSubmit={(event) => {
            event.preventDefault()

            if (!token || isLoadingPostDetail || !validate()) {
              return
            }

            const payload = {
              title: title.trim(),
              category_id: categoryId ? Number(categoryId) : null,
              tags: normalizeTags(tags),
              content: content.trim(),
              content_json: JSON.stringify(contentJson),
              excerpt: excerpt.trim() || null,
              cover_image_url: coverImageUrl || null,
              cover_image_path: coverImagePath || null,
              funding_url: fundingUrl.trim() || null,
              images,
            }

            setFormError(null)

            startTransition(() => {
              void (isEditing && initialData
                ? updatePost(initialData.id, payload, token)
                : createPost(payload, token))
                .then((post) => {
                  if (!isEditing) {
                    toast({
                      title: messages.form.success,
                    })
                  }

                  onSuccess?.(post)
                  onOpenChange(false)
                })
                .catch((error) => {
                  if (error instanceof ApiError) {
                    setErrors({
                      title: getApiFieldError(error, ["title"]),
                      category_id: getApiFieldError(error, ["category_id"]),
                      tags: getApiFieldError(error, ["tags", "tags.0", "tags.1"]),
                      content: getApiFieldError(error, ["content", "content_json"]),
                      excerpt: getApiFieldError(error, ["excerpt"]),
                      funding_url: getApiFieldError(error, ["funding_url"]),
                      images: getApiFieldError(error, [
                        "images",
                        "images.0",
                        "images.1",
                        "images.2",
                        "images.3",
                      ]),
                    })
                  }

                  setFormError(getErrorMessage(error))
                })
            })
          }}
        >
          {isEditing && isLoadingPostDetail ? (
            <div className="rounded-2xl border border-border/70 bg-muted/20 px-4 py-5 text-sm text-muted-foreground">
              {messages.form.loadingPost}
            </div>
          ) : null}

          <div className="space-y-2">
            <label className="text-sm font-medium text-foreground">
              {messages.form.titleLabel}
            </label>
            <Input
              value={title}
              onChange={(event) => setTitle(event.target.value)}
              maxLength={100}
              placeholder={messages.form.titlePlaceholder}
            />
            {errors.title ? (
              <p className="text-sm text-destructive">{errors.title}</p>
            ) : null}
          </div>

          <div className="grid gap-6 sm:grid-cols-2">
            <div className="space-y-2">
              <label className="text-sm font-medium text-foreground">
                {messages.form.categoryLabel}
              </label>
              <Select value={categoryId} onValueChange={setCategoryId}>
                <SelectTrigger className="w-full">
                  <SelectValue placeholder={messages.form.categoryPlaceholder} />
                </SelectTrigger>
                <SelectContent>
                  {categories.map((category) => (
                    <SelectItem key={category.id} value={String(category.id)}>
                      {getCommunityTaxonomyName(category, locale)}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              {errors.category_id ? (
                <p className="text-sm text-destructive">{errors.category_id}</p>
              ) : null}
            </div>

            <div className="space-y-2">
              <label className="text-sm font-medium text-foreground">
                {messages.form.tagsLabel}
              </label>
              <Input
                value={tags}
                onChange={(event) => setTags(event.target.value)}
                placeholder={messages.form.tagsPlaceholder}
              />
              <p className="text-xs text-muted-foreground">
                {messages.form.tagsHint}
              </p>
              {errors.tags ? (
                <p className="text-sm text-destructive">{errors.tags}</p>
              ) : null}
            </div>
          </div>

          <div className="space-y-2">
            <label className="text-sm font-medium text-foreground">
              {messages.form.fundingLabel}
            </label>
            <Input
              value={fundingUrl}
              onChange={(event) => setFundingUrl(event.target.value)}
              placeholder={messages.form.fundingPlaceholder}
            />
            {errors.funding_url ? (
              <p className="text-sm text-destructive">{errors.funding_url}</p>
            ) : null}
          </div>

          <div className="space-y-2">
            <label className="text-sm font-medium text-foreground">
              {messages.form.contentLabel}
            </label>
            <RichPostEditor
              content={contentJson}
              placeholder={messages.form.contentPlaceholder}
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
            {errors.content ? (
              <p className="text-sm text-destructive">{errors.content}</p>
            ) : null}
          </div>

          <div className="space-y-2">
            <label className="text-sm font-medium text-foreground">
              {messages.form.excerptLabel}
            </label>
            <Textarea
              value={excerpt}
              onChange={(event) => setExcerpt(event.target.value)}
              maxLength={500}
              placeholder={messages.form.excerptPlaceholder}
              className="min-h-24"
            />
            <div className="flex items-center justify-between gap-3">
              <p className="text-xs text-muted-foreground">
                {messages.form.excerptHint}
              </p>
              <p className="text-xs text-muted-foreground">
                {excerpt.length} / 500
              </p>
            </div>
            {errors.excerpt ? (
              <p className="text-sm text-destructive">{errors.excerpt}</p>
            ) : null}
          </div>

          {isEditing && currentPost?.images.length ? (
            <div className="space-y-3">
              <p className="text-sm font-medium text-foreground">
                {messages.form.existingImages}
              </p>
              <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                {currentPost.images.map((image) => (
                  <div
                    key={image.id}
                    className="overflow-hidden rounded-2xl border border-border/60 bg-muted"
                  >
                    <img
                      src={image.url || getCommunityPostCoverImage(currentPost)}
                      alt={image.alt_text ?? currentPost.title}
                      className="aspect-square w-full object-cover"
                    />
                  </div>
                ))}
              </div>
            </div>
          ) : null}

          <div className="space-y-3">
            <label className="text-sm font-medium text-foreground">
              {messages.form.imagesLabel}
            </label>
            <Input
              type="file"
              accept="image/*"
              multiple
              onChange={(event) => {
                const nextFiles = Array.from(event.target.files ?? [])
                setImages(nextFiles)
              }}
            />
            <p className="text-xs text-muted-foreground">
              {messages.form.imagesHint}
            </p>
            {previews.length > 0 ? (
              <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                {previews.map((preview, index) => (
                  <div
                    key={preview}
                    className="relative overflow-hidden rounded-2xl border border-border/60"
                  >
                    <img
                      src={preview}
                      alt={images[index]?.name ?? `${locale}-preview-${index + 1}`}
                      className="aspect-square w-full object-cover"
                    />
                    <button
                      type="button"
                      className="absolute right-2 top-2 rounded-full bg-background/90 px-2 py-1 text-xs text-foreground"
                      onClick={() => {
                        setImages((currentImages) =>
                          currentImages.filter(
                            (_, imageIndex) => imageIndex !== index,
                          ),
                        )
                      }}
                    >
                      {messages.post.delete}
                    </button>
                  </div>
                ))}
              </div>
            ) : null}
            {errors.images ? (
              <p className="text-sm text-destructive">{errors.images}</p>
            ) : null}
          </div>

          {formError ? (
            <div className="rounded-2xl border border-destructive/30 bg-destructive/5 px-4 py-3 text-sm text-destructive">
              {formError}
            </div>
          ) : null}

          <div className="flex justify-end">
            <Button
              type="submit"
              disabled={!token || isPending || isLoadingPostDetail}
            >
              {isPending
                ? messages.form.uploading
                : isEditing
                  ? messages.form.save
                  : messages.form.publish}
            </Button>
          </div>
        </form>
      </SheetContent>
    </Sheet>
  )
}
