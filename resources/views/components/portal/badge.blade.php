@props(['color' => 'gray'])

@php
    // Nama warna lama (gray/indigo/…) dipetakan ke token semantik NCH agar pemanggil
    // lama tetap jalan tanpa ubah API.
    $map = [
        'gray' => 'bg-surface-container-high text-on-surface-variant',
        'indigo' => 'bg-primary/10 text-primary',
        'emerald' => 'bg-sdg-3/10 text-sdg-3',
        'amber' => 'bg-secondary-container text-on-secondary-container',
        'sky' => 'bg-sdg-14/10 text-sdg-14',
        'red' => 'bg-error-container text-on-error-container',
    ];
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold '.($map[$color] ?? $map['gray'])]) }}>
    {{ $slot }}
</span>
