import { describe, it } from "node:test"
import assert from "node:assert/strict"
import { readFileSync } from "node:fs"
import { join } from "node:path"

import { payloadArray } from "../lib/payload-array.ts"

describe("payloadArray", () => {
  it("returns real arrays and sorts by sort_order", () => {
    const result = payloadArray(
      {
        payload: {
          items: [
            { title: "Second", sort_order: 2 },
            { title: "First", sort_order: 1 },
          ],
        },
      },
      "items",
    )

    assert.deepEqual(result, [
      { title: "First", sort_order: 1 },
      { title: "Second", sort_order: 2 },
    ])
  })

  it("returns object-map values and sorts by order", () => {
    const result = payloadArray(
      {
        payload: {
          cards: {
            "41af71a6-7f28-4e50-a248-d7f88c70b722": {
              title: "Second",
              order: 20,
            },
            "304f3bf5-79c1-4902-9072-8455bbb3c218": {
              title: "First",
              order: 10,
            },
          },
        },
      },
      "cards",
    )

    assert.deepEqual(result, [
      { title: "First", order: 10 },
      { title: "Second", order: 20 },
    ])
  })

  it("returns an empty array for null and undefined payloads", () => {
    assert.deepEqual(payloadArray(null, "items"), [])
    assert.deepEqual(payloadArray(undefined, "items"), [])
    assert.deepEqual(payloadArray({ payload: null }, "items"), [])
    assert.deepEqual(payloadArray({ payload: undefined }, "items"), [])
  })
})

describe("CMS content builders", () => {
  it("wires material facts, contact details, and lead form copy to CMS payload fields", () => {
    const pageContentSource = readFileSync(
      join(process.cwd(), "lib", "page-content.ts"),
      "utf8",
    )
    const formSource = readFileSync(
      join(process.cwd(), "components", "sections", "b2b-inquiry-form.tsx"),
      "utf8",
    )
    const contactSource = readFileSync(
      join(process.cwd(), "components", "sections", "contact-details.tsx"),
      "utf8",
    )

    assert.match(pageContentSource, /export function buildMaterialFactSpecs/)
    assert.match(pageContentSource, /export function hasCmsFactCards/)
    assert.match(pageContentSource, /payloadArray\(scienceSection, "info_cards"\)/)
    assert.match(pageContentSource, /payloadArray\(section, "metrics"\)/)
    assert.match(pageContentSource, /payloadArray\(section, "items"\)/)
    assert.match(pageContentSource, /localizedPayloadString\(payload,\s*"sheet_description"/)
    assert.match(pageContentSource, /localizedPayloadString\(\s*payload,\s*"sheet_cta_label"/)
    assert.match(pageContentSource, /fallbackSpecs: MaterialSpec\[\] = \[\]/)
    assert.match(pageContentSource, /payloadArray\(section, "interest_options"\)/)
    assert.match(pageContentSource, /payload\.panel_copy/)
    assert.match(pageContentSource, /payloadList\(payload\?\.field_settings\)/)
    assert.match(pageContentSource, /payloadList\(payload\?\.custom_fields\)/)
    assert.match(pageContentSource, /localizedLeadFieldString\(\s*payload,\s*"fields"/)
    assert.match(pageContentSource, /localizedLeadFieldString\(\s*payload,\s*"placeholders"/)
    assert.match(pageContentSource, /localizedPayloadRecord\(\s*payload,\s*"validation"/)
    assert.match(pageContentSource, /icon: materialSpecIcon\(rawItem\.icon, index\)/)

    assert.match(formSource, /content\.interestOptionList/)
    assert.match(formSource, /content\.panelCopy\[values\.interestType\]/)
    assert.match(formSource, /activeLeadFields\(content, values\.type\)/)
    assert.match(formSource, /renderCustomField\(customField\)/)
    assert.match(formSource, /metadata:\s*\{\s*\.\.\.metadata,\s*custom_fields:/)
    assert.match(formSource, /customErrorFromApiKey/)
    assert.doesNotMatch(formSource, /getPanelCopy/)
    assert.doesNotMatch(formSource, /Evaluation kits, material notes/)
    assert.doesNotMatch(formSource, /Pellet supply, raw material buying/)

    assert.match(contactSource, /card\.icon/)
  })
})
