@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
    'loading' => true,            // spinner otomatis untuk tombol submit (opt-out: :loading="false")
    'loadingText' => 'Memproses…',
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

    // Tombol submit → dua-state (label ↔ spinner) yang digerakkan handler global di app.js.
    $isLoadingSubmit = $href === null && $type === 'submit' && $loading;
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</a>
@elseif($isLoadingSubmit)
    <button type="submit" data-loading {{ $attributes->merge(['class' => $classes]) }}>
        <span data-loading-label class="inline-flex items-center gap-2">{{ $slot }}</span>
        <span data-loading-spinner class="hidden items-center gap-2">
            <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
            </svg>
            {{ $loadingText }}
        </span>
    </button>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>{{ $slot }}</button>
@endif
