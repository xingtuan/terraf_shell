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
const SAFE_LINK_REL = "noopener noreferrer nofollow"
const SAFE_LINK_TARGET = "_blank"

const ALLOWED_NODE_TYPES = new Set([
  "doc",
  "paragraph",
  "text",
  "heading",
  "bulletList",
  "orderedList",
  "listItem",
  "blockquote",
  "codeBlock",
  "horizontalRule",
  "hardBreak",
  "image",
])

const ALLOWED_MARK_TYPES = new Set([
  "bold",
  "italic",
  "underline",
  "strike",
  "code",
  "link",
])

export const EMPTY_RICH_TEXT_DOCUMENT: JSONContent = {
  type: "doc",
  content: [{ type: "paragraph" }],
}

type CommunityTiptapOptions = {
  placeholder?: string
  maxCharacters?: number
  includeEditorExtensions?: boolean
}

type JsonRecord = Record<string, unknown>

function isRecord(value: unknown): value is JsonRecord {
  return value !== null && typeof value === "object" && !Array.isArray(value)
}

function emptyRichTextDocument(): JSONContent {
  return {
    type: "doc",
    content: [{ type: "paragraph" }],
  }
}

export function isRichTextDocument(value: unknown): value is JSONContent {
  return sanitizeRichTextDocument(value) !== null
}

export function isSafeRichTextUrl(
  value: unknown,
  options: { allowRelative?: boolean } = {},
): value is string {
  if (typeof value !== "string") {
    return false
  }

  const url = value.trim()

  if (!url || /[\u0000-\u001f\u007f\s]/.test(url)) {
    return false
  }

  if (url.startsWith("//")) {
    return false
  }

  if (options.allowRelative && (url.startsWith("/") || url.startsWith("#"))) {
    return true
  }

  if (!/^[a-z][a-z\d+.-]*:/i.test(url)) {
    return Boolean(options.allowRelative)
  }

  try {
    const parsed = new URL(url)

    return parsed.protocol === "http:" || parsed.protocol === "https:"
  } catch {
    return false
  }
}

export function normalizeRichTextLinkHref(value: string): string | null {
  const trimmed = value.trim()

  if (!trimmed) {
    return null
  }

  if (
    trimmed.startsWith("/") ||
    trimmed.startsWith("#") ||
    /^[a-z][a-z\d+.-]*:/i.test(trimmed)
  ) {
    return isSafeRichTextUrl(trimmed, { allowRelative: true }) ? trimmed : null
  }

  const normalized = `https://${trimmed}`

  return isSafeRichTextUrl(normalized) ? normalized : null
}

function stringAttribute(value: unknown): string | undefined {
  return typeof value === "string" && value.trim() ? value : undefined
}

function sanitizeMarks(value: unknown): JSONContent["marks"] | undefined {
  if (value === undefined) {
    return undefined
  }

  if (!Array.isArray(value)) {
    return undefined
  }

  const marks = value.flatMap((mark): NonNullable<JSONContent["marks"]> => {
    if (!isRecord(mark) || typeof mark.type !== "string") {
      return []
    }

    if (!ALLOWED_MARK_TYPES.has(mark.type)) {
      return []
    }

    if (mark.type !== "link") {
      return [{ type: mark.type }]
    }

    const attrs = isRecord(mark.attrs) ? mark.attrs : {}
    const href = stringAttribute(attrs.href)

    if (!href || !isSafeRichTextUrl(href, { allowRelative: true })) {
      return []
    }

    return [
      {
        type: "link",
        attrs: {
          href,
          target: SAFE_LINK_TARGET,
          rel: SAFE_LINK_REL,
        },
      },
    ]
  })

  return marks.length > 0 ? marks : undefined
}

