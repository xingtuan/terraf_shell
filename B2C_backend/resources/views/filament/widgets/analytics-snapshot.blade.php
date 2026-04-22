<x-filament-widgets::widget>
    @php
        $summary    = $overview['summary'] ?? [];
        $categories = $overview['categories']['highest_engagement'] ?? [];
        $schools    = $overview['activity']['schools_or_companies'] ?? [];
        $concepts   = $overview['funding']['most_likely_concepts'] ?? [];

        $maxCatEngagement    = collect($categories)->max('total_engagement') ?: 1;
        $maxSchoolEngagement = collect($schools)->max('total_engagement') ?: 1;
        $maxReadiness        = collect($concepts)->max('funding_readiness_score') ?: 1;
    @endphp

    {{-- Summary metric strip --}}
    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:1rem; margin-bottom:1.5rem;">

        <div style="display:flex; align-items:center; gap:0.75rem; background:#fffbeb; border:1px solid #fde68a; border-radius:0.75rem; padding:1rem;">
            <x-filament::icon icon="heroicon-o-bolt" style="width:1.25rem;height:1.25rem;color:#f59e0b;flex-shrink:0;" />
            <div>
                <div style="font-size:0.75rem;color:#6b7280;font-weight:500;">Total Engagement</div>
                <div style="font-size:1.5rem;font-weight:700;line-height:1.2;">{{ number_format((int)($summary['total_engagement'] ?? 0)) }}</div>
            </div>
        </div>

        <div style="display:flex; align-items:center; gap:0.75rem; background:#eff6ff; border:1px solid #bfdbfe; border-radius:0.75rem; padding:1rem;">
            <x-filament::icon icon="heroicon-o-eye" style="width:1.25rem;height:1.25rem;color:#3b82f6;flex-shrink:0;" />
            <div>
                <div style="font-size:0.75rem;color:#6b7280;font-weight:500;">Total Views</div>
                <div style="font-size:1.5rem;font-weight:700;line-height:1.2;">{{ number_format((int)($summary['total_views'] ?? 0)) }}</div>
            </div>
        </div>

        <div style="display:flex; align-items:center; gap:0.75rem; background:#f5f3ff; border:1px solid #ddd6fe; border-radius:0.75rem; padding:1rem;">
            <x-filament::icon icon="heroicon-o-briefcase" style="width:1.25rem;height:1.25rem;color:#8b5cf6;flex-shrink:0;" />
            <div>
                <div style="font-size:0.75rem;color:#6b7280;font-weight:500;">B2B Leads</div>
                <div style="font-size:1.5rem;font-weight:700;line-height:1.2;">{{ number_format((int)($summary['total_b2b_leads'] ?? 0)) }}</div>
            </div>
        </div>

        <div style="display:flex; align-items:center; gap:0.75rem; background:#ecfdf5; border:1px solid #a7f3d0; border-radius:0.75rem; padding:1rem;">
            <x-filament::icon icon="heroicon-o-banknotes" style="width:1.25rem;height:1.25rem;color:#10b981;flex-shrink:0;" />
            <div>
                <div style="font-size:0.75rem;color:#6b7280;font-weight:500;">Funding-Linked</div>
                <div style="font-size:1.5rem;font-weight:700;line-height:1.2;">{{ number_format((int)($summary['concepts_with_funding_campaigns'] ?? 0)) }}</div>
            </div>
        </div>

    </div>

    <div style="display:grid; grid-template-columns:repeat(auto-fit,minmax(280px,1fr)); gap:1.5rem;">

        {{-- Top Categories --}}
        <x-filament::section>
            <x-slot name="heading">Top Categories</x-slot>

            <div style="display:flex;flex-direction:column;gap:0.75rem;">
                @forelse ($categories as $i => $category)
                    @php
                        $pct = $maxCatEngagement > 0 ? round(($category['total_engagement'] / $maxCatEngagement) * 100) : 0;
                        $barColors = ['#3b82f6','#8b5cf6','#10b981','#f59e0b','#f43f5e'];
                        $bar = $barColors[$i % count($barColors)];
                    @endphp
                    <div>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.25rem;">
                            <span style="font-size:0.875rem;font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:65%;">{{ $category['name'] }}</span>
                            <span style="font-size:0.75rem;color:#6b7280;flex-shrink:0;margin-left:0.5rem;">{{ number_format($category['total_engagement']) }}</span>
                        </div>
                        <div style="display:flex;align-items:center;gap:0.5rem;">
                            <div style="flex:1;height:6px;border-radius:9999px;background:#f3f4f6;">
                                <div style="height:6px;border-radius:9999px;background:{{ $bar }};width:{{ $pct }}%;transition:width 0.3s;"></div>
                            </div>
                            <span style="font-size:0.7rem;color:#9ca3af;width:2.5rem;text-align:right;flex-shrink:0;">{{ number_format($category['total_concepts']) }}c</span>
                        </div>
                    </div>
                @empty
                    <p style="font-size:0.875rem;color:#9ca3af;">No category analytics yet.</p>
                @endforelse
            </div>
        </x-filament::section>

        {{-- Active Schools / Companies --}}
        <x-filament::section>
            <x-slot name="heading">Active Schools &amp; Companies</x-slot>

            <div style="display:flex;flex-direction:column;gap:0.75rem;">
                @forelse ($schools as $school)
                    @php
                        $pct = $maxSchoolEngagement > 0 ? round(($school['total_engagement'] / $maxSchoolEngagement) * 100) : 0;
                    @endphp
                    <div>
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.25rem;">
                            <span style="font-size:0.875rem;font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:65%;">{{ $school['school_or_company'] }}</span>
                            <span style="font-size:0.75rem;color:#6b7280;flex-shrink:0;margin-left:0.5rem;">{{ number_format($school['total_engagement']) }}</span>
                        </div>
                        <div style="display:flex;align-items:center;gap:0.5rem;">
                            <div style="flex:1;height:6px;border-radius:9999px;background:#f3f4f6;">
                                <div style="height:6px;border-radius:9999px;background:#10b981;width:{{ $pct }}%;transition:width 0.3s;"></div>
                            </div>
                            <span style="font-size:0.7rem;color:#9ca3af;width:5rem;text-align:right;flex-shrink:0;">{{ number_format($school['total_users']) }}u · {{ number_format($school['approved_concepts']) }}c</span>
                        </div>
                    </div>
                @empty
                    <p style="font-size:0.875rem;color:#9ca3af;">No school or company activity yet.</p>
                @endforelse
            </div>
        </x-filament::section>

        {{-- Funding-Ready Concepts --}}
        <x-filament::section>
            <x-slot name="heading">Funding-Ready Concepts</x-slot>

            <div style="display:flex;flex-direction:column;gap:0.875rem;">
                @forelse ($concepts as $concept)
                    @php
                        $score    = (float)($concept['funding_readiness_score'] ?? 0);
                        $pct      = $maxReadiness > 0 ? round(($score / $maxReadiness) * 100) : 0;
                        $barColor = $score >= 50 ? '#10b981' : ($score >= 20 ? '#f59e0b' : '#9ca3af');
                        $scoreColor = $score >= 50 ? '#059669' : ($score >= 20 ? '#d97706' : '#6b7280');
                    @endphp
                    <div>
                        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:0.5rem;margin-bottom:0.25rem;">
                            <div style="min-width:0;">
                                <div style="font-size:0.875rem;font-weight:500;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">{{ $concept['title'] }}</div>
                                <div style="font-size:0.75rem;color:#6b7280;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                    {{ $concept['creator_name'] ?? 'Unknown' }}
                                    @if (!empty($concept['school_or_company']))· {{ $concept['school_or_company'] }}@endif
                                </div>
                            </div>
                            <span style="font-size:0.875rem;font-weight:700;color:{{ $scoreColor }};flex-shrink:0;font-variant-numeric:tabular-nums;">{{ number_format($score, 1) }}</span>
                        </div>
                        <div style="height:6px;width:100%;border-radius:9999px;background:#f3f4f6;margin-bottom:0.25rem;">
                            <div style="height:6px;border-radius:9999px;background:{{ $barColor }};width:{{ $pct }}%;transition:width 0.3s;"></div>
                        </div>
                        @if (!empty($concept['recommended_next_action']))
                            <p style="font-size:0.7rem;color:#9ca3af;line-height:1.4;">{{ $concept['recommended_next_action'] }}</p>
                        @endif
                    </div>
                @empty
                    <p style="font-size:0.875rem;color:#9ca3af;">No funding-ready concepts yet.</p>
                @endforelse
            </div>
        </x-filament::section>

    </div>
</x-filament-widgets::widget>
