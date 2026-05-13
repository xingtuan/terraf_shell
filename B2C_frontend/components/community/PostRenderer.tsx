import { generateHTML } from "@tiptap/html"

import {
  buildCommunityTiptapExtensions,
  sanitizeRichTextDocument,
} from "@/lib/community-rich-text"
import { cn } from "@/lib/utils"

type PostRendererProps = {
  contentJson?: Record<string, unknown> | null
  content?: string | null
  compact?: boolean
  className?: string
}

function decodeCodePoint(value: string, radix = 10) {
  const codePoint = Number.parseInt(value, radix)

  return Number.isFinite(codePoint) && codePoint >= 0 && codePoint <= 0x10ffff
    ? String.fromCodePoint(codePoint)
    : ""
}

function decodeHtmlEntities(value: string) {
  return value
    .replace(/&nbsp;/gi, " ")
    .replace(/&amp;/gi, "&")
    .replace(/&lt;/gi, "<")
    .replace(/&gt;/gi, ">")
    .replace(/&quot;/gi, '"')
    .replace(/&#39;/g, "'")
    .replace(/&#(\d+);/g, (_, code) => decodeCodePoint(code))
    .replace(/&#x([a-f\d]+);/gi, (_, code) => decodeCodePoint(code, 16))
}

function plainTextFromMaybeHtml(value?: string | null) {
  const content = value ?? ""

  if (!/<[a-z][\s\S]*>/i.test(content)) {
    return content
  }

  return decodeHtmlEntities(
    content
      .replace(/<br\s*\/?>/gi, "\n")
      .replace(/<\/(p|div|h[1-6]|blockquote|pre)>/gi, "\n\n")
      .replace(/<li[^>]*>/gi, "- ")
      .replace(/<\/li>/gi, "\n")
      .replace(/<[^>]+>/g, "")
      .replace(/\n{3,}/g, "\n\n"),
  ).trim()
}

export function PostRenderer({
  contentJson,
  content,
  compact = false,
  className,
}: PostRendererProps) {
  const proseClassName = cn(
    "prose prose-neutral max-w-none",
    compact &&
      "prose-sm max-h-36 overflow-hidden [&_img]:hidden [&_pre]:max-h-24 [&_pre]:overflow-hidden",
    className,
  )

  const sanitizedDocument = sanitizeRichTextDocument(contentJson)

  if (sanitizedDocument) {
    try {
      const html = generateHTML(
        sanitizedDocument,
        buildCommunityTiptapExtensions({ includeEditorExtensions: false }),
      )

      return (
        <div
          className={proseClassName}
          dangerouslySetInnerHTML={{ __html: html }}
        />
      )
    } catch {
      // Fall through to escaped plain text when a malformed document reaches the client.
    }
  }

  return (
    <div className={proseClassName}>
      <pre className="whitespace-pre-wrap font-sans text-base leading-7 text-foreground">
        {plainTextFromMaybeHtml(content)}
      </pre>
    </div>
  )
}
