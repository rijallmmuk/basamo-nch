@props([
    'icon' => 'heroicon-o-inbox',
    'title' => 'Belum ada data',
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center rounded-2xl border border-dashed border-gray-300 bg-white px-6 py-16 text-center']) }}>
    <x-dynamic-component :component="$icon" class="h-10 w-10 text-gray-300" />
    <p class="mt-4 font-semibold text-gray-700">{{ $title }}</p>
    @if($subtitle)
        <p class="mt-1 text-sm text-gray-400">{{ $subtitle }}</p>
    @endif
    @if(trim($slot))
        <div class="mt-5">{{ $slot }}</div>
    @endif
</div>
