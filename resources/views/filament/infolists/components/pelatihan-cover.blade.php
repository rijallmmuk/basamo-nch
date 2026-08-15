@php
    /* ViewEntry mengganti seluruh render entri, termasuk labelnya, jadi label digambar
       sendiri di sini. Pola yang sama dipakai {@see deskripsi-ringkas}. */
    $record = $getRecord();
    $punyaUnggahan = $record?->punyaCover() ?? false;
@endphp

<div class="w-full max-w-xs">
    <div class="mb-1.5 text-sm font-medium text-gray-500 dark:text-gray-400">Sampul</div>

    <div class="aspect-[16/9] overflow-hidden rounded-xl ring-1 ring-gray-950/5 dark:ring-white/10">
        @if ($punyaUnggahan)
            <img src="{{ $record->coverUrl() }}" alt="Sampul {{ $record->temaNama() }}" class="h-full w-full object-cover">
        @else
            <x-slc.tema-cover :nama="$record?->temaNama()" :seed="$record?->getKey()" ratio="16:9"
                class="h-full w-full object-cover" />
        @endif
    </div>

    @unless ($punyaUnggahan)
        <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
            Digambar sistem dari nama tema. Unggah berkas untuk menggantinya.
        </p>
    @endunless
</div>
