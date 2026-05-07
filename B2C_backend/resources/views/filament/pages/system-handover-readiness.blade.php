<x-filament-panels::page>
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <table class="w-full divide-y divide-gray-200 text-sm dark:divide-gray-800">
            <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-700 dark:text-gray-200">Check</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-700 dark:text-gray-200">Value</th>
                    <th class="px-4 py-3 text-left font-medium text-gray-700 dark:text-gray-200">Status</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach ($this->checks() as $check)
                    @php
                        $color = match ($check['status']) {
                            'ok' => 'success',
                            'warning' => 'warning',
                            default => 'danger',
                        };
                        $label = match ($check['status']) {
                            'ok' => __('admin.system.ok'),
                            'warning' => __('admin.system.warning'),
                            default => __('admin.system.error'),
                        };
                    @endphp
                    <tr>
                        <td class="px-4 py-3 font-medium text-gray-950 dark:text-white">{{ $check['label'] }}</td>
                        <td class="px-4 py-3 text-gray-700 dark:text-gray-300">
                            <div>{{ $check['value'] }}</div>
                            @if (! empty($check['detail']))
                                <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $check['detail'] }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <x-filament::badge :color="$color">
                                {{ $label }}
                            </x-filament::badge>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-filament-panels::page>
