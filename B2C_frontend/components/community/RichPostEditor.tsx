"use client"

import { useEffect, useRef, useState } from "react"

import { EditorContent, useEditor } from "@tiptap/react"

import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover"
import { Separator } from "@/components/ui/separator"
import {
  buildCommunityTiptapExtensions,
  createRichTextDocumentFromText,
  normalizeRichTextDocument,
} from "@/lib/community-rich-text"
import { deleteMedia, uploadMedia } from "@/lib/api/media"

type RichPostEditorProps = {
  content?: Record<string, unknown>
  onChange: (json: Record<string, unknown>, plainText: string) => void
  onCoverImageChange?: (url: string, path: string) => void
  coverImageUrl?: string
  coverImagePath?: string
  placeholder?: string
  maxCharacters?: number
}

type CoverImageState = {
  url: string
  path: string
}

function ToolbarButton({
  active = false,
  disabled = false,
  onClick,
  children,
}: {
  active?: boolean
  disabled?: boolean
  onClick?: () => void
  children: string
}) {
  return (
    <button
      type="button"
      disabled={disabled}
      onClick={onClick}
      className={`rounded-md border px-3 py-2 text-sm transition-colors ${
        active
          ? "border-primary bg-primary text-primary-foreground"
          : "border-border/70 bg-background text-foreground hover:bg-muted"
      } disabled:cursor-not-allowed disabled:opacity-50`}
    >
      {children}
    </button>
  )
}

