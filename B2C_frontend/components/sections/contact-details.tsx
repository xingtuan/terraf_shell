import { Mail, MapPin, Phone } from "lucide-react"

import { getBrandContactLabel } from "@/lib/brand"
import type { SiteMessages } from "@/lib/i18n"

type ContactDetailsSectionProps = {
  content: SiteMessages["contactPage"]["details"]
}

const contactIcons = [Mail, Phone, MapPin]

export function ContactDetailsSection({
  content,
}: ContactDetailsSectionProps) {
  return (
    <section className="bg-background py-24 lg:py-28">
      <div className="mx-auto max-w-7xl px-6 lg:px-8">
        <div className="mb-14 max-w-3xl">
          <p className="mb-4 text-sm uppercase tracking-[0.2em] text-primary">
            {content.eyebrow}
          </p>
          <h2 className="mb-5 font-serif text-3xl leading-tight text-foreground md:text-4xl lg:text-5xl">
            {content.title}
          </h2>
          <p className="text-lg leading-relaxed text-muted-foreground">
            {content.description}
          </p>
        </div>

        <div className="grid grid-cols-1 gap-6 md:grid-cols-3">
          {content.cards.map((card, index) => {
            const Icon = contactIcons[index] ?? Mail
            const value = index === 0 ? getBrandContactLabel() : card.value

            return (
              <div
                key={card.label}
                className="rounded-3xl border border-border/60 bg-card p-8"
              >
                <div className="mb-5 flex h-12 w-12 items-center justify-center rounded-2xl bg-primary/10">
                  <Icon className="h-6 w-6 text-primary" />
                </div>
                <p className="mb-2 text-sm uppercase tracking-[0.18em] text-primary">
                  {card.label}
                </p>
                <p className="mb-3 font-serif text-2xl text-foreground">
                  {value}
                </p>
                <p className="text-sm leading-relaxed text-muted-foreground">
                  {card.detail}
                </p>
              </div>
            )
          })}
        </div>

        <p className="mt-8 text-sm text-muted-foreground">
          {content.response}
        </p>
      </div>
    </section>
  )
}
