import { PageIntro } from "@/components/page-intro"
import { CommunityHub } from "@/components/community/community-hub"
import { FinalCtaSection } from "@/components/sections/final-cta"
import { getCommunityCopy } from "@/lib/community-copy"
import { getLocalizedHref, getMessages } from "@/lib/i18n"
import { resolveLocale } from "@/lib/resolve-locale"

type CommunityPageProps = {
  params: Promise<{ locale: string }>
}

export default async function CommunityPage({
  params,
}: CommunityPageProps) {
  const locale = await resolveLocale(params)
  const messages = getMessages(locale)
  const communityCopy = getCommunityCopy(locale)
  const intro = communityCopy.pageIntro

  return (
    <>
      <PageIntro
        eyebrow={intro.eyebrow}
        title={intro.title}
        description={intro.description}
        primaryAction={{
          label: intro.primaryCta,
          href: `${getLocalizedHref(locale, "community")}#posts`,
        }}
        secondaryAction={{
          label: intro.secondaryCta,
          href: `${getLocalizedHref(locale, "community")}#community-access`,
        }}
      />
      <CommunityHub locale={locale} copy={communityCopy} />
      <FinalCtaSection locale={locale} content={messages.home.finalCta} />
    </>
  )
}
