@props([
    'icon' => 'heroicon-o-inbox',
    'title',
    'description' => null,
    'tone' => 'light',
])

{{-- Keadaan kosong TUNGGAL untuk seluruh halaman publik.

     Halaman ini melayani nagari yang isinya belum lengkap, dan keadaan kosong akan
     sering terlihat. Karena itu bunyinya harus seragam dan selalu MENJELASKAN
     sebabnya, bukan sekadar berkata "tidak ada data": pengunjung perlu tahu apakah
     ini belum diisi, atau memang tidak ada. --}}
@php
    $latar = $tone === 'dark'
        ? 'border-on-primary/20 bg-on-primary/5'
        : 'border-outline-variant bg-surface-container-low';
    $judulWarna = $tone === 'dark' ? 'text-on-primary' : 'text-primary';
    $isiWarna = $tone === 'dark' ? 'text-on-primary/70' : 'text-on-surface-variant';
@endphp

<div {{ $attributes->class(['flex flex-col items-center justify-center rounded-2xl border border-dashed px-6 py-12 text-center', $latar]) }}>
    <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-surface-container-high text-outline">
        <x-dynamic-component :component="$icon" class="h-6 w-6" />
    </span>

    <p class="mt-4 font-extrabold {{ $judulWarna }}">{{ $title }}</p>

    @if(filled($description))
        <p class="mt-1 max-w-md text-sm leading-relaxed {{ $isiWarna }}">{{ $description }}</p>
    @endif

    @if(filled(trim($slot)))
        <div class="mt-5">{{ $slot }}</div>
    @endif
</div>
