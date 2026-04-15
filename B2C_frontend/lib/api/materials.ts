import {
  normalizeMaterialDetail,
  normalizeMaterialSummary,
} from "@/lib/api/adapters"
import { requestApi } from "@/lib/api/client"
import { ensureArray, normalizeMaterialSpecIcon } from "@/lib/api/normalizers"
import { materialSpecRecords } from "@/lib/data/materials"
import { pickLocalizedValue, type Locale } from "@/lib/i18n"
import type { MaterialDetail, MaterialSpec, MaterialSummary } from "@/lib/types"

type ListMaterialsParams = {
  featured?: boolean
}

type ApiRequestOverrides = {
  baseUrl?: string
}

function getFallbackSpecs(locale: Locale): MaterialSpec[] {
  return materialSpecRecords.map((spec) => ({
    id: spec.id,
    icon: normalizeMaterialSpecIcon(spec.icon),
    label: pickLocalizedValue(spec.label, locale),
    value: pickLocalizedValue(spec.value, locale),
    detail: pickLocalizedValue(spec.detail, locale),
  }))
}

export async function listMaterials(
  params: ListMaterialsParams = {},
  options: ApiRequestOverrides = {},
) {
  const response = await requestApi<MaterialSummary[]>("/materials", {
    query: params,
    baseUrl: options.baseUrl,
  })

  return ensureArray(response.data).map(normalizeMaterialSummary)
}

export async function getMaterial(
  identifier: string,
  options: ApiRequestOverrides = {},
) {
  const response = await requestApi<MaterialDetail>(
    `/materials/${encodeURIComponent(identifier)}`,
    {
      baseUrl: options.baseUrl,
    },
  )

  return normalizeMaterialDetail(response.data)
}

export async function getFeaturedMaterial(options: ApiRequestOverrides = {}) {
  let materials = await listMaterials({ featured: true }, options)

  if (materials.length === 0) {
    materials = await listMaterials({}, options)
  }

  const primaryMaterial = materials[0] ?? null

  if (!primaryMaterial) {
    return null
  }

  try {
    return await getMaterial(primaryMaterial.slug, options)
  } catch {
    return {
      ...primaryMaterial,
      specs: [],
      story_sections: [],
      applications: [],
    } satisfies MaterialDetail
  }
}

export async function getMaterialSpecs(
  locale: Locale,
  options: ApiRequestOverrides = {},
): Promise<MaterialSpec[]> {
  try {
    const material = await getFeaturedMaterial(options)

    if (material?.specs.length) {
      return material.specs
    }
  } catch {
    // Build-time fallback keeps the page renderable when the local API is down.
  }

  return getFallbackSpecs(locale)
}
