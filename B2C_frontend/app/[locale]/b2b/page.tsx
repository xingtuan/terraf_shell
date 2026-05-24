import { Fragment, Suspense, type ReactNode } from "react"

import { PageIntro } from "@/components/page-intro"
import { B2BInquiryFormSection } from "@/components/sections/b2b-inquiry-form"
import { CollaborationSection } from "@/components/sections/collaboration"
import { CredibilitySection } from "@/components/sections/credibility"
import { FinalCtaSection } from "@/components/sections/final-cta"
import { MaterialFactsSection } from "@/components/sections/material-facts"
import {
  B2BAfterSubmitSection,
  B2BApplicationsSection,
  B2BCtaStrip,
  B2BProcessSection,
  PilotProjectsSection,
  TrustAndCredibilitySection,
} from "@/components/sections/trust-and-b2b-sections"
import { getPageSections } from "@/lib/api/page-sections"
import { getServerApiBaseUrl } from "@/lib/api/server-base-url"
import { getLocalizedHref, getMessages } from "@/lib/i18n"
import type { HomeSection } from "@/lib/types"
import {
  buildB2BAfterSubmitContent,
  buildB2BApplicationsContent,
  buildB2BCtaStripContent,
  buildB2BFormContent,
  buildB2BProcessContent,
  buildCollaborationContent,
  buildCredibilityContent,
  buildFinalCtaContent,
  buildMaterialFactSpecs,
  buildMaterialFactsContent,
  buildPageIntroContent,
  buildPilotProjectsContent,
  buildTrustAndCredibilityContent,
} from "@/lib/page-content"
import { resolveLocale } from "@/lib/resolve-locale"

type B2BPageProps = {
  params: Promise<{ locale: string }>
}

function orderedSections(sections: HomeSection[]) {
  return [...sections].sort((a, b) => (a.sort_order ?? 0) - (b.sort_order ?? 0))
}

export default async function B2BPage({ params }: B2BPageProps) {
  const locale = await resolveLocale(params)
  const apiBaseUrl = await getServerApiBaseUrl()
  const messages = getMessages(locale)
  const defaultPrimaryHref = `${getLocalizedHref(locale, "b2b")}?leadType=inquiry#inquiry`
  const defaultSecondaryHref = getLocalizedHref(locale, "material")

  let b2bSections: HomeSection[] = []

  try {
    b2bSections = await getPageSections({ baseUrl: apiBaseUrl, locale, page: "b2b" })
  } catch {
    b2bSections = []
  }

  const b2bRenderers: Record<string, (section: HomeSection) => ReactNode> = {
    intro: (section) => {
      const intro = buildPageIntroContent(
        messages.b2bPage.intro,
        section,
        locale,
        defaultPrimaryHref,
        defaultSecondaryHref,
      )

      return (
        <PageIntro
          eyebrow={intro.eyebrow}
          title={intro.title}
          description={intro.description}
          primaryAction={{
            label: intro.primaryCta,
            href: intro.primaryHref ?? defaultPrimaryHref,
          }}
          secondaryAction={{
            label: intro.secondaryCta,
            href: intro.secondaryHref ?? defaultSecondaryHref,
          }}
        />
      )
    },
    collaboration: (section) => {
      const content = buildCollaborationContent(
        messages.home.collaboration,
        section,
        locale,
      )

      return (
        <CollaborationSection
          locale={locale}
          content={content}
          cardHrefs={content.cardHrefs}
        />
      )
    },
    process: (section) => (
      <B2BProcessSection
        content={buildB2BProcessContent(messages.b2bPage.process, section, locale)}
      />
    ),
    cta_strip: (section) => (
      <B2BCtaStrip
        locale={locale}
        content={buildB2BCtaStripContent(messages.b2bPage.ctaStrip, section, locale)}
      />
    ),
    applications: (section) => (
      <B2BApplicationsSection
        content={buildB2BApplicationsContent(
          messages.b2bPage.applications,
          section,
          locale,
        )}
      />
    ),
    material_facts: (section) => (
      <MaterialFactsSection
        locale={locale}
        content={buildMaterialFactsContent(
          messages.home.materialFacts,
          null,
          section,
          locale,
        )}
        specs={buildMaterialFactSpecs(section, locale)}
        sheetHref={`${getLocalizedHref(locale, "b2b")}?leadType=sample_request#inquiry`}
      />
    ),
    credibility: (section) => (
      <CredibilitySection
        content={buildCredibilityContent(
          messages.home.credibility,
          null,
          locale,
          section,
        )}
      />
    ),
    trust_and_credibility: (section) => (
      <TrustAndCredibilitySection
        content={buildTrustAndCredibilityContent(
          messages.trustAndCredibility,
          section,
          locale,
        )}
      />
    ),
    pilot_projects: (section) => (
      <PilotProjectsSection
        content={buildPilotProjectsContent(messages.pilotProjects, section, locale)}
      />
    ),
    form: (section) => {
      const content = buildB2BFormContent(messages.b2bPage.form, section, locale)

      return (
        <Suspense fallback={null}>
          <B2BInquiryFormSection
            locale={locale}
            content={content}
            common={messages.common}
            sourcePage="b2b"
            defaultLeadType="inquiry"
          />
        </Suspense>
      )
    },
    after_submit: (section) => (
      <B2BAfterSubmitSection
        content={buildB2BAfterSubmitContent(
          messages.b2bPage.afterSubmit,
          section,
          locale,
        )}
      />
    ),
    final_cta: (section) => (
      <FinalCtaSection
        locale={locale}
        content={buildFinalCtaContent(messages.home.finalCta, section, locale)}
      />
    ),
  }

  return (
    <>
      {orderedSections(b2bSections).map((section) => {
        const renderSection = b2bRenderers[section.key]

        return renderSection ? (
          <Fragment key={`${section.page_key}-${section.key}`}>
            {renderSection(section)}
          </Fragment>
        ) : null
      })}
    </>
  )
}
