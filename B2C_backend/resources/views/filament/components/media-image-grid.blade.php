@php
    $urls = array_values(array_filter($urls ?? ($getState() ?? [])));
@endphp

@if (count($urls) > 0)
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
        @foreach ($urls as $url)
            <a
                href="{{ $url }}"
                target="_blank"
                rel="noreferrer"
                class="block overflow-hidden rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-900"
            >
                <img
                    src="{{ $url }}"
                    alt=""
                    class="aspect-video h-full w-full object-cover"
                    loading="lazy"
                >
            </a>
        @endforeach
    </div>
@endif
