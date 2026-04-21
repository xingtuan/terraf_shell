import { generateHTML } from "@tiptap/html"

import {
  buildCommunityTiptapExtensions,
  isRichTextDocument,
} from "@/lib/community-rich-text"

type PostRendererProps = {
  contentJson?: Record<string, unknown> | null
  content?: string | null
}

export function PostRenderer({ contentJson, content }: PostRendererProps) {
  if (contentJson && isRichTextDocument(contentJson)) {
    const html = generateHTML(
      contentJson,
      buildCommunityTiptapExtensions(),
    )

    return (
      <div
        className="prose prose-neutral max-w-none"
        dangerouslySetInnerHTML={{ __html: html }}
      />
    )
  }

  return (
    <div className="prose prose-neutral max-w-none">
      <pre className="whitespace-pre-wrap font-sans text-base leading-7 text-foreground">
        {content ?? ""}
      </pre>
    </div>
  )
}
