import Link from "next/link"
import { Mail, MapPin, Phone } from "lucide-react"

import { getLocalizedHref, type Locale, type SiteMessages } from "@/lib/i18n"

type FooterProps = {
  locale: Locale
  header: SiteMessages["header"]
  footer: SiteMessages["footer"]
}

export function Footer({ locale, header, footer }: FooterProps) {
  const exploreLinks = [
    { label: header.home, href: getLocalizedHref(locale) },
    { label: header.material, href: getLocalizedHref(locale, "material") },
    { label: header.store, href: getLocalizedHref(locale, "store") },
  ]

  const businessLinks = [
    { label: header.b2b, href: getLocalizedHref(locale, "b2b") },
    { label: footer.materialSheet, href: getLocalizedHref(locale, "material") },
    {
      label: footer.sampleRequest,
      href: `${getLocalizedHref(locale, "b2b")}?leadType=sample_request#inquiry`,
    },
    {
      label: footer.productDevelopment,
      href: `${getLocalizedHref(locale, "b2b")}?leadType=product_development_collaboration#inquiry`,
    },
  ]

  const communityLinks = [
    { label: header.community, href: getLocalizedHref(locale, "community") },
    { label: footer.ideaSupport, href: getLocalizedHref(locale, "community") },
    { label: footer.conceptFund, href: getLocalizedHref(locale, "community") },
    { label: header.contact, href: getLocalizedHref(locale, "contact") },
  ]

  const contactItems = [
    {
      icon: Mail,
      label: footer.emailLabel,
      value: "hello@shellfin.kr",
      href: "mailto:hello@shellfin.kr",
    },
    {
      icon: Phone,
      label: footer.phoneLabel,
      value: "+82 51-555-0188",
      href: "tel:+82515550188",
    },
    {
      icon: MapPin,
      label: footer.locationLabel,
      value: footer.locationValue,
      href: getLocalizedHref(locale, "contact"),
    },
  ]

  return (
    <footer className="bg-foreground text-background">
      <div className="mx-auto max-w-7xl px-6 py-16 lg:px-8 lg:py-20">
        <div className="mb-14 grid grid-cols-1 gap-12 lg:grid-cols-12 lg:gap-8">
          <div className="lg:col-span-4">
            <Link href={getLocalizedHref(locale)} className="inline-block">
              <span className="font-serif text-3xl tracking-[0.28em] text-background">
                SHELLFIN
              </span>
            </Link>
            <p className="mt-6 max-w-sm text-background/72 leading-relaxed">
              {footer.description}
            </p>
            <div className="mt-8 space-y-4">
              {contactItems.map((item) => (
                <Link
                  key={item.label}
                  href={item.href}
                  className="flex items-center gap-3 text-sm text-background/75 transition-colors hover:text-background"
                >
                  <item.icon className="h-4 w-4" />
                  <span>{item.label}</span>
                  <span className="text-background/55">{item.value}</span>
                </Link>
              ))}
            </div>
          </div>

          <div className="lg:col-span-8 grid grid-cols-2 gap-8 md:grid-cols-3">
            <div>
              <h3 className="mb-4 text-sm uppercase tracking-[0.2em] text-background/60">
                {footer.explore}
              </h3>
              <ul className="space-y-3">
                {exploreLinks.map((item) => (
                  <li key={item.label}>
                    <Link
                      href={item.href}
                      className="text-background/72 transition-colors hover:text-background"
                    >
                      {item.label}
                    </Link>
                  </li>
                ))}
              </ul>
            </div>
            <div>
              <h3 className="mb-4 text-sm uppercase tracking-[0.2em] text-background/60">
                {footer.business}
              </h3>
              <ul className="space-y-3">
                {businessLinks.map((item) => (
                  <li key={item.label}>
                    <Link
                      href={item.href}
                      className="text-background/72 transition-colors hover:text-background"
                    >
                      {item.label}
                    </Link>
                  </li>
                ))}
              </ul>
            </div>
            <div>
              <h3 className="mb-4 text-sm uppercase tracking-[0.2em] text-background/60">
                {footer.communityLabel}
              </h3>
              <ul className="space-y-3">
                {communityLinks.map((item) => (
                  <li key={item.label}>
                    <Link
                      href={item.href}
                      className="text-background/72 transition-colors hover:text-background"
                    >
                      {item.label}
                    </Link>
                  </li>
                ))}
              </ul>
            </div>
          </div>
        </div>

        <div className="flex flex-col gap-4 border-t border-background/10 pt-8 text-sm text-background/50 md:flex-row md:items-center md:justify-between">
          <p>{footer.copyright}</p>
          <div className="flex gap-6">
            <Link href="#" className="transition-colors hover:text-background/70">
              {footer.privacy}
            </Link>
            <Link href="#" className="transition-colors hover:text-background/70">
              {footer.terms}
            </Link>
          </div>
        </div>
      </div>
    </footer>
  )
}
