<x-filament-widgets::widget>
    @php
        $toneClasses = static fn (string $tone): string => match ($tone) {
            'danger' => 'bg-danger-50 text-danger-700 ring-danger-200 dark:bg-danger-500/10 dark:text-danger-300 dark:ring-danger-500/20',
            'warning' => 'bg-amber-50 text-amber-700 ring-amber-200 dark:bg-amber-500/10 dark:text-amber-300 dark:ring-amber-500/20',
            'success' => 'bg-emerald-50 text-emerald-700 ring-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-300 dark:ring-emerald-500/20',
            default => 'bg-primary-50 text-primary-700 ring-primary-200 dark:bg-primary-500/10 dark:text-primary-300 dark:ring-primary-500/20',
        };
    @endphp

    <x-filament::section
        heading="Operational Activity"
        :description="$isAdmin
            ? 'The most recent order, lead, report, and publishing events across the operation.'
            : 'Recent report, concept, and comment activity that may need staff attention.'"
    >
        <div class="grid gap-3 lg:grid-cols-2">
            @forelse ($items as $item)
                <a
                    href="{{ $item['url'] }}"
                    class="group flex items-start gap-4 rounded-2xl border border-gray-200/80 bg-white px-4 py-4 shadow-sm transition hover:-translate-y-0.5 hover:border-primary-300 hover:shadow-md dark:border-white/10 dark:bg-white/5 dark:hover:border-primary-500/40"
                >
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl ring-1 {{ $toneClasses($item['tone'] ?? 'primary') }}">
                        <x-filament::icon :icon="$item['icon']" class="h-5 w-5" />
                    </span>

                    <span class="min-w-0 flex-1">
                        <span class="flex items-start justify-between gap-3">
                            <span class="min-w-0">
                                <span class="block truncate text-sm font-semibold text-gray-950 dark:text-white">
                                    {{ $item['title'] }}
                                </span>
                                <span class="mt-1 inline-flex rounded-full bg-gray-100 px-2.5 py-1 text-[0.72rem] font-semibold uppercase tracking-[0.14em] text-gray-600 dark:bg-white/10 dark:text-gray-300">
                                    {{ $item['type'] }}
                                </span>
                            </span>

                            <span class="shrink-0 text-xs text-gray-500 dark:text-gray-400">
                                {{ \Illuminate\Support\Carbon::parse($item['timestamp'])->diffForHumans() }}
                            </span>
                        </span>

                        <span class="mt-3 block text-sm leading-6 text-gray-500 dark:text-gray-400">
                            {{ $item['subtitle'] }}
                        </span>
                    </span>
                </a>
            @empty
                <div class="rounded-2xl border border-dashed border-gray-300/80 px-6 py-10 text-sm text-gray-500 dark:border-white/10 dark:text-gray-400 lg:col-span-2">
                    No recent operational activity has been captured yet.
                </div>
            @endforelse
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
