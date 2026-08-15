@php
    /* Dipakai infolist Modul dan Pelatihan. Keduanya memakai sampul yang DIGAMBAR
       bila belum ada unggahan, sama seperti portal dan halaman publik, supaya satu
       modul tampil serupa di mana pun ia muncul. */
    $record = $getRecord();
    $punyaUnggahan = method_exists($record, 'punyaCover') && $record->punyaCover();
    $judul = $record->judul ?? $record->temaNama();
@endphp

<div class="w-full flex flex-col items-center justify-center p-1">
    <div class="relative aspect-[4/3] w-full overflow-hidden rounded-2xl border border-gray-200 bg-gray-100 shadow-sm dark:border-gray-700 dark:bg-gray-800">
        @if($punyaUnggahan)
            <img src="{{ $record->coverUrl() }}" alt="Sampul {{ $judul }}" class="w-full h-full object-cover" />
        @else
            <x-slc.module-cover :judul="$judul" :seed="$record->getKey()" ratio="4:3" ringkas class="h-full w-full object-cover" />
        @endif
    </div>
</div>
