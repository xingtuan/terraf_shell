import { describe, it } from "node:test"
import assert from "node:assert/strict"
import { readFileSync } from "node:fs"
import { join } from "node:path"

function readSource(path: string) {
  return readFileSync(join(process.cwd(), path), "utf8")
}

describe("notification announcement UI wiring", () => {
  it("normalizes notification title and body from top-level fields and data fallbacks", () => {
    const source = readSource("lib/api/adapters.ts")

    assert.match(source, /notification\.title\s*\?\?[\s\S]*data\.title/)
    assert.match(source, /notification\.body\s*\?\?[\s\S]*data\.body[\s\S]*data\.message/)
  })

  it("renders system announcements in the bell with excerpt and detail dialog", () => {
    const source = readSource("components/community/NotificationBell.tsx")

    assert.match(source, /isSystemAnnouncement\(notification\)/)
    assert.match(source, /getNotificationExcerpt\(notification\)/)
    assert.match(source, /setSelectedAnnouncement\(updatedNotification\)/)
    assert.match(source, /DialogContent/)
    assert.match(source, /whitespace-pre-line/)
    assert.match(source, /selectedAnnouncement\.action_url[\s\S]*resolveNotificationHref/)
  })

  it("keeps system announcements without action URLs inside the panel", () => {
    const source = readSource(
      "components/community/community-notifications-panel.tsx",
    )

    assert.match(source, /isSystemAnnouncement\(notification\)/)
    assert.match(source, /announcement && !notification\.action_url\s*\?\s*null/)
    assert.match(source, /whitespace-pre-line/)
    assert.match(source, /t\.collapse : t\.expand/)
  })
})
