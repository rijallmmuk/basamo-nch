@props([
    'label' => '',
    'value' => '',
    'sub' => null,
    'icon' => 'heroicon-s-chart-bar',
    'iconBg' => 'bg-primary/10',
    'iconFg' => 'text-primary',
])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-outline-variant bg-surface-container-lowest p-5 shadow-sm']) }}>
    <div class="flex items-center gap-2.5">
        <span class="flex h-9 w-9 items-center justify-center rounded-lg {{ $iconBg }}">
            <x-dynamic-component :component="$icon" class="h-5 w-5 {{ $iconFg }}" />
        </span>
        <span class="text-sm font-medium text-on-surface-variant">{{ $label }}</span>
    </div>
    <p class="mt-3 text-3xl font-bold text-on-surface">
        {{ $value }}
        @if(! is_null($sub))<span class="text-sm font-normal text-on-surface-variant">{{ $sub }}</span>@endif
    </p>
</div>
