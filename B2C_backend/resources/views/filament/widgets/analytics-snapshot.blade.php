<x-filament-widgets::widget>
    @php
        $summary    = $overview['summary'] ?? [];
        $categories = $overview['categories']['highest_engagement'] ?? [];
        $schools    = $overview['activity']['schools_or_companies'] ?? [];
        $concepts   = $overview['funding']['most_likely_concepts'] ?? [];

        $maxCatEngagement    = collect($categories)->max('total_engagement') ?: 1;
        $maxSchoolEngagement = collect($schools)->max('total_engagement') ?: 1;
        $maxReadiness        = collect($concepts)->max('funding_readiness_score') ?: 1;

        $metricCards = [
            [
                'label' => 'Total Engagement',
                'value' => number_format((int) ($summary['total_engagement'] ?? 0)),
                'icon'  => 'heroicon-o-bolt',
                'color' => 'text-amber-500',
                'bg'    => 'bg-amber-50 dark:bg-amber-950/30',
            ],
            [
                'label' => 'Total Views',
                'value' => number_format((int) ($summary['total_views'] ?? 0)),
                'icon'  => 'heroicon-o-eye',
                'color' => 'text-blue-500',
                'bg'    => 'bg-blue-50 dark:bg-blue-950/30',
            ],
            [
                'label' => 'B2B Leads',
                'value' => number_format((int) ($summary['total_b2b_leads'] ?? 0)),
                'icon'  => 'heroicon-o-briefcase',
                'color' => 'text-violet-500',
                'bg'    => 'bg-violet-50 dark:bg-violet-950/30',
            ],
            [
                'label' => 'Funding-Linked',
                'value' => number_format((int) ($summary['concepts_with_funding_campaigns'] ?? 0)),
                'icon'  => 'heroicon-o-banknotes',
                'color' => 'text-emerald-500',
                'bg'    => 'bg-emerald-50 dark:bg-emerald-950/30',
            ],
        ];
    @endphp

    {{-- Summary metric strip --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 mb-6">
        @foreach ($metricCards as $card)
            <div class="flex items-center gap-4 rounded-xl p-4 {{ $card['bg'] }} ring-1 ring-gray-950/5 dark:ring-white/10">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-white shadow-sm dark:bg-gray-900">
                    <x-filament::icon :icon="$card['icon']" class="h-5 w-5 {{ $card['color'] }}" />
                </div>
                <div class="min-w-0">
                    <div class="truncate text-xs font-medium text-gray-500 dark:text-gray-400">{{ $card['label'] }}</div>
                    <div class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white">{{ $card['value'] }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-3">

        {{-- Top Categories --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-squares-2x2" class="h-4 w-4 text-gray-400" />
                    Top Categories
                </div>
            </x-slot>

            <div class="space-y-3">
                @forelse ($categories as $i => $category)
                    @php
                        $pct = $maxCatEngagement > 0
                            ? round(($category['total_engagement'] / $maxCatEngagement) * 100)
                            : 0;
                        $barColors = ['bg-blue-500', 'bg-violet-500', 'bg-emerald-500', 'bg-amber-500', 'bg-rose-500'];
                        $bar = $barColors[$i % count($barColors)];
                    @endphp
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-900 dark:text-white truncate max-w-[60%]">
                                {{ $category['name'] }}
                            </span>
                            <span class="text-gray-500 dark:text-gray-400 shrink-0 ml-2">
                                {{ number_format($category['total_engagement']) }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="h-1.5 flex-1 rounded-full bg-gray-100 dark:bg-gray-800">
                                <div class="h-1.5 rounded-full {{ $bar }} transition-all" style="width: {{ $pct }}%"></div>
                            </div>
                            <span class="text-xs text-gray-400 w-10 text-right shrink-0">
                                {{ number_format($category['total_concepts']) }}c
                            </span>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">No category analytics yet.</p>
                @endforelse
            </div>
        </x-filament::section>

        {{-- Active Schools / Companies --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-building-office-2" class="h-4 w-4 text-gray-400" />
                    Active Schools & Companies
                </div>
            </x-slot>

            <div class="space-y-3">
                @forelse ($schools as $i => $school)
                    @php
                        $pct = $maxSchoolEngagement > 0
                            ? round(($school['total_engagement'] / $maxSchoolEngagement) * 100)
                            : 0;
                    @endphp
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-gray-900 dark:text-white truncate max-w-[60%]">
                                {{ $school['school_or_company'] }}
                            </span>
                            <span class="text-gray-500 dark:text-gray-400 shrink-0 ml-2">
                                {{ number_format($school['total_engagement']) }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="h-1.5 flex-1 rounded-full bg-gray-100 dark:bg-gray-800">
                                <div class="h-1.5 rounded-full bg-emerald-500 transition-all" style="width: {{ $pct }}%"></div>
                            </div>
                            <span class="text-xs text-gray-400 w-24 text-right shrink-0">
                                {{ number_format($school['total_users']) }}u · {{ number_format($school['approved_concepts']) }}c
                            </span>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">No school or company activity yet.</p>
                @endforelse
            </div>
        </x-filament::section>

        {{-- Funding-Ready Concepts --}}
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <x-filament::icon icon="heroicon-o-banknotes" class="h-4 w-4 text-gray-400" />
                    Funding-Ready Concepts
                </div>
            </x-slot>

            <div class="space-y-3">
                @forelse ($concepts as $concept)
                    @php
                        $score     = (float) ($concept['funding_readiness_score'] ?? 0);
                        $pct       = $maxReadiness > 0 ? round(($score / $maxReadiness) * 100) : 0;
                        $scoreColor = $score >= 50 ? 'text-emerald-600 dark:text-emerald-400'
                            : ($score >= 20 ? 'text-amber-600 dark:text-amber-400'
                            : 'text-gray-500 dark:text-gray-400');
                        $barColor  = $score >= 50 ? 'bg-emerald-500'
                            : ($score >= 20 ? 'bg-amber-500' : 'bg-gray-400');
                    @endphp
                    <div class="space-y-1">
                        <div class="flex items-start justify-between gap-2">
                            <div class="min-w-0">
                                <div class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                    {{ $concept['title'] }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400 truncate">
                                    {{ $concept['creator_name'] ?? 'Unknown' }}
                                    @if (!empty($concept['school_or_company']))
                                        · {{ $concept['school_or_company'] }}
                                    @endif
                                </div>
                            </div>
                            <span class="shrink-0 text-sm font-semibold tabular-nums {{ $scoreColor }}">
                                {{ number_format($score, 1) }}
                            </span>
                        </div>
                        <div class="h-1.5 w-full rounded-full bg-gray-100 dark:bg-gray-800">
                            <div class="h-1.5 rounded-full {{ $barColor }} transition-all" style="width: {{ $pct }}%"></div>
                        </div>
                        @if (!empty($concept['recommended_next_action']))
                            <p class="text-xs text-gray-400 dark:text-gray-500 leading-snug">
                                {{ $concept['recommended_next_action'] }}
                            </p>
                        @endif
                    </div>
                @empty
                    <p class="text-sm text-gray-400">No funding-ready concepts yet.</p>
                @endforelse
            </div>
        </x-filament::section>

    </div>
</x-filament-widgets::widget>
