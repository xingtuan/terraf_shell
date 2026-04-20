import { Skeleton } from "@/components/ui/skeleton"

export default function ProductDetailLoading() {
  return (
    <section className="bg-background py-20 lg:py-24">
      <div className="mx-auto max-w-7xl px-6 lg:px-8">
        <div className="grid grid-cols-1 gap-10 lg:grid-cols-[1.05fr_0.95fr]">
          <Skeleton className="h-[520px] w-full rounded-3xl" />
          <div className="space-y-5">
            <Skeleton className="h-6 w-32" />
            <Skeleton className="h-14 w-4/5" />
            <Skeleton className="h-28 w-full" />
            <Skeleton className="h-40 w-full rounded-3xl" />
          </div>
        </div>
      </div>
    </section>
  )
}
