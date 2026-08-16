@props([
    'label' => 'Lihat dokumen',
    'keterangan' => null,
    'ikon' => 'heroicon-s-document-text',
])

{{-- Pembungkus "muat saat diminta" untuk isi berat: PDF, video, audio.

     Satu halaman materi boleh berisi banyak berkas sekaligus, dan setiap berkas
     dilayani lewat rute yang memeriksa kewenangan warga, bukan berkas statis. Memuat
     semuanya begitu halaman dibuka berarti sekian unduhan penuh sekaligus, ditambah
     sekian kali proses pemeriksaan di server.

     JEBAKAN: menyembunyikan iframe dengan `x-show`, `hidden`, atau `display:none`
     TIDAK menghentikan unduhannya, peramban tetap menarik isinya. Karena itu isinya
     dititipkan di dalam `<template x-if>` sehingga benar-benar belum ada di DOM
     sampai tombolnya ditekan. Alpine memuat isi template hanya saat kondisinya benar. --}}
<div x-data="{ dimuat: false }" data-muat-diminta class="relative w-full">
    <template x-if="! dimuat">
        <div class="flex flex-col items-center justify-center gap-3 bg-surface-container px-6 py-14 text-center">
            <x-dynamic-component :component="$ikon" class="h-10 w-10 text-outline" />

            @if(filled($keterangan))
                <p class="text-xs text-on-surface-variant">{{ $keterangan }}</p>
            @endif

            <button type="button" @click="dimuat = true"
                class="inline-flex min-h-11 items-center gap-2 rounded-xl bg-primary px-5 text-sm font-extrabold text-on-primary shadow-xs transition hover:bg-surface-tint focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2">
                <x-heroicon-s-eye class="h-4 w-4" />
                <span>{{ $label }}</span>
            </button>
        </div>
    </template>

    <template x-if="dimuat">
        {{ $slot }}
    </template>
</div>
