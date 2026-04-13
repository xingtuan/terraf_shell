<x-filament-widgets::widget>
    @php
        $summary = $overview['summary'] ?? [];
        $categories = $overview['categories']['highest_engagement'] ?? [];
        $schools = $overview['activity']['schools_or_companies'] ?? [];
        $concepts = $overview['funding']['most_likely_concepts'] ?? [];
    @endphp

    <div class="grid gap-6 lg:grid-cols-3">
        <x-filament::section heading="Analytics Summary" class="lg:col-span-3">
            <div class="grid gap-4 md:grid-cols-4">
                <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total engagement</div>
                    <div class="mt-2 text-2xl font-semibold">{{ number_format((int) ($summary['total_engagement'] ?? 0)) }}</div>
                </div>
                <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Total views</div>
                    <div class="mt-2 text-2xl font-semibold">{{ number_format((int) ($summary['total_views'] ?? 0)) }}</div>
                </div>
                <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="text-sm text-gray-500 dark:text-gray-400">B2B leads</div>
                    <div class="mt-2 text-2xl font-semibold">{{ number_format((int) ($summary['total_b2b_leads'] ?? 0)) }}</div>
                </div>
                <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="text-sm text-gray-500 dark:text-gray-400">Funding-linked concepts</div>
                    <div class="mt-2 text-2xl font-semibold">{{ number_format((int) ($summary['concepts_with_funding_campaigns'] ?? 0)) }}</div>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section heading="Top Categories">
            <div class="space-y-3">
                @forelse ($categories as $category)
                    <div class="flex items-start justify-between gap-4 rounded-xl border border-gray-200/70 p-3 dark:border-white/10">
                        <div>
                            <div class="font-medium">{{ $category['name'] }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                {{ number_format($category['total_concepts']) }} concepts
                            </div>
                        </div>
                        <div class="text-right text-sm">
                            <div class="font-medium">{{ number_format($category['total_engagement']) }}</div>
                            <div class="text-gray-500 dark:text-gray-400">engagement</div>
                        </div>
                    </div>
                @empty
                    <div class="text-sm text-gray-500 dark:text-gray-400">No category analytics available yet.</div>
                @endforelse
            </div>
        </x-filament::section>

        <x-filament::section heading="Active Schools / Companies">
            <div class="space-y-3">
                @forelse ($schools as $school)
                    <div class="flex items-start justify-between gap-4 rounded-xl border border-gray-200/70 p-3 dark:border-white/10">
                        <div>
                            <div class="font-medium">{{ $school['school_or_company'] }}</div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                {{ number_format($school['total_users']) }} users, {{ number_format($school['approved_concepts']) }} approved concepts
                            </div>
                        </div>
                        <div class="text-right text-sm">
                            <div class="font-medium">{{ number_format($school['total_engagement']) }}</div>
                            <div class="text-gray-500 dark:text-gray-400">engagement</div>
                        </div>
                    </div>
                @empty
                    <div class="text-sm text-gray-500 dark:text-gray-400">No school or company activity yet.</div>
                @endforelse
            </div>
        </x-filament::section>

        <x-filament::section heading="Funding-Ready Concepts">
            <div class="space-y-3">
                @forelse ($concepts as $concept)
                    <div class="rounded-xl border border-gray-200/70 p-3 dark:border-white/10">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <div class="font-medium">{{ $concept['title'] }}</div>
                                <div class="text-sm text-gray-500 dark:text-gray-400">
                                    {{ $concept['creator_name'] ?? 'Unknown creator' }}
                                    @if (! empty($concept['school_or_company']))
                                        • {{ $concept['school_or_company'] }}
                                    @endif
                                </div>
                            </div>
                            <div class="text-right text-sm">
                                <div class="font-medium">{{ number_format((float) $concept['funding_readiness_score'], 2) }}</div>
                                <div class="text-gray-500 dark:text-gray-400">readiness</div>
                            </div>
                        </div>

                        <div class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                            {{ $concept['recommended_next_action'] }}
                        </div>
                    </div>
                @empty
                    <div class="text-sm text-gray-500 dark:text-gray-400">No funding-ready concepts available yet.</div>
                @endforelse
            </div>
        </x-filament::section>
    </div>
</x-filament-widgets::widget>
