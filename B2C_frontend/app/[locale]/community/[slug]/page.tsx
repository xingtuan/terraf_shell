import { notFound } from "next/navigation"

import { PageIntro } from "@/components/page-intro"
import { CommunityPostDetail } from "@/components/community/community-post-detail"
import { FinalCtaSection } from "@/components/sections/final-cta"
import { getCommunityCopy } from "@/lib/community-copy"
import { getLocalizedHref, getMessages, isValidLocale } from "@/lib/i18n"

type CommunityPostPageProps = {
  params: Promise<{ locale: string; slug: string }>
}

export default async function CommunityPostPage({
  params,
}: CommunityPostPageProps) {
  const resolvedParams = await params

  if (!isValidLocale(resolvedParams.locale)) {
    notFound()
  }

  const locale = resolvedParams.locale
  const messages = getMessages(locale)
  const communityCopy = getCommunityCopy(locale)
  const intro = communityCopy.detailIntro

  return (
    <>
      <PageIntro
        eyebrow={intro.eyebrow}
        title={intro.title}
        description={intro.description}
        primaryAction={{
          label: intro.primaryCta,
          href: getLocalizedHref(locale, "community"),
        }}
        secondaryAction={{
          label: intro.secondaryCta,
          href: `${getLocalizedHref(locale, `community/${resolvedParams.slug}`)}#comments`,
        }}
      />
      <CommunityPostDetail
        locale={locale}
        slug={resolvedParams.slug}
        copy={communityCopy}
      />
      <FinalCtaSection locale={locale} content={messages.home.finalCta} />
    </>
  )
}
