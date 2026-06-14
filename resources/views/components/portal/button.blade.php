@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
])

@php
    $variants = [
        'primary' => 'bg-indigo-600 text-white hover:bg-indigo-700 shadow-sm',
        'secondary' => 'bg-gray-100 text-gray-700 hover:bg-gray-200',
        'ghost' => 'text-gray-600 hover:bg-gray-100',
        'danger' => 'bg-red-600 text-white hover:bg-red-700 shadow-sm',
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
