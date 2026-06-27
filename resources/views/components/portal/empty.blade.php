@props([
    'icon' => 'heroicon-o-inbox',
    'title' => 'Belum ada data',
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center rounded-2xl border border-dashed border-outline-variant bg-surface-container-lowest px-6 py-16 text-center']) }}>
    <x-dynamic-component :component="$icon" class="h-10 w-10 text-outline-variant" />
    <p class="mt-4 font-semibold text-on-surface">{{ $title }}</p>
    @if($subtitle)
        <p class="mt-1 text-sm text-on-surface-variant">{{ $subtitle }}</p>
    @endif
    @if(trim($slot))
        <div class="mt-5">{{ $slot }}</div>
    @endif
</div>
