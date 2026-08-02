@php
    $record = $getRecord();
@endphp

<div class="aspect-[4/3] overflow-hidden rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10">
    @if ($record?->punyaCover())
        <img src="{{ $record->coverUrl() }}" alt="Cover {{ $record->temaNama() }}" class="h-full w-full object-cover">
    @else
        {{-- Belum diunggah: gambar otomatis dari nama tema. --}}
        <x-slc.tema-cover :nama="$record?->temaNama()" ratio="4:3" />
    @endif
</div>
