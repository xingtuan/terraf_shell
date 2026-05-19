import { ForgotPasswordClient } from "@/components/auth/ForgotPasswordClient"
import { getMessages } from "@/lib/i18n"
import { resolveLocale } from "@/lib/resolve-locale"

type ForgotPasswordPageProps = {
  params: Promise<{ locale: string }>
}

export default async function ForgotPasswordPage({
  params,
}: ForgotPasswordPageProps) {
  const locale = await resolveLocale(params)
  const messages = getMessages(locale)
  const copy = messages.community.auth.forgotPassword

  return (
    <div className="mx-auto max-w-2xl px-6 py-24 lg:px-8">
      <div className="mb-10 text-center">
        <p className="text-sm uppercase tracking-[0.2em] text-primary">
          {copy.eyebrow}
        </p>
        <h1 className="mt-3 font-serif text-4xl text-foreground">
          {copy.title}
        </h1>
        <p className="mt-4 text-sm leading-relaxed text-muted-foreground">
          {copy.description}
        </p>
      </div>
      <ForgotPasswordClient locale={locale} copy={copy} />
    </div>
  )
}
