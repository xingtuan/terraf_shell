import { Skeleton } from "@/components/ui/skeleton"

export default function StoreLoading() {
  return (
    <section className="bg-background py-24 lg:py-28">
      <div className="mx-auto max-w-7xl px-6 lg:px-8">
        <Skeleton className="h-6 w-40" />
        <Skeleton className="mt-4 h-12 w-full max-w-2xl" />
        <Skeleton className="mt-4 h-6 w-full max-w-3xl" />
        <div className="mt-12 grid gap-8 xl:grid-cols-[0.34fr_0.66fr]">
          <div className="rounded-[2rem] border border-border/60 bg-card p-6">
            <Skeleton className="h-5 w-40" />
            <Skeleton className="mt-4 h-10 w-full" />
            <Skeleton className="mt-4 h-10 w-full" />
            <Skeleton className="mt-4 h-10 w-full" />
            <Skeleton className="mt-4 h-10 w-full" />
            <Skeleton className="mt-6 h-10 w-32" />
          </div>
          <div className="space-y-6">
            <Skeleton className="h-24 w-full rounded-[2rem]" />
            <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
              {Array.from({ length: 4 }).map((_, index) => (
                <div
                  key={index}
                  className="overflow-hidden rounded-[2rem] border border-border/60 bg-card"
                >
                  <Skeleton className="h-[320px] w-full" />
                  <div className="space-y-4 p-6">
                    <Skeleton className="h-4 w-32" />
                    <Skeleton className="h-8 w-3/4" />
                    <Skeleton className="h-16 w-full" />
                    <Skeleton className="h-12 w-full rounded-3xl" />
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>
    </section>
  )
}
