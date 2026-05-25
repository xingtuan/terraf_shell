import { describe, it } from "node:test"
import assert from "node:assert/strict"
import { readFileSync } from "node:fs"
import { join } from "node:path"

function readSource(path: string) {
  return readFileSync(join(process.cwd(), path), "utf8")
}

describe("community public settings wiring", () => {
  it("types public settings with a community object", () => {
    const source = readSource("lib/api/public-settings.ts")

    assert.match(source, /export type CommunityPublicSettings/)
    assert.match(source, /community: CommunityPublicSettings/)
    assert.match(source, /allowed_extensions: string\[\]/)
    assert.match(source, /default_funding_support_button_text: string/)
  })

  it("post editor reads upload and link limits from public settings", () => {
    const source = [
      readSource("components/community/CreatePostPanel.tsx"),
      readSource("components/community/community-post-composer.tsx"),
      readSource("components/community/community-post-editor-dialog.tsx"),
    ].join("\n")

    assert.match(source, /getPublicSettings\(\)/)
    assert.match(source, /communitySettings\.max_files/)
    assert.match(source, /communitySettings\.max_file_size_kb/)
    assert.match(source, /communitySettings\.allowed_extensions/)
    assert.match(source, /communitySettings\.max_external_links/)
    assert.match(source, /messages\.form\.uploadLimitsHint/)
  })

  it("front-end validation has translated messages for file and link limits", () => {
    for (const locale of ["en", "zh", "ko"]) {
      const messages = JSON.parse(
        readSource(`messages/${locale}.json`),
      ) as {
        community: {
          form: Record<string, string>
          richEditor: Record<string, string>
        }
      }

      assert.ok(messages.community.form.imagesMaxDynamic)
      assert.ok(messages.community.form.fileTooLarge)
      assert.ok(messages.community.form.invalidFileType)
      assert.ok(messages.community.form.externalLinksMax)
      assert.ok(messages.community.richEditor.fileTooLarge)
      assert.ok(messages.community.richEditor.invalidFileType)
    }
  })

  it("funding buttons use API-provided default text before translation fallback", () => {
    const cardSource = readSource("components/community/PostCard.tsx")
    const detailSource = readSource("components/community/community-post-detail.tsx")

    assert.match(cardSource, /post\.support_button_text\?\.trim\(\) \|\| messages\.post\.supportIdea/)
    assert.match(detailSource, /post\?\.support_button_text\?\.trim\(\) \|\| messages\.post\.supportIdea/)
  })

  it("mock community idea submission still reads public community settings", () => {
    const source = readSource("lib/api/community.ts")

    assert.match(source, /getPublicSettings\(\)/)
    assert.match(source, /communitySettings\.max_external_links/)
    assert.match(source, /externalLinksMax/)
  })
})
