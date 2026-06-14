@props(['value' => 0, 'color' => 'bg-indigo-500', 'track' => 'bg-gray-100'])

@php $pct = max(0, min(100, (int) $value)); @endphp

<div {{ $attributes->merge(['class' => "h-2 overflow-hidden rounded-full {$track}"]) }}>
    <div class="h-full rounded-full {{ $color }} transition-all" style="width: {{ $pct }}%"></div>
</div>
