import Image from "next/image"
import Link from "next/link"
import { Mail, MapPin, Phone } from "lucide-react"

import {
  BRAND_DISPLAY_NAME,
  getBrandContactHref,
  getBrandContactLabel,
} from "@/lib/brand"
import type { Branding } from "@/lib/api/public-settings"
import { getLocalizedHref, type Locale, type SiteMessages } from "@/lib/i18n"
import type { FooterContent } from "@/lib/page-content"

type FooterProps = {
  locale: Locale
  header: SiteMessages["header"]
  footer: FooterContent
  branding?: Branding
}

const defaultPhoneValue = "+82 51-555-0188"

function phoneHrefFromValue(value: string) {
  const normalized = value.replace(/[^\d+]/g, "")

  return normalized ? `tel:${normalized}` : "tel:+82515550188"
}

function emailHrefFromValue(value: string, fallbackHref: string) {
  return value.includes("@") ? `mailto:${value}` : fallbackHref
}

export function Footer({ locale, header, footer, branding }: FooterProps) {
  const exploreLinks = [
    { label: footer.homeLabel ?? header.home, href: getLocalizedHref(locale) },
    {
      label: footer.materialLabel ?? header.material,
      href: getLocalizedHref(locale, "material"),
    },
    { label: footer.storeLabel ?? header.store, href: getLocalizedHref(locale, "store") },
  ]

  const businessLinks = [
    { label: footer.b2bLabel ?? header.b2b, href: getLocalizedHref(locale, "b2b") },
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
    {
      label: footer.communityLinkLabel ?? header.community,
      href: getLocalizedHref(locale, "community"),
    },
    { label: footer.ideaSupport, href: getLocalizedHref(locale, "community") },
    { label: footer.conceptFund, href: getLocalizedHref(locale, "community") },
    {
      label: footer.contactLabel ?? header.contact,
      href: getLocalizedHref(locale, "contact"),
    },
  ]

  const defaultContactHref = getBrandContactHref(
    `${getLocalizedHref(locale, "contact")}#contact-form`,
  )
  const emailValue = footer.emailValue ?? getBrandContactLabel()
  const phoneValue = footer.phoneValue ?? defaultPhoneValue

  const contactItems = [
    {
      icon: Mail,
      label: footer.emailLabel,
      value: emailValue,
      href: footer.emailHref ?? emailHrefFromValue(emailValue, defaultContactHref),
    },
    {
      icon: Phone,
      label: footer.phoneLabel,
      value: phoneValue,
      href: footer.phoneHref ?? phoneHrefFromValue(phoneValue),
    },
    {
      icon: MapPin,
      label: footer.locationLabel,
      value: footer.locationValue,
      href: footer.locationHref ?? getLocalizedHref(locale, "contact"),
    },
  ]
  const socialLinks = footer.socialLinks ?? []
  const legalLinks =
    footer.legalLinks?.length
      ? footer.legalLinks
      : [
          {
            label: footer.privacy,
            href: footer.privacyHref ?? getLocalizedHref(locale, "privacy"),
          },
          {
            label: footer.terms,
            href: footer.termsHref ?? getLocalizedHref(locale, "terms"),
          },
        ]

  return (
    <footer className="bg-foreground text-background">
      <div className="mx-auto max-w-7xl px-6 py-16 lg:px-8 lg:py-20">
        <div className="mb-14 grid grid-cols-1 gap-12 lg:grid-cols-12 lg:gap-8">
          <div className="lg:col-span-4">
            <Link href={getLocalizedHref(locale)} className="inline-block">
              {branding?.logo_url ? (
                <Image
                  src={branding.logo_url}
                  alt={branding.logo_alt ?? branding.logo_text}
                  height={48}
                  width={144}
                  className="h-12 w-auto object-contain brightness-0 invert"
                  unoptimized
                />
              ) : (
                <span className="font-serif text-3xl tracking-[0.28em] text-background">
                  {branding?.logo_text ?? BRAND_DISPLAY_NAME}
                </span>
              )}
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
            {socialLinks.length ? (
              <div className="mt-8 flex flex-wrap gap-4 text-sm">
                {socialLinks.map((item) => (
                  <Link
                    key={`${item.label}-${item.href}`}
                    href={item.href}
                    className="text-background/75 underline-offset-4 transition-colors hover:text-background hover:underline"
                  >
                    {item.label}
                  </Link>
                ))}
              </div>
            ) : null}
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
          <div className="flex flex-wrap gap-6">
            {legalLinks.map((item) => (
              <Link
                key={`${item.label}-${item.href}`}
                href={item.href}
                className="text-background/75 transition-colors hover:text-background"
              >
                {item.label}
              </Link>
            ))}
          </div>
        </div>
      </div>
    </footer>
  )
}
