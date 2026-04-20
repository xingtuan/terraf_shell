import { CertificationsGrid } from "@/components/sections/certifications-grid"
import { ContentBlockSection } from "@/components/sections/content-block"
import { ContentHeroSection } from "@/components/sections/content-hero"
import { MaterialPropertiesGrid } from "@/components/sections/material-properties-grid"
import { ProcessStepsGrid } from "@/components/sections/process-steps-grid"
import { getMaterialContent } from "@/lib/api/content"
import { getServerApiBaseUrl } from "@/lib/api/server-base-url"
import { resolveLocale } from "@/lib/resolve-locale"

type MaterialPageProps = {
  params: Promise<{ locale: string }>
}

export default async function MaterialPage({ params }: MaterialPageProps) {
  const locale = await resolveLocale(params)
  const apiBaseUrl = await getServerApiBaseUrl()
  const data = await getMaterialContent(locale, { baseUrl: apiBaseUrl })

  return (
    <>
      <ContentHeroSection
        title={data.sections.hero?.title}
        subtitle={data.sections.hero?.subtitle}
      />
      <ContentBlockSection
        title={data.sections.origin?.title}
        body={data.sections.origin?.body}
      />
      <MaterialPropertiesGrid
        title={data.sections.properties_intro?.title}
        subtitle={data.sections.properties_intro?.subtitle}
        properties={data.properties}
      />
      <ProcessStepsGrid
        title={data.sections.process_intro?.title}
        subtitle={data.sections.process_intro?.subtitle}
        steps={data.process}
      />
      <CertificationsGrid
        title={data.sections.certifications_intro?.title}
        subtitle={data.sections.certifications_intro?.subtitle}
        certifications={data.certifications}
      />
    </>
  )
}
