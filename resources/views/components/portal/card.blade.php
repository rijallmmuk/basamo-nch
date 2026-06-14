@props(['padded' => true])

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm'.($padded ? ' p-5 sm:p-6' : '')]) }}>
    {{ $slot }}
</div>
