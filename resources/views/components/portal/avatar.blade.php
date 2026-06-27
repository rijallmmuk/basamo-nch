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
        'gray' => 'bg-surface-container-high text-on-surface-variant',
    ];
    $dim = $sizes[$size] ?? $sizes['md'];

    // Inisial 2 huruf (huruf depan 2 kata pertama) — konsisten dgn kartu peringkat beranda.
    $initials = collect(preg_split('/\s+/', trim($name ?: '?')))
        ->filter()
        ->take(2)
        ->map(fn ($p) => mb_substr($p, 0, 1))
        ->join('');
    $initials = mb_strtoupper($initials ?: '?');
@endphp

@if($src)
    <img src="{{ $src }}" alt="{{ $name }}"
        {{ $attributes->merge(['class' => "shrink-0 rounded-full object-cover {$dim}"]) }}>
@else
    <div {{ $attributes->merge(['class' => "flex shrink-0 items-center justify-center rounded-full font-bold {$dim} ".($variants[$variant] ?? $variants['soft'])]) }}>
        {{ $initials }}
    </div>
@endif
