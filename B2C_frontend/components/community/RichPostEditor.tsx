"use client"

import { type ReactNode, useEffect, useRef, useState } from "react"

import { EditorContent, useEditor } from "@tiptap/react"
import {
  Bold,
  Code2,
  Heading2,
  Heading3,
  ImagePlus,
  Italic,
  Link2,
  List,
  ListOrdered,
  Loader2,
  Minus,
  Quote,
  Redo2,
  Strikethrough,
  Trash2,
  Underline as UnderlineIcon,
  Undo2,
  UploadCloud,
} from "lucide-react"
import { useParams } from "next/navigation"

import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover"
import { Separator } from "@/components/ui/separator"
import {
  Tooltip,
  TooltipContent,
  TooltipTrigger,
} from "@/components/ui/tooltip"
import {
  buildCommunityTiptapExtensions,
  createRichTextDocumentFromText,
  normalizeRichTextLinkHref,
  normalizeRichTextDocument,
} from "@/lib/community-rich-text"
import { deleteMedia, uploadMedia } from "@/lib/api/media"
import {
  defaultLocale,
  getMessages,
  isValidLocale,
  type Locale,
} from "@/lib/i18n"
import { cn } from "@/lib/utils"

type RichPostEditorProps = {
  content?: Record<string, unknown> | null
  onChange: (json: Record<string, unknown>, plainText: string) => void
  onCoverImageChange?: (url: string, path: string, disk?: string | null) => void
  coverImageUrl?: string
  coverImagePath?: string
  placeholder?: string
  maxCharacters?: number
  disabled?: boolean
  showCoverImage?: boolean
}

type CoverImageState = {
  url: string
  path: string
  disk?: string | null
}

function ToolbarButton({
  label,
  active = false,
  disabled = false,
  onClick,
  children,
}: {
  label: string
  active?: boolean
  disabled?: boolean
  onClick?: () => void
  children: ReactNode
}) {
  const button = (
    <button
      type="button"
      aria-label={label}
      title={label}
      disabled={disabled}
      onClick={onClick}
      className={cn(
        "inline-flex size-8 shrink-0 items-center justify-center rounded-md border text-sm transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-45",
        active
          ? "border-primary bg-primary text-primary-foreground"
          : "border-border/70 bg-background text-foreground hover:bg-muted",
      )}
    >
      {children}
    </button>
  )

  return (
    <Tooltip>
      <TooltipTrigger asChild>{button}</TooltipTrigger>
      <TooltipContent side="bottom">{label}</TooltipContent>
    </Tooltip>
  )
}

function PopoverToolbarButton({
  label,
  active = false,
  disabled = false,
  children,
}: {
  label: string
  active?: boolean
  disabled?: boolean
  children: ReactNode
}) {
  return (
    <Tooltip>
      <TooltipTrigger asChild>
        <PopoverTrigger asChild>
          <button
            type="button"
            aria-label={label}
            title={label}
            disabled={disabled}
            className={cn(
              "inline-flex size-8 shrink-0 items-center justify-center rounded-md border text-sm transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-45",
              active
                ? "border-primary bg-primary text-primary-foreground"
                : "border-border/70 bg-background text-foreground hover:bg-muted",
            )}
          >
            {children}
          </button>
        </PopoverTrigger>
      </TooltipTrigger>
      <TooltipContent side="bottom">{label}</TooltipContent>
    </Tooltip>
  )
}

function ToolbarDivider() {
  return (
    <Separator
      orientation="vertical"
      className="mx-0.5 hidden h-6 sm:block"
    />
  )
}