function sanitizeContent(value: unknown): JSONContent[] | null {
  if (value === undefined) {
    return []
  }

  if (!Array.isArray(value)) {
    return null
  }

  const content: JSONContent[] = []

  for (const child of value) {
    const sanitizedChild = sanitizeNode(child)

    if (sanitizedChild === null) {
      return null
    }

    content.push(sanitizedChild)
  }

  return content
}

function sanitizeNode(value: unknown): JSONContent | null {
  if (!isRecord(value) || typeof value.type !== "string") {
    return null
  }

  if (!ALLOWED_NODE_TYPES.has(value.type)) {
    return null
  }

  if (value.type === "text") {
    if (typeof value.text !== "string") {
      return null
    }

    const marks = sanitizeMarks(value.marks)

    return {
      type: "text",
      text: value.text,
      ...(marks ? { marks } : {}),
    }
  }

  if (value.type === "image") {
    const attrs = isRecord(value.attrs) ? value.attrs : {}
    const src = stringAttribute(attrs.src)

    if (!src || !isSafeRichTextUrl(src, { allowRelative: true })) {
      return null
    }

    return {
      type: "image",
      attrs: {
        src,
        alt: typeof attrs.alt === "string" ? attrs.alt : null,
        title: typeof attrs.title === "string" ? attrs.title : null,
      },
    }
  }

  if (value.type === "horizontalRule" || value.type === "hardBreak") {
    return {
      type: value.type,
    }
  }

  const content = sanitizeContent(value.content)

  if (content === null) {
    return null
  }

  if (value.type === "doc") {
    return content.length > 0
      ? { type: "doc", content }
      : emptyRichTextDocument()
  }

  if (value.type === "heading") {
    const attrs = isRecord(value.attrs) ? value.attrs : {}
    const level = Number(attrs.level)

    if (![1, 2, 3].includes(level)) {
      return null
    }

    return {
      type: "heading",
      attrs: { level },
      ...(content.length > 0 ? { content } : {}),
    }
  }

  if (value.type === "orderedList") {
    const attrs = isRecord(value.attrs) ? value.attrs : {}
    const start = Number(attrs.start)

    return {
      type: "orderedList",
      attrs: Number.isFinite(start) && start > 1 ? { start } : {},
      ...(content.length > 0 ? { content } : {}),
    }
  }

  if (value.type === "codeBlock") {
    const attrs = isRecord(value.attrs) ? value.attrs : {}
    const language = stringAttribute(attrs.language)

    return {
      type: "codeBlock",
      ...(language ? { attrs: { language } } : {}),
      ...(content.length > 0 ? { content } : {}),
    }
  }

  return {
    type: value.type,
    ...(content.length > 0 ? { content } : {}),
  }
}

export function sanitizeRichTextDocument(value: unknown): JSONContent | null {
  const document = sanitizeNode(value)

  return document?.type === "doc" ? document : null
}

export function normalizeRichTextDocument(
  value?: Record<string, unknown> | JSONContent | null,
): JSONContent {
  return sanitizeRichTextDocument(value) ?? emptyRichTextDocument()
}

export function createRichTextDocumentFromText(value?: string | null): JSONContent {
  const lines = (value ?? "").split(/\r?\n/)

  if (lines.length === 0) {
    return emptyRichTextDocument()
  }

  return {
    type: "doc",
    content: lines.map((line) => ({
      type: "paragraph",
      ...(line
        ? {
            content: [{ type: "text", text: line }],
          }
        : {}),
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
      defaultProtocol: "https",
      protocols: ["http", "https"],
      isAllowedUri: (url) => isSafeRichTextUrl(url, { allowRelative: true }),
      shouldAutoLink: (url) => normalizeRichTextLinkHref(url) !== null,
      HTMLAttributes: {
        class: "text-primary underline underline-offset-4",
        rel: SAFE_LINK_REL,
        target: SAFE_LINK_TARGET,
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
        placeholder: options.placeholder ?? "",
      }),
      CharacterCount.configure({
        limit: options.maxCharacters ?? 10000,
      }),
    )
  }

  return extensions
}
