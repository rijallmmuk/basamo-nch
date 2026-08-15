@php
    /* Sampul modul. Bila belum ada unggahan, yang tampil adalah sampul yang digambar
       sistem, sama seperti yang dilihat warga di portal dan halaman publik. Label
       digambar sendiri karena ViewEntry mengganti seluruh render entri. */
    $record = $getRecord();
    $punyaUnggahan = $record?->punyaCover() ?? false;
    $judul = $record->judul ?? $record->temaNama();
@endphp

<div class="w-full max-w-xs">
    <div class="mb-1.5 text-sm font-medium text-gray-500 dark:text-gray-400">Sampul</div>

    <div class="aspect-[16/9] overflow-hidden rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10">
        @if ($punyaUnggahan)
            <img src="{{ $record->coverUrl() }}" alt="Sampul {{ $judul }}" class="h-full w-full object-cover">
        @else
            <x-slc.module-cover :judul="$judul" :seed="$record?->getKey()" ratio="16:9"
                class="h-full w-full object-cover" />
        @endif
    </div>

    @unless ($punyaUnggahan)
        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
            Digambar sistem dari judul modul. Unggah berkas untuk menggantinya.
        </p>
    @endunless
</div>
