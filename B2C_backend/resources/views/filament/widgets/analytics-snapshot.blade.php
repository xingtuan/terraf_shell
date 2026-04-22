<x-filament-widgets::widget>
    @php
        $focus = $hero['focus'] ?? [];
        $insights = $hero['insights'] ?? [];
        $quickLinks = $hero['quick_links'] ?? [];
        $formattedGeneratedAt = \Illuminate\Support\Carbon::parse($generatedAt)->format('M j, Y g:i A');

        $toneClasses = static fn (string $tone): string => match ($tone) {
            'danger' => 'border-danger-200/70 bg-danger-50/80 text-danger-700 dark:border-danger-500/20 dark:bg-danger-500/10 dark:text-danger-300',
            'warning' => 'border-amber-200/70 bg-amber-50/80 text-amber-700 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-300',
            'success' => 'border-emerald-200/70 bg-emerald-50/80 text-emerald-700 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-300',
            default => 'border-primary-200/70 bg-primary-50/80 text-primary-700 dark:border-primary-500/20 dark:bg-primary-500/10 dark:text-primary-300',
        };
    @endphp

    <x-filament::section>
        <div class="grid gap-6 xl:grid-cols-[1.35fr,0.95fr]">
            <div class="overflow-hidden rounded-[1.75rem] border border-white/60 bg-gradient-to-br from-amber-400/20 via-white to-emerald-400/10 p-6 shadow-sm ring-1 ring-gray-950/5 dark:border-white/10 dark:from-amber-400/10 dark:via-gray-950 dark:to-emerald-400/10 dark:ring-white/10">
                <div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                    <div class="max-w-2xl">
                        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.22em] text-amber-700 dark:text-amber-300">
                            Daily Pulse
                        </div>

                        <h2 class="mt-3 text-2xl font-semibold tracking-tight text-gray-950 dark:text-white">
                            Shellfin operations across commerce, growth, content, and community.
                        </h2>

                        <p class="mt-3 text-sm leading-6 text-gray-600 dark:text-gray-300">
                            Use this dashboard to triage revenue-impacting work first, keep lead response times tight,
                            and make sure moderation and publishing queues do not drift.
                        </p>
                    </div>

                    <div class="rounded-2xl border border-white/60 bg-white/85 px-4 py-3 text-sm text-gray-600 shadow-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-300">
                        <div class="text-xs uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">
                            Refreshed
                        </div>
                        <div class="mt-1 font-medium text-gray-950 dark:text-white">
                            {{ $formattedGeneratedAt }}
                        </div>
                    </div>
                </div>

                <div class="mt-6 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                    @foreach ($focus as $item)
                        <a
                            href="{{ $item['url'] }}"
                            class="rounded-2xl border px-4 py-4 transition hover:-translate-y-0.5 hover:shadow-md {{ $toneClasses($item['tone'] ?? 'primary') }}"
                        >
                            <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em]">
                                {{ $item['label'] }}
                            </div>
                            <div class="mt-2 text-2xl font-semibold tracking-tight">
                                {{ number_format((int) $item['value']) }}
                            </div>
                        </a>
                    @endforeach
                </div>

                <div class="mt-6 grid gap-3 sm:grid-cols-2">
                    @foreach ($quickLinks as $link)
                        <a
                            href="{{ $link['url'] }}"
                            class="group flex items-start gap-3 rounded-2xl border border-gray-200/80 bg-white/80 px-4 py-4 transition hover:border-amber-300 hover:bg-white hover:shadow-md dark:border-white/10 dark:bg-white/5 dark:hover:border-amber-500/40"
                        >
                            <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300">
                                <x-filament::icon :icon="$link['icon']" class="h-5 w-5" />
                            </span>

                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-semibold text-gray-950 dark:text-white">
                                    {{ $link['label'] }}
                                </span>
                                <span class="mt-1 block text-sm text-gray-500 dark:text-gray-400">
                                    {{ $link['description'] }}
                                </span>
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                @foreach ($insights as $insight)
                    <a
                        href="{{ $insight['url'] }}"
                        class="rounded-2xl border border-gray-200/80 bg-white/90 px-5 py-4 shadow-sm transition hover:-translate-y-0.5 hover:border-amber-300 hover:shadow-md dark:border-white/10 dark:bg-white/5 dark:hover:border-amber-500/40"
                    >
                        <div class="text-[0.72rem] font-semibold uppercase tracking-[0.18em] text-gray-500 dark:text-gray-400">
                            {{ $insight['label'] }}
                        </div>

                        <div class="mt-2 text-lg font-semibold tracking-tight text-gray-950 dark:text-white">
                            {{ $insight['value'] }}
                        </div>

                        <div class="mt-2 text-sm leading-6 text-gray-500 dark:text-gray-400">
                            {{ $insight['meta'] }}
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
