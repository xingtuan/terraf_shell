import { ResetPasswordClient } from "@/components/auth/ResetPasswordClient"
import { getMessages } from "@/lib/i18n"
import { resolveLocale } from "@/lib/resolve-locale"

type ResetPasswordPageProps = {
  params: Promise<{ locale: string }>
  searchParams?: Promise<{
    token?: string
    email?: string
  }>
}

export default async function ResetPasswordPage({
  params,
  searchParams,
}: ResetPasswordPageProps) {
  const locale = await resolveLocale(params)
  const resolvedSearchParams = await searchParams
  const messages = getMessages(locale)
  const copy = messages.community.auth.resetPassword

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
      <ResetPasswordClient
        locale={locale}
        copy={copy}
        token={resolvedSearchParams?.token ?? ""}
        email={resolvedSearchParams?.email ?? ""}
      />
    </div>
  )
}
