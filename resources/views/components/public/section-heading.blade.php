@props([
    'eyebrow',
    'title',
    'description' => null,
    'align' => 'left',
    'tone' => 'light',
])

@php
    $isCenter = $align === 'center';
    $isDark = $tone === 'dark';
    $eyebrowClass = $isDark ? 'text-secondary-container' : 'text-secondary';
    $titleClass = $isDark ? 'text-on-primary' : 'text-primary';
    $descClass = $isDark ? 'text-on-primary/80' : 'text-on-surface-variant';
    $lineClass = $isDark ? 'bg-secondary-container/60' : 'bg-secondary/60';
@endphp

<div {{ $attributes->class([
    'space-y-4 reveal-up',
    'mx-auto max-w-3xl text-center' => $isCenter,
    'max-w-3xl' => ! $isCenter,
]) }}>
    <span @class([
        'inline-flex items-center gap-3 text-xs sm:text-sm font-bold uppercase tracking-[0.18em]',
        'justify-center' => $isCenter,
        $eyebrowClass,
    ])>
        @unless($isCenter)
            <span class="h-px w-10 transition-all duration-500 {{ $lineClass }}" aria-hidden="true"></span>
        @endunless
        {{ $eyebrow }}
        @if($isCenter)
            <span class="h-px w-10 transition-all duration-500 {{ $lineClass }}" aria-hidden="true"></span>
        @endif
    </span>
    <h2 class="text-section text-balance font-extrabold tracking-tight {{ $titleClass }}">{{ $title }}</h2>
    @if(filled($description))
        <p class="text-pretty text-base sm:text-lg leading-relaxed font-light {{ $descClass }}">{{ $description }}</p>
    @endif
    {{ $slot }}
</div>