function resolveLocaleFromParams(value: unknown): Locale {
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

export function RichPostEditor({
  content,
  onChange,
  onCoverImageChange,
  coverImageUrl = "",
  coverImagePath = "",
  placeholder,
  maxCharacters = 10000,
  disabled = false,
  showCoverImage,
}: RichPostEditorProps) {
  const params = useParams()
  const locale = resolveLocaleFromParams(params.locale)
  const messages = getMessages(locale).community
  const labels = messages.richEditor
  const toolbar = labels.toolbar
  const placeholderText = placeholder ?? messages.form.contentPlaceholder
  const imageInputRef = useRef<HTMLInputElement | null>(null)
  const coverInputRef = useRef<HTMLInputElement | null>(null)
  const onChangeRef = useRef(onChange)
  const [linkUrl, setLinkUrl] = useState("")
  const [linkError, setLinkError] = useState<string | null>(null)
  const [isLinkOpen, setIsLinkOpen] = useState(false)
  const [isUploadingInlineImage, setIsUploadingInlineImage] = useState(false)
  const [isUploadingCoverImage, setIsUploadingCoverImage] = useState(false)
  const [inlineError, setInlineError] = useState<string | null>(null)
  const [coverError, setCoverError] = useState<string | null>(null)
  const [isDraggingCover, setIsDraggingCover] = useState(false)
  const [coverImage, setCoverImage] = useState<CoverImageState>({
    url: coverImageUrl,
    path: coverImagePath,
    disk: null,
  })
  const shouldShowCoverImage =
    showCoverImage ?? Boolean(onCoverImageChange || coverImage.url || coverImage.path)

  const editor = useEditor({
    immediatelyRender: false,
    extensions: buildCommunityTiptapExtensions({
      placeholder: placeholderText,
      maxCharacters,
    }),
    content: normalizeRichTextDocument(content),
    editable: !disabled,
    editorProps: {
      attributes: {
        "aria-label": labels.editorLabel,
        class:
          "prose prose-neutral max-w-none min-h-72 px-4 py-4 focus:outline-none prose-img:rounded-xl prose-img:border prose-img:border-border/60",
      },
    },
    onUpdate: ({ editor: currentEditor }) => {
      onChangeRef.current(
        currentEditor.getJSON() as Record<string, unknown>,
        currentEditor.getText(),
      )
    },
  })

  const characterCount =
    editor?.storage.characterCount?.characters() ?? editor?.getText().length ?? 0
  const hasCharacterError = characterCount > maxCharacters
  const counterClassName = hasCharacterError
    ? "text-destructive"
    : characterCount >= Math.floor(maxCharacters * 0.9)
      ? "text-amber-600"
      : "text-muted-foreground"

  useEffect(() => {
    onChangeRef.current = onChange
  }, [onChange])

  useEffect(() => {
    if (!editor) {
      return
    }

    editor.setEditable(!disabled)
  }, [disabled, editor])

  useEffect(() => {
    if (!editor) {
      return
    }

    onChangeRef.current(
      editor.getJSON() as Record<string, unknown>,
      editor.getText(),
    )
  }, [editor])

  useEffect(() => {
    setCoverImage({
      url: coverImageUrl,
      path: coverImagePath,
      disk: null,
    })
  }, [coverImagePath, coverImageUrl])

  useEffect(() => {
    if (!editor) {
      return
    }

    const nextContent = content
      ? normalizeRichTextDocument(content)
      : createRichTextDocumentFromText("")

    if (JSON.stringify(editor.getJSON()) === JSON.stringify(nextContent)) {
      return
    }

    editor.commands.setContent(nextContent, false)
  }, [content, editor])

  async function handleInlineImageUpload(file?: File | null) {
    if (!file || !editor || disabled) {
      return
    }

    setInlineError(null)
    setIsUploadingInlineImage(true)

    try {
      const uploaded = await uploadMedia(file, "community")
      editor
        .chain()
        .focus()
        .setImage({ src: uploaded.url, alt: file.name })
        .run()
    } catch {
      setInlineError(labels.imageUploadFailed)
    } finally {
      setIsUploadingInlineImage(false)
    }
  }

  async function handleCoverImageUpload(file?: File | null) {
    if (!file || disabled) {
      return
    }

    setCoverError(null)
    setIsUploadingCoverImage(true)

    try {
      const uploaded = await uploadMedia(file, "community")
      const nextCoverImage = {
        url: uploaded.url,
        path: uploaded.path,
        disk: uploaded.disk ?? null,
      }

      setCoverImage(nextCoverImage)
      onCoverImageChange?.(uploaded.url, uploaded.path, uploaded.disk ?? null)
    } catch {
      setCoverError(labels.coverUploadFailed)
    } finally {
      setIsUploadingCoverImage(false)
    }
  }

  async function handleCoverImageRemove() {
    if (disabled) {
      return
    }

    setCoverError(null)

    try {
      if (coverImage.path) {
        await deleteMedia(coverImage.path)
      }

      setCoverImage({ url: "", path: "" })
      onCoverImageChange?.("", "", null)
    } catch {
      setCoverError(labels.coverRemoveFailed)
    }
  }

  function applyLink() {
    if (!editor || disabled) {
      return
    }

    const href = normalizeRichTextLinkHref(linkUrl)

    if (!linkUrl.trim()) {
      editor.chain().focus().extendMarkRange("link").unsetLink().run()
      setIsLinkOpen(false)
      setLinkError(null)
      return
    }

    if (!href) {
      setLinkError(labels.linkInvalid)
      return
    }

    editor
      .chain()
      .focus()
      .extendMarkRange("link")
      .setLink({
        href,
        target: "_blank",
        rel: "noopener noreferrer nofollow",
      })
      .run()

    setIsLinkOpen(false)
    setLinkError(null)
  }

  return (
    <div className={cn("space-y-4", disabled && "opacity-80")}>
      {shouldShowCoverImage ? (
        <div className="space-y-3">
          {coverImage.url ? (
            <div className="relative overflow-hidden rounded-xl border border-border/70">
              <img
                src={coverImage.url}
                alt={labels.coverAlt}
                className="aspect-video w-full object-cover"
              />
              <div className="absolute right-3 top-3">
                <Button
                  type="button"
                  variant="secondary"
                  size="sm"
                  disabled={disabled || isUploadingCoverImage}
                  onClick={() => {
                    void handleCoverImageRemove()
                  }}
                >
                  <Trash2 className="size-4" />
                  {labels.removeCoverImage}
                </Button>
              </div>
            </div>
          ) : (
            <button
              type="button"
              disabled={disabled || isUploadingCoverImage}
              onClick={() => coverInputRef.current?.click()}
              onDragOver={(event) => {
                if (disabled) {
                  return
                }

                event.preventDefault()
                setIsDraggingCover(true)
              }}
              onDragLeave={(event) => {
                event.preventDefault()
                setIsDraggingCover(false)
              }}
              onDrop={(event) => {
                if (disabled) {
                  return
                }

                event.preventDefault()
                setIsDraggingCover(false)

                const file = event.dataTransfer.files?.[0]
                void handleCoverImageUpload(file)
              }}
              className={cn(
                "flex w-full flex-col items-center justify-center rounded-xl border border-dashed px-6 py-10 text-center transition-colors disabled:cursor-not-allowed disabled:opacity-60",
                isDraggingCover
                  ? "border-primary bg-primary/5"
                  : "border-border/70 bg-muted/20 hover:bg-muted/40",
              )}
            >
              <span className="flex items-center gap-2 text-sm font-medium text-foreground">
                {isUploadingCoverImage ? (
                  <Loader2 className="size-4 animate-spin" />
                ) : (
                  <UploadCloud className="size-4" />
                )}
                {isUploadingCoverImage
                  ? labels.uploadingCoverImage
                  : labels.addCoverImage}
              </span>
              <span className="mt-2 text-sm text-muted-foreground">
                {labels.coverImageHint}
              </span>
            </button>
          )}
          <Input
            ref={coverInputRef}
            type="file"
            accept="image/*"
            disabled={disabled || isUploadingCoverImage}
            className="hidden"
            onChange={(event) => {
              const file = event.target.files?.[0]
              void handleCoverImageUpload(file)
              event.target.value = ""
            }}
          />
          {coverError ? (
            <p className="text-sm text-destructive">{coverError}</p>
          ) : null}
        </div>
      ) : null}

      <div
        className={cn(
          "overflow-hidden rounded-xl border border-border/70 bg-background",
          disabled && "pointer-events-none",
        )}
      >
        <div className="border-b border-border/70 bg-muted/30 p-2">
          <div className="flex flex-wrap items-center gap-1.5">
            <ToolbarButton
              label={toolbar.bold}
              active={editor?.isActive("bold")}
              disabled={!editor || disabled}
              onClick={() => editor?.chain().focus().toggleBold().run()}
            >
              <Bold className="size-4" />
            </ToolbarButton>
            <ToolbarButton
              label={toolbar.italic}
              active={editor?.isActive("italic")}
              disabled={!editor || disabled}
              onClick={() => editor?.chain().focus().toggleItalic().run()}
            >
              <Italic className="size-4" />
            </ToolbarButton>
            <ToolbarButton
              label={toolbar.underline}
              active={editor?.isActive("underline")}
              disabled={!editor || disabled}
              onClick={() => editor?.chain().focus().toggleUnderline().run()}
            >
              <UnderlineIcon className="size-4" />
            </ToolbarButton>
            <ToolbarButton
              label={toolbar.strike}
              active={editor?.isActive("strike")}
              disabled={!editor || disabled}
              onClick={() => editor?.chain().focus().toggleStrike().run()}
            >
              <Strikethrough className="size-4" />
            </ToolbarButton>

            <ToolbarDivider />

            <ToolbarButton
              label={toolbar.heading2}
              active={editor?.isActive("heading", { level: 2 })}
              disabled={!editor || disabled}
              onClick={() =>
                editor?.chain().focus().toggleHeading({ level: 2 }).run()
              }
            >
              <Heading2 className="size-4" />
            </ToolbarButton>
            <ToolbarButton
              label={toolbar.heading3}
              active={editor?.isActive("heading", { level: 3 })}
              disabled={!editor || disabled}
              onClick={() =>
                editor?.chain().focus().toggleHeading({ level: 3 }).run()
              }
            >
              <Heading3 className="size-4" />
            </ToolbarButton>

            <ToolbarDivider />

            <ToolbarButton
              label={toolbar.bulletList}
              active={editor?.isActive("bulletList")}
              disabled={!editor || disabled}
              onClick={() => editor?.chain().focus().toggleBulletList().run()}
            >
              <List className="size-4" />
            </ToolbarButton>
            <ToolbarButton
              label={toolbar.orderedList}
              active={editor?.isActive("orderedList")}
              disabled={!editor || disabled}
              onClick={() => editor?.chain().focus().toggleOrderedList().run()}
            >
              <ListOrdered className="size-4" />
            </ToolbarButton>

            <ToolbarDivider />

            <ToolbarButton
              label={toolbar.blockquote}
              active={editor?.isActive("blockquote")}
              disabled={!editor || disabled}
              onClick={() => editor?.chain().focus().toggleBlockquote().run()}
            >
              <Quote className="size-4" />
            </ToolbarButton>
            <ToolbarButton
              label={toolbar.codeBlock}
              active={editor?.isActive("codeBlock")}
              disabled={!editor || disabled}
              onClick={() => editor?.chain().focus().toggleCodeBlock().run()}
            >
              <Code2 className="size-4" />
            </ToolbarButton>
            <ToolbarButton
              label={toolbar.horizontalRule}
              disabled={!editor || disabled}
              onClick={() => editor?.chain().focus().setHorizontalRule().run()}
            >
              <Minus className="size-4" />
            </ToolbarButton>

            <ToolbarDivider />

            <Popover
              open={isLinkOpen}
              onOpenChange={(open) => {
                setIsLinkOpen(open)
                setLinkError(null)
                if (open) {
                  setLinkUrl(editor?.getAttributes("link").href ?? "")
                }
              }}
            >
              <PopoverToolbarButton
                label={toolbar.link}
                active={editor?.isActive("link")}
                disabled={!editor || disabled}
              >
                <Link2 className="size-4" />
              </PopoverToolbarButton>
              <PopoverContent align="start" className="w-80 space-y-3">
                <Input
                  value={linkUrl}
                  disabled={disabled}
                  onChange={(event) => {
                    setLinkUrl(event.target.value)
                    setLinkError(null)
                  }}
                  placeholder={labels.linkPlaceholder}
                />
                {linkError ? (
                  <p className="text-sm text-destructive">{linkError}</p>
                ) : null}
                <div className="flex justify-end gap-2">
                  <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    onClick={() => setIsLinkOpen(false)}
                  >
                    {labels.cancel}
                  </Button>
                  <Button type="button" size="sm" onClick={applyLink}>
                    {labels.applyLink}
                  </Button>
                </div>
              </PopoverContent>
            </Popover>

            <ToolbarButton
              label={
                isUploadingInlineImage ? labels.uploadingImage : toolbar.image
              }
              disabled={!editor || disabled || isUploadingInlineImage}
              onClick={() => imageInputRef.current?.click()}
            >
              {isUploadingInlineImage ? (
                <Loader2 className="size-4 animate-spin" />
              ) : (
                <ImagePlus className="size-4" />
              )}
            </ToolbarButton>
            <Input
              ref={imageInputRef}
              type="file"
              accept="image/*"
              disabled={disabled || isUploadingInlineImage}
              className="hidden"
              onChange={(event) => {
                const file = event.target.files?.[0]
                void handleInlineImageUpload(file)
                event.target.value = ""
              }}
            />

            <ToolbarDivider />

            <ToolbarButton
              label={toolbar.undo}
              disabled={
                !editor ||
                disabled ||
                !editor.can().chain().focus().undo().run()
              }
              onClick={() => editor?.chain().focus().undo().run()}
            >
              <Undo2 className="size-4" />
            </ToolbarButton>
            <ToolbarButton
              label={toolbar.redo}
              disabled={
                !editor ||
                disabled ||
                !editor.can().chain().focus().redo().run()
              }
              onClick={() => editor?.chain().focus().redo().run()}
            >
              <Redo2 className="size-4" />
            </ToolbarButton>
          </div>
          {inlineError ? (
            <p className="mt-3 text-sm text-destructive">{inlineError}</p>
          ) : null}
        </div>

        <EditorContent editor={editor} />
      </div>

      <div className="flex flex-wrap items-center justify-between gap-2">
        {hasCharacterError ? (
          <p className="text-sm text-destructive">
            {formatMessage(labels.maxCharactersExceeded, {
              max: maxCharacters,
            })}
          </p>
        ) : (
          <span aria-hidden="true" />
        )}
        <p className={cn("ml-auto text-sm", counterClassName)}>
          {formatMessage(labels.characterCounter, {
            count: characterCount,
            max: maxCharacters,
          })}
        </p>
      </div>
    </div>
  )
}
