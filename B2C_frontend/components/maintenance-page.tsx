import Image from "next/image"

import type { Branding } from "@/lib/api/public-settings"
import type { SiteMessages } from "@/lib/i18n"

type MaintenancePageProps = {
  messages: SiteMessages["maintenance"]
  branding: Branding
}

export function MaintenancePage({ messages, branding }: MaintenancePageProps) {
  return (
    <div className="flex min-h-screen flex-col items-center justify-center bg-background px-6">
      <div className="flex max-w-md flex-col items-center text-center">
        <div className="mb-8">
          {branding.logo_url ? (
            <Image
              src={branding.logo_url}
              alt={branding.logo_alt ?? branding.logo_text}
              height={48}
              width={160}
              className="h-12 w-auto object-contain"
              unoptimized
            />
          ) : (
            <span className="font-serif text-3xl tracking-[0.28em] text-foreground">
              {branding.logo_text}
            </span>
          )}
        </div>

        <div className="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-muted">
          <svg
            xmlns="http://www.w3.org/2000/svg"
            className="h-8 w-8 text-muted-foreground"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
            strokeWidth={1.5}
          >
            <path
              strokeLinecap="round"
              strokeLinejoin="round"
              d="M11.42 15.17L17.25 21A2.652 2.652 0 0021 17.25l-5.877-5.877M11.42 15.17l2.496-3.03c.317-.384.74-.626 1.208-.766M11.42 15.17l-4.655 5.653a2.548 2.548 0 11-3.586-3.586l6.837-5.63m5.108-.233c.55-.164 1.163-.188 1.743-.14a4.5 4.5 0 004.486-6.336l-3.276 3.277a3.004 3.004 0 01-2.25-2.25l3.276-3.276a4.5 4.5 0 00-6.336 4.486c.091 1.076-.071 2.264-.904 2.95l-.102.085m-1.745 1.437L5.909 7.5H4.5L2.25 3.75l1.5-1.5L7.5 4.5v1.409l4.26 4.26m-1.745 1.437l1.745-1.437m6.615 8.206L15.75 15.75M4.867 19.125h.008v.008h-.008v-.008z"
            />
          </svg>
        </div>

        <h1 className="mb-4 font-serif text-3xl text-foreground">
          {messages.title}
        </h1>

        <p className="mb-2 text-muted-foreground leading-relaxed">
          {messages.message}
        </p>

        <p className="text-sm text-muted-foreground">{messages.backSoon}</p>
      </div>
    </div>
  )
}
