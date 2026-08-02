@php
    $record = $record ?? $getRecord();
    $url = $record ? route('admin.nagari.boundary.show', ['nagari' => $record->getKey()]) : null;
@endphp

@vite('resources/js/filament/nagari-boundary-map.js')

<div
    wire:ignore
    data-nagari-boundary-map
    data-boundary-url="{{ $url }}"
    class="relative overflow-hidden rounded-2xl border border-gray-200 bg-gray-50"
>
    <div data-map-canvas class="h-80 w-full" aria-label="Peta batas {{ $record?->nama_lengkap }}"></div>

    <div
        data-map-status
        role="status"
        class="absolute inset-x-3 bottom-3 z-[500] rounded-xl border border-gray-200 bg-white/95 px-4 py-3 text-sm text-gray-600 shadow-lg backdrop-blur-sm"
    >
        <div class="flex items-start gap-3">
            <x-filament::loading-indicator data-map-spinner class="mt-0.5 h-4 w-4 shrink-0 text-primary-600" />
            <x-filament::icon data-map-status-icon icon="heroicon-o-information-circle" class="mt-0.5 hidden h-4 w-4 shrink-0 text-primary-600" />
            <span data-map-status-copy>Memuat batas wilayah Nagari…</span>
        </div>
    </div>
</div>
