import Link from "next/link"
import { Download, FileText } from "lucide-react"

import type { SiteMessages } from "@/lib/i18n"
import type { TechnicalDownload } from "@/lib/types"
import { Button } from "@/components/ui/button"

type MaterialProofPointsSectionProps = {
  content: SiteMessages["materialProof"]["proofPoints"]
}

type TechnicalDownloadsSectionProps = {
  content: SiteMessages["materialProof"]["technicalDownloads"]
  downloads?: TechnicalDownload[] | null
}

type MaterialComparisonSectionProps = {
  content: SiteMessages["materialProof"]["comparison"]
}

function downloadHref(download: TechnicalDownload) {
  return download.url || download.document_url || download.href || null
}

export function MaterialProofPointsSection({
  content,
}: MaterialProofPointsSectionProps) {
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
      </div>
    </section>
  )
}

export function TechnicalDownloadsSection({
  content,
  downloads,
}: TechnicalDownloadsSectionProps) {
  const availableDownloads = (downloads ?? []).filter((download) =>
    Boolean(download.title || download.label || download.type),
  )

  return (
    <section className="bg-card py-24 lg:py-28">
      <div className="mx-auto max-w-7xl px-6 lg:px-8">
        <div className="mb-12 flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">
          <div className="max-w-3xl">
            <p className="mb-4 text-sm uppercase tracking-[0.2em] text-primary">
              {content.eyebrow}
            </p>
            <h2 className="font-serif text-3xl leading-tight text-foreground md:text-4xl lg:text-5xl">
              {content.title}
            </h2>
          </div>
          <p className="max-w-xl text-base leading-relaxed text-muted-foreground">
            {content.description}
          </p>
        </div>

        {availableDownloads.length === 0 ? (
          <div className="rounded-3xl border border-dashed border-border/70 bg-background p-8">
            <p className="text-base text-foreground">{content.emptyTitle}</p>
            <p className="mt-3 max-w-2xl text-sm leading-relaxed text-muted-foreground">
              {content.emptyDescription}
            </p>
          </div>
        ) : (
          <div className="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            {availableDownloads.map((download, index) => {
              const href = downloadHref(download)
              const title =
                download.title || download.label || download.type || content.fileLabel

              return (
                <article
                  key={`${title}-${index}`}
                  className="rounded-2xl border border-border/60 bg-background p-6"
                >
                  <div className="flex items-start gap-4">
                    <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                      <FileText className="h-5 w-5" />
                    </div>
                    <div>
                      <p className="font-medium text-foreground">{title}</p>
                      {download.description ? (
                        <p className="mt-2 text-sm leading-relaxed text-muted-foreground">
                          {download.description}
                        </p>
                      ) : null}
                    </div>
                  </div>
                  {href ? (
                    <Button asChild variant="ghost" className="mt-5 px-0 text-primary">
                      <Link href={href}>
                        <Download className="mr-2 h-4 w-4" />
                        {content.downloadLabel}
                      </Link>
                    </Button>
                  ) : (
                    <p className="mt-5 text-sm text-muted-foreground">
                      {content.onRequestLabel}
                    </p>
                  )}
                </article>
              )
            })}
          </div>
        )}
      </div>
    </section>
  )
}

export function MaterialComparisonSection({
  content,
}: MaterialComparisonSectionProps) {
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

        <div className="overflow-x-auto rounded-3xl border border-border/60 bg-card">
          <table className="min-w-[860px] text-left text-sm">
            <thead className="bg-muted/60 text-foreground">
              <tr>
                {content.columns.map((column) => (
                  <th key={column} className="px-5 py-4 font-medium">
                    {column}
                  </th>
                ))}
              </tr>
            </thead>
            <tbody>
              {content.rows.map((row) => (
                <tr key={row.label} className="border-t border-border/60">
                  <th className="px-5 py-4 font-medium text-foreground">
                    {row.label}
                  </th>
                  <td className="px-5 py-4 text-muted-foreground">{row.oxp}</td>
                  <td className="px-5 py-4 text-muted-foreground">
                    {row.plastic}
                  </td>
                  <td className="px-5 py-4 text-muted-foreground">{row.ceramic}</td>
                  <td className="px-5 py-4 text-muted-foreground">{row.paper}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        <p className="mt-6 max-w-3xl text-sm leading-relaxed text-muted-foreground">
          {content.disclaimer}
        </p>
      </div>
    </section>
  )
}
