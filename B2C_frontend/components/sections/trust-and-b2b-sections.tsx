import Link from "next/link"

import type { Locale, SiteMessages } from "@/lib/i18n"
import { getLocalizedHref } from "@/lib/i18n"
import { Button } from "@/components/ui/button"

type TrustSectionProps = {
  content: SiteMessages["trustAndCredibility"]
}

type PilotProjectsSectionProps = {
  content: SiteMessages["pilotProjects"]
}

type B2BProcessSectionProps = {
  content: SiteMessages["b2bPage"]["process"]
}

type B2BApplicationsSectionProps = {
  content: SiteMessages["b2bPage"]["applications"]
}

type B2BAfterSubmitSectionProps = {
  content: SiteMessages["b2bPage"]["afterSubmit"]
}

type B2BCtaStripProps = {
  locale: Locale
  content: SiteMessages["b2bPage"]["ctaStrip"]
}

export function TrustAndCredibilitySection({ content }: TrustSectionProps) {
  return (
    <section className="bg-background py-24 lg:py-28">
      <div className="mx-auto max-w-7xl px-6 lg:px-8">
        <div className="mb-12 max-w-3xl">
          <p className="mb-4 text-sm uppercase tracking-[0.2em] text-primary">
            {content.eyebrow}
          </p>
          <h2 className="font-serif text-3xl leading-tight text-foreground md:text-4xl lg:text-5xl">
            {content.title}
          </h2>
          <p className="mt-5 text-lg leading-relaxed text-muted-foreground">
            {content.description}
          </p>
        </div>

        <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-5">
          {content.cards.map((card) => (
            <article
              key={card.title}
              className="rounded-2xl border border-border/60 bg-card p-5"
            >
              <h3 className="text-sm font-medium text-foreground">{card.title}</h3>
              <p className="mt-3 text-sm leading-relaxed text-muted-foreground">
                {card.description}
              </p>
            </article>
          ))}
        </div>

        <p className="mt-8 max-w-3xl text-sm leading-relaxed text-muted-foreground">
          {content.disclaimer}
        </p>
      </div>
    </section>
  )
}

export function PilotProjectsSection({ content }: PilotProjectsSectionProps) {
  return (
    <section className="bg-card py-24 lg:py-28">
      <div className="mx-auto max-w-7xl px-6 lg:px-8">
        <div className="mb-12 max-w-3xl">
          <p className="mb-4 text-sm uppercase tracking-[0.2em] text-primary">
            {content.eyebrow}
          </p>
          <h2 className="font-serif text-3xl leading-tight text-foreground md:text-4xl lg:text-5xl">
            {content.title}
          </h2>
          <p className="mt-5 text-lg leading-relaxed text-muted-foreground">
            {content.description}
          </p>
        </div>

        <div className="grid grid-cols-1 gap-4 lg:grid-cols-3">
          {content.items.map((item) => (
            <article
              key={item.title}
              className="rounded-2xl border border-border/60 bg-background p-6"
            >
              <span className="rounded-full border border-border/60 px-3 py-1 text-xs uppercase tracking-[0.16em] text-muted-foreground">
                {item.status}
              </span>
              <h3 className="mt-5 font-serif text-2xl text-foreground">
                {item.title}
              </h3>
              <p className="mt-3 text-sm leading-relaxed text-muted-foreground">
                {item.description}
              </p>
            </article>
          ))}
        </div>
      </div>
    </section>
  )
}

export function B2BProcessSection({ content }: B2BProcessSectionProps) {
  return (
    <section className="bg-background py-24 lg:py-28">
      <div className="mx-auto max-w-7xl px-6 lg:px-8">
        <div className="mb-12 max-w-3xl">
          <p className="mb-4 text-sm uppercase tracking-[0.2em] text-primary">
            {content.eyebrow}
          </p>
          <h2 className="font-serif text-3xl leading-tight text-foreground md:text-4xl lg:text-5xl">
            {content.title}
          </h2>
        </div>

        <div className="grid grid-cols-1 gap-4 md:grid-cols-5">
          {content.steps.map((step, index) => (
            <article
              key={step.title}
              className="rounded-2xl border border-border/60 bg-card p-5"
            >
              <p className="text-sm uppercase tracking-[0.18em] text-primary">
                {String(index + 1).padStart(2, "0")}
              </p>
              <h3 className="mt-4 text-sm font-medium text-foreground">
                {step.title}
              </h3>
              <p className="mt-3 text-sm leading-relaxed text-muted-foreground">
                {step.description}
              </p>
            </article>
          ))}
        </div>
      </div>
    </section>
  )
}

export function B2BApplicationsSection({ content }: B2BApplicationsSectionProps) {
  return (
    <section className="bg-card py-24 lg:py-28">
      <div className="mx-auto max-w-7xl px-6 lg:px-8">
        <div className="mb-12 max-w-3xl">
          <p className="mb-4 text-sm uppercase tracking-[0.2em] text-primary">
            {content.eyebrow}
          </p>
          <h2 className="font-serif text-3xl leading-tight text-foreground md:text-4xl lg:text-5xl">
            {content.title}
          </h2>
        </div>

        <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
          {content.cards.map((card) => (
            <article
              key={card.title}
              className="rounded-2xl border border-border/60 bg-background p-6"
            >
              <h3 className="font-medium text-foreground">{card.title}</h3>
              <p className="mt-3 text-sm leading-relaxed text-muted-foreground">
                {card.description}
              </p>
            </article>
          ))}
        </div>
      </div>
    </section>
  )
}

export function B2BAfterSubmitSection({ content }: B2BAfterSubmitSectionProps) {
  return (
    <section className="bg-background py-24 lg:py-28">
      <div className="mx-auto max-w-7xl px-6 lg:px-8">
        <div className="grid gap-10 lg:grid-cols-[0.8fr_1.2fr]">
          <div>
            <p className="mb-4 text-sm uppercase tracking-[0.2em] text-primary">
              {content.eyebrow}
            </p>
            <h2 className="font-serif text-3xl leading-tight text-foreground md:text-4xl">
              {content.title}
            </h2>
          </div>
          <div className="grid gap-4 md:grid-cols-2">
            {content.items.map((item) => (
              <div
                key={item}
                className="rounded-2xl border border-border/60 bg-card p-5 text-sm leading-relaxed text-muted-foreground"
              >
                {item}
              </div>
            ))}
          </div>
        </div>
      </div>
    </section>
  )
}

export function B2BCtaStrip({ locale, content }: B2BCtaStripProps) {
  const actions = [
    {
      label: content.sample,
      href: `${getLocalizedHref(locale, "b2b")}?leadType=sample_request#inquiry`,
    },
    {
      label: content.materialData,
      href: `${getLocalizedHref(locale, "b2b")}?leadType=inquiry#inquiry`,
    },
    {
      label: content.requirements,
      href: `${getLocalizedHref(locale, "b2b")}?leadType=product_development_collaboration#inquiry`,
    },
    {
      label: content.bulkSupply,
      href: `${getLocalizedHref(locale, "b2b")}?leadType=bulk_order#inquiry`,
    },
  ]

  return (
    <section className="bg-card py-10">
      <div className="mx-auto flex max-w-7xl flex-wrap gap-3 px-6 lg:px-8">
        {actions.map((action) => (
          <Button key={action.label} asChild variant="outline">
            <Link href={action.href}>{action.label}</Link>
          </Button>
        ))}
      </div>
    </section>
  )
}