export function RichPostEditor({
  content,
  onChange,
  onCoverImageChange,
  coverImageUrl = "",
  coverImagePath = "",
  placeholder = "Write your post...",
  maxCharacters = 10000,
}: RichPostEditorProps) {
  const imageInputRef = useRef<HTMLInputElement | null>(null)
  const coverInputRef = useRef<HTMLInputElement | null>(null)
  const onChangeRef = useRef(onChange)
  const [linkUrl, setLinkUrl] = useState("")
  const [isLinkOpen, setIsLinkOpen] = useState(false)
  const [isUploadingInlineImage, setIsUploadingInlineImage] = useState(false)
  const [isUploadingCoverImage, setIsUploadingCoverImage] = useState(false)
  const [inlineError, setInlineError] = useState<string | null>(null)
  const [coverError, setCoverError] = useState<string | null>(null)
  const [isDraggingCover, setIsDraggingCover] = useState(false)
  const [coverImage, setCoverImage] = useState<CoverImageState>({
    url: coverImageUrl,
    path: coverImagePath,
  })

  const editor = useEditor({
    immediatelyRender: false,
    extensions: buildCommunityTiptapExtensions({
      placeholder,
      maxCharacters,
    }),
    content: normalizeRichTextDocument(content),
    editorProps: {
      attributes: {
        class:
          "prose prose-neutral max-w-none min-h-80 px-4 py-4 focus:outline-none",
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
  const counterClassName =
    characterCount > maxCharacters
      ? "text-red-600"
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

    onChangeRef.current(
      editor.getJSON() as Record<string, unknown>,
      editor.getText(),
    )
  }, [editor])

  useEffect(() => {
    setCoverImage({
      url: coverImageUrl,
      path: coverImagePath,
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
    if (!file || !editor) {
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
      setInlineError("Image upload failed. Try again.")
    } finally {
      setIsUploadingInlineImage(false)
    }
  }

  async function handleCoverImageUpload(file?: File | null) {
    if (!file) {
      return
    }

    setCoverError(null)
    setIsUploadingCoverImage(true)

    try {
      const uploaded = await uploadMedia(file, "community")
      const nextCoverImage = {
        url: uploaded.url,
        path: uploaded.path,
      }

      setCoverImage(nextCoverImage)
      onCoverImageChange?.(uploaded.url, uploaded.path)
    } catch {
      setCoverError("Cover image upload failed. Try again.")
    } finally {
      setIsUploadingCoverImage(false)
    }
  }

  async function handleCoverImageRemove() {
    setCoverError(null)

    try {
      if (coverImage.path) {
        await deleteMedia(coverImage.path)
      }

      setCoverImage({ url: "", path: "" })
      onCoverImageChange?.("", "")
    } catch {
      setCoverError("Cover image removal failed. Try again.")
    }
  }

  function applyLink() {
    if (!editor) {
      return
    }

    const href = linkUrl.trim()

    if (!href) {
      editor.chain().focus().extendMarkRange("link").unsetLink().run()
      setIsLinkOpen(false)
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
  }

  return (
    <div className="space-y-4">
      <div className="space-y-3">
        {coverImage.url ? (
          <div className="relative overflow-hidden rounded-2xl border border-border/70">
            <img
              src={coverImage.url}
              alt="Post cover"
              className="aspect-video w-full object-cover"
            />
            <div className="absolute right-3 top-3">
              <Button
                type="button"
                variant="secondary"
                size="sm"
                onClick={() => {
                  void handleCoverImageRemove()
                }}
              >
                Remove
              </Button>
            </div>
          </div>
        ) : (
          <button
            type="button"
            onClick={() => coverInputRef.current?.click()}
            onDragOver={(event) => {
              event.preventDefault()
              setIsDraggingCover(true)
            }}
            onDragLeave={(event) => {
              event.preventDefault()
              setIsDraggingCover(false)
            }}
            onDrop={(event) => {
              event.preventDefault()
              setIsDraggingCover(false)

              const file = event.dataTransfer.files?.[0]
              void handleCoverImageUpload(file)
            }}
            className={`flex w-full flex-col items-center justify-center rounded-2xl border border-dashed px-6 py-10 text-center transition-colors ${
              isDraggingCover
                ? "border-primary bg-primary/5"
                : "border-border/70 bg-muted/20 hover:bg-muted/40"
            }`}
          >
            <span className="text-sm font-medium text-foreground">
              {isUploadingCoverImage ? "Uploading cover image..." : "Add cover image"}
            </span>
            <span className="mt-2 text-sm text-muted-foreground">
              Drop an image here or click to upload.
            </span>
          </button>
        )}
        <Input
          ref={coverInputRef}
          type="file"
          accept="image/*"
          className="hidden"
          onChange={(event) => {
            const file = event.target.files?.[0]
            void handleCoverImageUpload(file)
            event.target.value = ""
          }}
        />
        {coverError ? <p className="text-sm text-destructive">{coverError}</p> : null}
      </div>

      <div className="overflow-hidden rounded-2xl border border-border/70 bg-background">
        <div className="sticky top-0 z-10 border-b border-border/70 bg-background/95 p-3 backdrop-blur">
          <div className="flex flex-wrap items-center gap-2">
            <ToolbarButton
              active={editor?.isActive("bold")}
              disabled={!editor}
              onClick={() => editor?.chain().focus().toggleBold().run()}
            >
              Bold
            </ToolbarButton>
            <ToolbarButton
              active={editor?.isActive("italic")}
              disabled={!editor}
              onClick={() => editor?.chain().focus().toggleItalic().run()}
            >
              Italic
            </ToolbarButton>
            <ToolbarButton
              active={editor?.isActive("underline")}
              disabled={!editor}
              onClick={() => editor?.chain().focus().toggleUnderline().run()}
            >
              Underline
            </ToolbarButton>
            <ToolbarButton
              active={editor?.isActive("strike")}
              disabled={!editor}
              onClick={() => editor?.chain().focus().toggleStrike().run()}
            >
              Strike
            </ToolbarButton>

            <Separator orientation="vertical" className="mx-1 h-6" />

            <ToolbarButton
              active={editor?.isActive("heading", { level: 1 })}
              disabled={!editor}
              onClick={() =>
                editor?.chain().focus().toggleHeading({ level: 1 }).run()
              }
            >
              H1
            </ToolbarButton>
            <ToolbarButton
              active={editor?.isActive("heading", { level: 2 })}
              disabled={!editor}
              onClick={() =>
                editor?.chain().focus().toggleHeading({ level: 2 }).run()
              }
            >
              H2
            </ToolbarButton>
            <ToolbarButton
              active={editor?.isActive("heading", { level: 3 })}
              disabled={!editor}
              onClick={() =>
                editor?.chain().focus().toggleHeading({ level: 3 }).run()
              }
            >
              H3
            </ToolbarButton>

            <Separator orientation="vertical" className="mx-1 h-6" />

            <ToolbarButton
              active={editor?.isActive("bulletList")}
              disabled={!editor}
              onClick={() => editor?.chain().focus().toggleBulletList().run()}
            >
              Bullet list
            </ToolbarButton>
            <ToolbarButton
              active={editor?.isActive("orderedList")}
              disabled={!editor}
              onClick={() => editor?.chain().focus().toggleOrderedList().run()}
            >
              Ordered list
            </ToolbarButton>

            <Separator orientation="vertical" className="mx-1 h-6" />

            <ToolbarButton
              active={editor?.isActive("blockquote")}
              disabled={!editor}
              onClick={() => editor?.chain().focus().toggleBlockquote().run()}
            >
              Blockquote
            </ToolbarButton>
            <ToolbarButton
              active={editor?.isActive("codeBlock")}
              disabled={!editor}
              onClick={() => editor?.chain().focus().toggleCodeBlock().run()}
            >
              Code block
            </ToolbarButton>
            <ToolbarButton
              disabled={!editor}
              onClick={() => editor?.chain().focus().setHorizontalRule().run()}
            >
              Horizontal rule
            </ToolbarButton>

            <Separator orientation="vertical" className="mx-1 h-6" />

            <Popover
              open={isLinkOpen}
              onOpenChange={(open) => {
                setIsLinkOpen(open)
                if (open) {
                  setLinkUrl(editor?.getAttributes("link").href ?? "")
                }
              }}
            >
              <PopoverTrigger asChild>
                <div>
                  <ToolbarButton
                    active={editor?.isActive("link")}
                    disabled={!editor}
                  >
                    Link
                  </ToolbarButton>
                </div>
              </PopoverTrigger>
              <PopoverContent align="start" className="space-y-3">
                <Input
                  value={linkUrl}
                  onChange={(event) => setLinkUrl(event.target.value)}
                  placeholder="https://example.com"
                />
                <div className="flex justify-end gap-2">
                  <Button
                    type="button"
                    variant="ghost"
                    size="sm"
                    onClick={() => setIsLinkOpen(false)}
                  >
                    Cancel
                  </Button>
                  <Button type="button" size="sm" onClick={applyLink}>
                    Apply
                  </Button>
                </div>
              </PopoverContent>
            </Popover>

            <ToolbarButton
              disabled={!editor || isUploadingInlineImage}
              onClick={() => imageInputRef.current?.click()}
            >
              {isUploadingInlineImage ? "Uploading..." : "Image"}
            </ToolbarButton>
            <Input
              ref={imageInputRef}
              type="file"
              accept="image/*"
              className="hidden"
              onChange={(event) => {
                const file = event.target.files?.[0]
                void handleInlineImageUpload(file)
                event.target.value = ""
              }}
            />

            <Separator orientation="vertical" className="mx-1 h-6" />

            <ToolbarButton
              disabled={!editor || !editor.can().chain().focus().undo().run()}
              onClick={() => editor?.chain().focus().undo().run()}
            >
              Undo
            </ToolbarButton>
            <ToolbarButton
              disabled={!editor || !editor.can().chain().focus().redo().run()}
              onClick={() => editor?.chain().focus().redo().run()}
            >
              Redo
            </ToolbarButton>
          </div>
          {inlineError ? (
            <p className="mt-3 text-sm text-destructive">{inlineError}</p>
          ) : null}
        </div>

        <EditorContent editor={editor} />
      </div>

      <p className={`text-right text-sm ${counterClassName}`}>
        {characterCount} / {maxCharacters} characters
      </p>
    </div>
  )
}
