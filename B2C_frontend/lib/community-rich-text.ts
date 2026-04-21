import type { Extensions, JSONContent } from "@tiptap/core"
import CharacterCount from "@tiptap/extension-character-count"
import CodeBlockLowlight from "@tiptap/extension-code-block-lowlight"
import Image from "@tiptap/extension-image"
import Link from "@tiptap/extension-link"
import Placeholder from "@tiptap/extension-placeholder"
import Underline from "@tiptap/extension-underline"
import StarterKit from "@tiptap/starter-kit"
import { common, createLowlight } from "lowlight"

const lowlight = createLowlight(common)

export const EMPTY_RICH_TEXT_DOCUMENT: JSONContent = {
  type: "doc",
  content: [{ type: "paragraph" }],
}

type CommunityTiptapOptions = {
  placeholder?: string
  maxCharacters?: number
  includeEditorExtensions?: boolean
}

export function isRichTextDocument(value: unknown): value is JSONContent {
  return value !== null && typeof value === "object" && !Array.isArray(value)
    ? (value as { type?: unknown }).type === "doc"
    : false
}

export function normalizeRichTextDocument(
  value?: Record<string, unknown> | JSONContent | null,
): JSONContent {
  return isRichTextDocument(value) ? value : EMPTY_RICH_TEXT_DOCUMENT
}

export function createRichTextDocumentFromText(value?: string | null): JSONContent {
  const lines = (value ?? "")
    .split(/\r?\n/)
    .map((line) => line.trim())
    .filter(Boolean)

  if (lines.length === 0) {
    return EMPTY_RICH_TEXT_DOCUMENT
  }

  return {
    type: "doc",
    content: lines.map((line) => ({
      type: "paragraph",
      content: [{ type: "text", text: line }],
    })),
  }
}

export function buildCommunityTiptapExtensions(
  options: CommunityTiptapOptions = {},
): Extensions {
  const extensions: Extensions = [
    StarterKit.configure({
      codeBlock: false,
      heading: {
        levels: [1, 2, 3],
      },
    }),
    Underline,
    Link.configure({
      autolink: true,
      linkOnPaste: true,
      openOnClick: false,
      HTMLAttributes: {
        class: "text-primary underline underline-offset-4",
        rel: "noopener noreferrer nofollow",
        target: "_blank",
      },
    }),
    Image.configure({
      allowBase64: false,
      HTMLAttributes: {
        class: "rounded-2xl border border-border/60",
      },
    }),
    CodeBlockLowlight.configure({
      lowlight,
      HTMLAttributes: {
        class: "rounded-2xl bg-slate-950 p-4 text-slate-50",
      },
    }),
  ]

  if (options.includeEditorExtensions !== false) {
    extensions.push(
      Placeholder.configure({
        placeholder: options.placeholder ?? "Write your post...",
      }),
      CharacterCount.configure({
        limit: options.maxCharacters ?? 10000,
      }),
    )
  }

  return extensions
}
