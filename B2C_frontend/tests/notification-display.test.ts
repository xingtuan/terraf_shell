import { describe, it } from "node:test"
import assert from "node:assert/strict"

import {
  getNotificationBody,
  getNotificationExcerpt,
  getNotificationTitle,
  isSystemAnnouncement,
} from "../lib/notification-display.ts"
import type { UserNotification } from "../lib/types.ts"

function notification(
  overrides: Partial<UserNotification>,
): UserNotification {
  return {
    id: 1,
    type: "system_announcement",
    is_read: false,
    data: {},
    ...overrides,
  }
}

describe("notification display helpers", () => {
  it("detects system announcements", () => {
    assert.equal(
      isSystemAnnouncement(notification({ type: "system_announcement" })),
      true,
    )
    assert.equal(isSystemAnnouncement(notification({ type: "comment" })), false)
  })

  it("prefers announcement title and body without mixing body into title", () => {
    const item = notification({
      title: "Platform update",
      body: "Line one\nLine two",
      data: {
        title: "Data title",
        body: "Data body",
        message: "Data message",
      },
    })

    assert.equal(getNotificationTitle(item, "Fallback"), "Platform update")
    assert.equal(getNotificationBody(item), "Line one\nLine two")
  })

  it("falls back to data body and data message for legacy payloads", () => {
    assert.equal(
      getNotificationBody(
        notification({
          body: null,
          data: {
            body: "Body from data",
            message: "Message from data",
          },
        }),
      ),
      "Body from data",
    )

    assert.equal(
      getNotificationBody(
        notification({
          body: null,
          data: {
            message: "Message from data",
          },
        }),
      ),
      "Message from data",
    )
  })

  it("returns a compact excerpt for bell lists", () => {
    assert.equal(
      getNotificationExcerpt(
        notification({
          body: "This is a long announcement body with multiple words.",
        }),
        24,
      ),
      "This is a long announ...",
    )
  })
})
