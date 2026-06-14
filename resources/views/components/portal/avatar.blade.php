@props([
    'name' => '?',
    'size' => 'md',
    'variant' => 'soft',
])

@php
    $sizes = [
        'sm' => 'h-8 w-8 text-xs',
        'md' => 'h-9 w-9 text-sm',
        'lg' => 'h-10 w-10 text-base',
    ];
    $variants = [
        'soft' => 'bg-indigo-100 text-indigo-700',
        'solid' => 'bg-indigo-600 text-white',
        'gray' => 'bg-gray-100 text-gray-600',
    ];
    $classes = ($sizes[$size] ?? $sizes['md']).' '.($variants[$variant] ?? $variants['soft']);
@endphp

<div {{ $attributes->merge(['class' => "flex shrink-0 items-center justify-center rounded-full font-bold {$classes}"]) }}>
    {{ strtoupper(mb_substr($name ?: '?', 0, 1)) }}
</div>
