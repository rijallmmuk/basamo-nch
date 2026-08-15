@php
    /* Sebangun dengan {@see filament/tables/pelatihan-cover}. Sebelumnya kolom ini
       memakai `defaultImageUrl` ke berkas statis `default-module-cover.svg`, yang
       sama persis untuk SELURUH modul dan bertuliskan "MODUL LITERASI" walau
       modulnya bukan tentang literasi. */
    $record = $getRecord();
@endphp

<div class="w-16 overflow-hidden rounded-lg ring-1 ring-gray-950/10 dark:ring-white/10">
    @if ($record->punyaCover())
        <img
            src="{{ $record->coverUrl() }}"
            alt="Sampul {{ $record->judul }}"
            class="aspect-[4/3] w-full object-cover"
        >
    @else
        <x-slc.module-cover :judul="$record->judul" :seed="$record->getKey()" ratio="4:3" />
    @endif
</div>
