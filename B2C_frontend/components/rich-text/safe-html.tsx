import { sanitizeArticleHtml } from "@/lib/article-html"
import { cn } from "@/lib/utils"

type SafeHtmlProps = {
  html?: string | null
  className?: string
}

export function SafeHtml({ html, className }: SafeHtmlProps) {
  const sanitized = sanitizeArticleHtml(html)

  if (!sanitized) {
    return null
  }

  return (
    <div
      className={cn(
        "leading-8 text-foreground",
        "[&_p]:mb-4 [&_p]:leading-relaxed",
        "[&_h2]:mt-8 [&_h2]:mb-4 [&_h2]:text-2xl [&_h2]:font-bold",
        "[&_h3]:mt-6 [&_h3]:mb-3 [&_h3]:text-xl [&_h3]:font-semibold",
        "[&_ul]:mb-4 [&_ul]:list-disc [&_ul]:pl-6",
        "[&_ol]:mb-4 [&_ol]:list-decimal [&_ol]:pl-6",
        "[&_li]:mb-1",
        "[&_blockquote]:my-4 [&_blockquote]:border-l-4 [&_blockquote]:border-primary/30 [&_blockquote]:pl-4 [&_blockquote]:italic [&_blockquote]:text-muted-foreground",
        "[&_a]:text-primary [&_a]:underline [&_a]:underline-offset-4",
        "[&_img]:my-4 [&_img]:max-w-full [&_img]:rounded-xl",
        "[&_strong]:font-semibold [&_b]:font-semibold",
        "[&_em]:italic [&_i]:italic",
        "[&_u]:underline",
        className,
      )}
      dangerouslySetInnerHTML={{ __html: sanitized }}
    />
  )
}
