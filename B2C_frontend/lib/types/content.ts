export interface SiteSection {
  id: number
  page: string
  section: string
  locale: string
  title: string | null
  subtitle: string | null
  body: string | null
  cta_label: string | null
  cta_url: string | null
  image_url: string | null
  metadata: Record<string, unknown> | null
}

export interface MaterialProperty {
  id: number
  key: string
  label: string
  value: string
  comparison: string
  icon: string | null
  sort_order: number
}

export interface ProcessStep {
  id: number
  step_number: number
  title: string
  body: string
  icon: string | null
}

export interface Certification {
  id: number
  key: string
  label: string
  value: string
  description: string | null
  badge_color: string | null
  sort_order: number
}

export interface MaterialContent {
  sections: Record<string, SiteSection>
  properties: MaterialProperty[]
  process: ProcessStep[]
  certifications: Certification[]
}
