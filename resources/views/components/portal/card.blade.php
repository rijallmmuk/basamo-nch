@props(['padded' => true])

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-2xl border border-outline-variant bg-surface-container-lowest shadow-sm'.($padded ? ' p-5 sm:p-6' : '')]) }}>
    {{ $slot }}
</div>
