type PayloadSection = {
  payload?: unknown
} | null | undefined

function isRecord(value: unknown): value is Record<string, unknown> {
  return value !== null && typeof value === "object" && !Array.isArray(value)
}

function listOrder(value: unknown): number | null {
  if (!isRecord(value)) {
    return null
  }

  const rawOrder = value.sort_order ?? value.order

  if (typeof rawOrder === "number" && Number.isFinite(rawOrder)) {
    return rawOrder
  }

  if (typeof rawOrder === "string" && rawOrder.trim() !== "") {
    const parsed = Number(rawOrder)

    return Number.isFinite(parsed) ? parsed : null
  }

  return null
}

export function payloadList(value: unknown): unknown[] {
  const items = Array.isArray(value)
    ? [...value]
    : isRecord(value)
      ? Object.values(value)
      : []

  const orderedItems = items.map((item, index) => ({
    item,
    index,
    order: listOrder(item),
  }))

  if (!orderedItems.some((entry) => entry.order !== null)) {
    return items
  }

  return orderedItems
    .sort((a, b) => {
      const orderDifference =
        (a.order ?? Number.MAX_SAFE_INTEGER) -
        (b.order ?? Number.MAX_SAFE_INTEGER)

      return orderDifference || a.index - b.index
    })
    .map((entry) => entry.item)
}

export function payloadArray(section: PayloadSection, field: string): unknown[] {
  const payload = isRecord(section?.payload) ? section.payload : null

  return payloadList(payload?.[field])
}
