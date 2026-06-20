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
        'soft' => 'bg-indigo-100 text-indigo-700',
        'solid' => 'bg-indigo-600 text-white',
        'gray' => 'bg-gray-100 text-gray-600',
    ];
    $dim = $sizes[$size] ?? $sizes['md'];
@endphp

@if($src)
    <img src="{{ $src }}" alt="{{ $name }}"
        {{ $attributes->merge(['class' => "shrink-0 rounded-full object-cover {$dim}"]) }}>
@else
    <div {{ $attributes->merge(['class' => "flex shrink-0 items-center justify-center rounded-full font-bold {$dim} ".($variants[$variant] ?? $variants['soft'])]) }}>
        {{ strtoupper(mb_substr($name ?: '?', 0, 1)) }}
    </div>
@endif
