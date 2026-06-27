@props(['value' => 0, 'color' => 'bg-primary', 'track' => 'bg-surface-container-high'])

@php $pct = max(0, min(100, (int) $value)); @endphp

<div {{ $attributes->merge(['class' => "h-2 overflow-hidden rounded-full {$track}"]) }}>
    <div class="h-full rounded-full {{ $color }} transition-all" style="width: {{ $pct }}%"></div>
</div>
