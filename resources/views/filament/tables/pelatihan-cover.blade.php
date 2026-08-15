@php
    $record = $getRecord();
@endphp

<div class="w-16 overflow-hidden rounded-lg ring-1 ring-gray-950/10 dark:ring-white/10">
    @if ($record->punyaCover())
        <img
            src="{{ $record->coverUrl() }}"
            alt="Cover {{ $record->temaNama() }}"
            class="aspect-[4/3] w-full object-cover"
        >
    @else
        <x-slc.tema-cover :nama="$record->temaNama()" ratio="4:3" ringkas />
    @endif
</div>
