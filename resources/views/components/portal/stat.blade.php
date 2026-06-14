@props([
    'label' => '',
    'value' => '',
    'sub' => null,
    'icon' => 'heroicon-s-chart-bar',
    'iconBg' => 'bg-indigo-100',
    'iconFg' => 'text-indigo-600',
])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-gray-200 bg-white p-5 shadow-sm']) }}>
    <div class="flex items-center gap-2.5">
        <span class="flex h-9 w-9 items-center justify-center rounded-lg {{ $iconBg }}">
            <x-dynamic-component :component="$icon" class="h-5 w-5 {{ $iconFg }}" />
        </span>
        <span class="text-sm font-medium text-gray-500">{{ $label }}</span>
    </div>
    <p class="mt-3 text-3xl font-bold text-gray-900">
        {{ $value }}
        @if(! is_null($sub))<span class="text-sm font-normal text-gray-400">{{ $sub }}</span>@endif
    </p>
</div>
