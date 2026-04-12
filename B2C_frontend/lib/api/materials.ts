import { materialSpecRecords } from "@/lib/data/materials"
import { pickLocalizedValue, type Locale } from "@/lib/i18n"
import type { MaterialSpec } from "@/lib/types"

export async function getMaterialSpecs(locale: Locale): Promise<MaterialSpec[]> {
  // TODO: Replace with a backend material-spec endpoint when lab data is available.
  return materialSpecRecords.map((spec) => ({
    id: spec.id,
    icon: spec.icon,
    label: pickLocalizedValue(spec.label, locale),
    value: pickLocalizedValue(spec.value, locale),
    detail: pickLocalizedValue(spec.detail, locale),
  }))
}
