import { Skeleton } from "@/components/ui/skeleton"

export default function StoreLoading() {
  return (
    <section className="bg-background py-24 lg:py-28">
      <div className="mx-auto max-w-7xl px-6 lg:px-8">
        <Skeleton className="h-6 w-40" />
        <Skeleton className="mt-4 h-12 w-full max-w-2xl" />
        <Skeleton className="mt-4 h-6 w-full max-w-3xl" />
        <div className="mt-12 grid grid-cols-1 gap-6 lg:grid-cols-2">
          {Array.from({ length: 4 }).map((_, index) => (
            <div
              key={index}
              className="overflow-hidden rounded-3xl border border-border/60 bg-card"
            >
              <Skeleton className="h-[320px] w-full" />
              <div className="space-y-4 p-8">
                <Skeleton className="h-4 w-32" />
                <Skeleton className="h-8 w-3/4" />
                <Skeleton className="h-20 w-full" />
              </div>
            </div>
          ))}
        </div>
      </div>
    </section>
  )
}
