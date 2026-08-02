@props(['cols' => 6])

{{-- Pembungkus grid untuk kartu angka. Jumlah kolom dipilih dari daftar tetap,
     bukan dirakit dari string, karena Tailwind memindai kelas secara harfiah:
     kelas yang dirangkai saat runtime tidak ikut terkompilasi dan grid-nya diam
     diam jatuh ke satu kolom. --}}
@php
    $kelas = match ((int) $cols) {
        2 => 'grid grid-cols-2 gap-3',
        3 => 'grid grid-cols-2 gap-3 sm:grid-cols-3',
        4 => 'grid grid-cols-2 gap-3 sm:grid-cols-2 lg:grid-cols-4',
        5 => 'grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-5',
        default => 'grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-6',
    };
@endphp

<div {{ $attributes->class($kelas) }}>
    {{ $slot }}
</div>
