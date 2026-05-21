const allowedTags = new Set([
  "p",
  "br",
  "strong",
  "em",
  "ul",
  "ol",
  "li",
  "h1",
  "h2",
  "h3",
  "h4",
  "h5",
  "h6",
  "blockquote",
  "a",
])

const dangerousBlocks =
  /<\s*(script|style|iframe|object|embed)\b[^>]*>[\s\S]*?<\s*\/\s*\1\s*>/gi

export function sanitizeLegalHtml(value?: string | null) {
  const html = value?.trim()

  if (!html) {
    return null
  }

  const sanitized = html
    .replace(dangerousBlocks, "")
    .replace(/<!--[\s\S]*?-->/g, "")
    .replace(/<\/?([a-z0-9-]+)([^>]*)>/gi, (match, rawTag, rawAttributes) => {
      const tag = rawTag.toLowerCase()

      if (!allowedTags.has(tag)) {
        return ""
      }

      if (/^<\s*\//.test(match)) {
        return tag === "br" ? "" : `</${tag}>`
      }

      if (tag === "br") {
        return "<br>"
      }

      if (tag !== "a") {
        return `<${tag}>`
      }

      const href = readAttribute(rawAttributes, "href")
      const title = readAttribute(rawAttributes, "title")
      const attrs = ["rel=\"noopener noreferrer\""]

      if (href && isSafeLink(href)) {
        attrs.unshift(`href="${escapeAttribute(href)}"`)
      }

      if (title) {
        attrs.push(`title="${escapeAttribute(title)}"`)
      }

      return `<a ${attrs.join(" ")}>`
    })
    .trim()

  const visibleText = sanitized
    .replace(/<[^>]+>/g, "")
    .replace(/&nbsp;/gi, " ")
    .trim()

  return visibleText ? sanitized : null
}

function readAttribute(attributes: string, name: string) {
  const match = attributes.match(
    new RegExp(`${name}\\s*=\\s*(?:"([^"]*)"|'([^']*)'|([^\\s"'>]+))`, "i"),
  )

  return match?.[1] ?? match?.[2] ?? match?.[3] ?? null
}

function isSafeLink(href: string) {
  const trimmed = href.trim()

  if (!trimmed || /^[\u0000-\u001f]/.test(trimmed)) {
    return false
  }

  if (trimmed.startsWith("#") || trimmed.startsWith("/")) {
    return true
  }

  try {
    return ["http:", "https:", "mailto:", "tel:"].includes(new URL(trimmed).protocol)
  } catch {
    return false
  }
}

function escapeAttribute(value: string) {
  return value
    .replace(/&/g, "&amp;")
    .replace(/"/g, "&quot;")
    .replace(/</g, "&lt;")
    .replace(/>/g, "&gt;")
}
