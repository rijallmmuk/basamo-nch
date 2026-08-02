@props([
    'name' => '?',
    'size' => 'md',
    'variant' => 'soft',
    'src' => null,
])

@php
    $sizes = [
        'sm' => 'h-8 w-8 text-xs',
        'md' => 'h-9 w-9 text-sm',
        'lg' => 'h-10 w-10 text-base',
    ];
    $variants = [
        'soft' => 'bg-primary/10 text-primary',
        'solid' => 'bg-primary text-on-primary',
        'neutral' => 'bg-surface-container-high text-on-surface-variant',
    ];
    $dimensions = $sizes[$size] ?? $sizes['md'];
    $initials = collect(preg_split('/\s+/', trim($name ?: '?')))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_substr($part, 0, 1))
        ->join('');
    $initials = mb_strtoupper($initials ?: '?');
@endphp

@if($src)
    <img src="{{ $src }}" alt="{{ $name }}"
        {{ $attributes->class(["shrink-0 rounded-full object-cover ring-1 ring-outline-variant/70 {$dimensions}"]) }}>
@else
    <span
        {{ $attributes->class(["inline-flex shrink-0 items-center justify-center rounded-full font-bold ring-1 ring-black/5 {$dimensions} ".($variants[$variant] ?? $variants['soft'])]) }}
        aria-hidden="true">
        {{ $initials }}
    </span>
@endif
