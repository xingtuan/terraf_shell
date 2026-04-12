"use client"

import { useEffect, useState } from "react"
import Link from "next/link"
import { usePathname } from "next/navigation"
import { Menu, X } from "lucide-react"

import { LanguageSwitcher } from "@/components/language-switcher"
import { Button } from "@/components/ui/button"
import {
  getLocalizedHref,
  type Locale,
  type SiteMessages,
} from "@/lib/i18n"
import { cn } from "@/lib/utils"

type HeaderProps = {
  locale: Locale
  header: SiteMessages["header"]
  languageSwitcher: SiteMessages["languageSwitcher"]
}

export function Header({ locale, header, languageSwitcher }: HeaderProps) {
  const pathname = usePathname()
  const [isScrolled, setIsScrolled] = useState(false)
  const [isMobileMenuOpen, setIsMobileMenuOpen] = useState(false)

  useEffect(() => {
    const handleScroll = () => {
      setIsScrolled(window.scrollY > 20)
    }

    handleScroll()
    window.addEventListener("scroll", handleScroll)

    return () => window.removeEventListener("scroll", handleScroll)
  }, [])

  useEffect(() => {
    setIsMobileMenuOpen(false)
  }, [pathname])

  const navLinks = [
    { href: getLocalizedHref(locale), label: header.home },
    { href: getLocalizedHref(locale, "material"), label: header.material },
    { href: getLocalizedHref(locale, "store"), label: header.store },
    { href: getLocalizedHref(locale, "b2b"), label: header.b2b },
    { href: getLocalizedHref(locale, "community"), label: header.community },
    { href: getLocalizedHref(locale, "contact"), label: header.contact },
  ]

  return (
    <header
      className={cn(
        "fixed inset-x-0 top-0 z-50 transition-all duration-500",
        isScrolled
          ? "border-b border-border/50 bg-background/94 backdrop-blur-md"
          : "bg-transparent",
      )}
    >
      <div className="mx-auto max-w-7xl px-6 lg:px-8">
        <div className="flex h-20 items-center justify-between gap-4">
          <Link href={getLocalizedHref(locale)} className="flex items-center">
            <span className="font-serif text-2xl tracking-[0.28em] text-foreground">
              SHELLFIN
            </span>
          </Link>

          <nav className="hidden items-center gap-8 lg:flex">
            {navLinks.map((link) => {
              const isActive =
                link.href === getLocalizedHref(locale)
                  ? pathname === link.href
                  : pathname.startsWith(link.href)

              return (
                <Link
                  key={link.href}
                  href={link.href}
                  className={cn(
                    "text-sm tracking-wide transition-colors duration-300",
                    isActive
                      ? "text-foreground"
                      : "text-muted-foreground hover:text-foreground",
                  )}
                >
                  {link.label}
                </Link>
              )
            })}
          </nav>

          <div className="hidden items-center gap-4 lg:flex">
            <LanguageSwitcher
              locale={locale}
              content={languageSwitcher}
            />
            <Button asChild className="px-6">
              <Link href={`${getLocalizedHref(locale, "b2b")}#inquiry`}>
                {header.primaryCta}
              </Link>
            </Button>
          </div>

          <button
            type="button"
            className="rounded-full border border-border/70 bg-background/70 p-2 lg:hidden"
            onClick={() => setIsMobileMenuOpen((current) => !current)}
            aria-label={header.mobileMenuLabel}
          >
            {isMobileMenuOpen ? (
              <X className="h-5 w-5 text-foreground" />
            ) : (
              <Menu className="h-5 w-5 text-foreground" />
            )}
          </button>
        </div>
      </div>

      <div
        className={cn(
          "absolute left-0 right-0 top-full border-b border-border bg-background/98 backdrop-blur-md transition-all duration-300 lg:hidden",
          isMobileMenuOpen ? "visible opacity-100" : "invisible opacity-0",
        )}
      >
        <nav className="mx-auto flex max-w-7xl flex-col gap-6 px-6 py-8">
          {navLinks.map((link) => (
            <Link
              key={link.href}
              href={link.href}
              className="text-lg tracking-wide text-muted-foreground transition-colors hover:text-foreground"
            >
              {link.label}
            </Link>
          ))}
          <div className="flex flex-col gap-4 border-t border-border pt-6">
            <LanguageSwitcher
              locale={locale}
              content={languageSwitcher}
              className="w-fit"
            />
            <Button asChild className="w-full">
              <Link href={`${getLocalizedHref(locale, "b2b")}#inquiry`}>
                {header.primaryCta}
              </Link>
            </Button>
          </div>
        </nav>
      </div>
    </header>
  )
}
