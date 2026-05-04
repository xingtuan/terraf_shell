import { describe, it } from "node:test"
import assert from "node:assert/strict"

import {
  ApiError,
  getErrorMessage,
  getFieldErrors,
  getLocalizedErrorMessage,
} from "./client.ts"

// ── helpers ──────────────────────────────────────────────────────────────────

function makeValidationError(
  message: string,
  errors: Record<string, string[]>,
) {
  return new ApiError(message, 422, errors)
}

const T = {
  apiUnavailable: "API 暂时不可用。",
  requestFailed: "请求无法完成。",
  validation: "请检查输入并重试。",
}

// ── ApiError class ────────────────────────────────────────────────────────────

describe("ApiError", () => {
  it("firstFieldError returns null when no errors", () => {
    const err = new ApiError("Something failed", 500)
    assert.equal(err.firstFieldError(), null)
  })

  it("firstFieldError returns first field and its first message", () => {
    const err = makeValidationError("The given data was invalid.", {
      email: ["The email has already been taken.", "Must be a valid email."],
      password: ["Too short."],
    })
    const result = err.firstFieldError()
    assert.notEqual(result, null)
    assert.equal(result?.field, "email")
    assert.equal(result?.message, "The email has already been taken.")
  })

  it("flattenedFieldErrors returns empty array when no errors", () => {
    const err = new ApiError("fail", 400)
    assert.deepEqual(err.flattenedFieldErrors(), [])
  })

  it("flattenedFieldErrors concatenates all field messages", () => {
    const err = makeValidationError("The given data was invalid.", {
      email: ["Email taken."],
      password: ["Too short.", "Must contain a number."],
    })
    assert.deepEqual(err.flattenedFieldErrors(), [
      "Email taken.",
      "Too short.",
      "Must contain a number.",
    ])
  })
})

// ── getErrorMessage ───────────────────────────────────────────────────────────

describe("getErrorMessage", () => {
  it("returns first field error when errors are present", () => {
    const err = makeValidationError("The given data was invalid.", {
      email: ["The email has already been taken."],
    })
    assert.equal(getErrorMessage(err), "The email has already been taken.")
  })

  it("field errors take priority over generic validation message", () => {
    const err = makeValidationError("Validation failed.", {
      password: ["Password is too short."],
    })
    assert.equal(getErrorMessage(err), "Password is too short.")
  })

  it("returns generic fallback when errors is empty and message is generic wrapper", () => {
    const err = new ApiError("The given data was invalid.", 422, {})
    assert.equal(getErrorMessage(err), "Please check your input and try again.")
  })

  it("returns error.message when it is specific (no field errors)", () => {
    const err = new ApiError("Invalid credentials.", 401)
    assert.equal(getErrorMessage(err), "Invalid credentials.")
  })

  it("falls back to string for plain Error", () => {
    assert.equal(getErrorMessage(new Error("boom")), "boom")
  })

  it("falls back to default string for unknown error type", () => {
    assert.equal(getErrorMessage(null), "The request could not be completed.")
    assert.equal(getErrorMessage(42), "The request could not be completed.")
  })
})

// ── getLocalizedErrorMessage ──────────────────────────────────────────────────

describe("getLocalizedErrorMessage", () => {
  it("maps api_unavailable code to t.apiUnavailable", () => {
    const err = new ApiError("The API is unavailable right now.", 0, undefined, "api_unavailable")
    assert.equal(getLocalizedErrorMessage(err, T), T.apiUnavailable)
  })

  it("maps request_failed code to t.requestFailed", () => {
    const err = new ApiError("The request could not be completed.", 500, undefined, "request_failed")
    assert.equal(getLocalizedErrorMessage(err, T), T.requestFailed)
  })

  it("surfaces first field error when present", () => {
    const err = makeValidationError("The given data was invalid.", {
      email: ["The email has already been taken."],
    })
    assert.equal(
      getLocalizedErrorMessage(err, T),
      "The email has already been taken.",
    )
  })

  it("uses t.validation when message is generic wrapper and no field errors", () => {
    const err = new ApiError("The given data was invalid.", 422, {})
    assert.equal(getLocalizedErrorMessage(err, T), T.validation)
  })

  it("falls back to t.requestFailed for unknown error type", () => {
    assert.equal(getLocalizedErrorMessage("not an error", T), T.requestFailed)
  })
})

// ── getFieldErrors ────────────────────────────────────────────────────────────

describe("getFieldErrors", () => {
  it("returns null for non-ApiError", () => {
    assert.equal(getFieldErrors(new Error("boom")), null)
    assert.equal(getFieldErrors(null), null)
  })

  it("returns null for ApiError without errors", () => {
    const err = new ApiError("fail", 500)
    assert.equal(getFieldErrors(err), null)
  })

  it("returns null for ApiError with empty errors object", () => {
    const err = new ApiError("The given data was invalid.", 422, {})
    assert.equal(getFieldErrors(err), null)
  })

  it("returns map of field → first message", () => {
    const err = makeValidationError("The given data was invalid.", {
      email: ["Email taken.", "Must be valid."],
      password: ["Too short."],
    })
    assert.deepEqual(getFieldErrors(err), {
      email: "Email taken.",
      password: "Too short.",
    })
  })

  it("skips fields with empty message arrays", () => {
    const err = makeValidationError("The given data was invalid.", {
      email: ["Email taken."],
      name: [],
    })
    assert.deepEqual(getFieldErrors(err), { email: "Email taken." })
  })
})
