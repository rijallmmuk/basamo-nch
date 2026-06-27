@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
])

@php
    $variants = [
        'primary' => 'bg-primary text-on-primary hover:bg-surface-tint shadow-sm',
        'secondary' => 'bg-surface-container-high text-on-surface-variant hover:bg-surface-container-highest',
        'ghost' => 'text-on-surface-variant hover:bg-surface-container-high',
        'accent' => 'bg-secondary-container text-on-secondary-container hover:bg-secondary-fixed-dim shadow-sm',
        'danger' => 'bg-error text-on-error hover:bg-on-error-container shadow-sm',
    ];
    $sizes = [
        'sm' => 'gap-1.5 px-3 py-2 text-sm',
        'md' => 'gap-2 px-5 py-2.5 text-sm',
        'lg' => 'gap-2.5 px-6 py-3.5 text-base',
    ];
    $classes = 'inline-flex items-center justify-center rounded-xl font-bold transition-colors disabled:cursor-not-allowed disabled:opacity-60 '
        .($variants[$variant] ?? $variants['primary']).' '.($sizes[$size] ?? $sizes['md']);
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
