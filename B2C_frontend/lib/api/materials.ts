import { requestApi } from "@/lib/api/client"
import { materialSpecRecords } from "@/lib/data/materials"
import { pickLocalizedValue, type Locale } from "@/lib/i18n"
import type { MaterialDetail, MaterialInfo, MaterialSpec } from "@/lib/types"

type ApiRequestOverrides = {
  baseUrl?: string
  locale?: Locale
}

const materialIcons: Record<string, MaterialSpec["icon"]> = {
  weight: "feather",
  strength: "shield",
  absorption: "badge",
  antibacterial: "leaf",
  grip: "shield",
  otr: "badge",
}

function getFallbackSpecs(locale: Locale): MaterialSpec[] {
  return materialSpecRecords.map((spec) => ({
    id: spec.id,
    icon: spec.icon,
    label: pickLocalizedValue(spec.label, locale),
    value: pickLocalizedValue(spec.value, locale),
    detail: pickLocalizedValue(spec.detail, locale),
  }))
}

export function materialInfoToSpecs(material: MaterialInfo): MaterialSpec[] {
  return material.properties.map((property, index) => ({
    id: property.key,
    key: property.key,
    label: property.label,
    value: property.value,
    detail: property.vs,
    icon: materialIcons[property.key] ?? "badge",
    sort_order: index,
  }))
}

export function materialInfoToDetail(material: MaterialInfo): MaterialDetail {
  const specs = materialInfoToSpecs(material)

  return {
    id: 1,
    title: material.name,
    slug: "oxp",
    headline: material.tagline,
    summary: material.origin,
    story_overview: material.process_steps.map((step) => step.title).join(" -> "),
    science_overview: material.certifications
      .map((certification) => `${certification.label}: ${certification.value}`)
      .join(" | "),
    is_featured: true,
    certifications: material.certifications,
    technical_downloads: material.technical_downloads ?? [],
    specs,
    story_sections: material.process_steps.map((step, index) => ({
      id: step.step,
      title: step.title,
      content: step.body,
      sort_order: index,
    })),
    applications: [
      ...material.models.map((model, index) => ({
        id: `model-${model.id}`,
        title: model.name,
        subtitle: model.finish,
        description: model.description,
        sort_order: index,
      })),
      ...material.colors.map((color, index) => ({
        id: `color-${color.id}`,
        title: color.name,
        subtitle: color.temp,
        description: color.description,
        sort_order: material.models.length + index,
      })),
    ].slice(0, 4),
  }
}

export async function getMaterialInfo(options: ApiRequestOverrides = {}) {
  return requestApi<MaterialInfo>("/materials", {
    query: {
      locale: options.locale,
    },
    baseUrl: options.baseUrl,
  })
}

export async function getFeaturedMaterial(options: ApiRequestOverrides = {}) {
  const response = await getMaterialInfo(options)

  return materialInfoToDetail(response.data)
}

export async function getMaterial(
  _identifier: string,
  options: ApiRequestOverrides = {},
) {
  const response = await getMaterialInfo(options)

  return materialInfoToDetail(response.data)
}

export async function getMaterialSpecs(
  locale: Locale,
  options: ApiRequestOverrides = {},
): Promise<MaterialSpec[]> {
  try {
    const response = await getMaterialInfo({
      ...options,
      locale: options.locale ?? locale,
    })

    return materialInfoToSpecs(response.data)
  } catch {
    return getFallbackSpecs(locale)
  }
}
