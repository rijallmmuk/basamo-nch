{{-- Detail produk di modal verifikasi "Tinjau & Setujui" (read-only). --}}
@php
    $photos = $product->getMedia('photos');
    $profile = $product->umkmProfile;
@endphp

<div class="space-y-5 text-sm">
    <div>
        <p class="mb-2 font-bold text-gray-950 dark:text-white">Produk</p>
        <dl class="divide-y divide-gray-100 rounded-xl border border-gray-200 dark:divide-white/10 dark:border-white/10">
            @foreach([
                'Nama produk' => $product->nama_produk,
                'Kategori' => $product->category?->nama ?? '—',
                'Harga' => $product->harga !== null ? 'Rp '.number_format($product->harga, 0, ',', '.') : '—',
                'Deskripsi' => $product->deskripsi,
            ] as $label => $value)
                <div class="flex gap-4 px-4 py-2.5">
                    <dt class="w-32 shrink-0 text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                    <dd class="min-w-0 flex-1 whitespace-pre-line font-medium text-gray-950 dark:text-white">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>

        @if($photos->isNotEmpty())
            @include('filament.partials.photo-lightbox', ['photos' => $photos])
        @else
            <p class="mt-3 rounded-xl bg-gray-50 px-4 py-3 text-gray-600 dark:bg-white/5 dark:text-gray-300">
                <span class="font-semibold">Perhatian:</span> produk ini belum punya foto — pertimbangkan
                menolak dengan alasan agar pemilik mengunggah foto dulu.
            </p>
        @endif
    </div>

    <div>
        <p class="mb-2 font-bold text-gray-950 dark:text-white">Usaha</p>
        <dl class="divide-y divide-gray-100 rounded-xl border border-gray-200 dark:divide-white/10 dark:border-white/10">
            @foreach([
                'Nama usaha' => $profile?->nama_usaha ?? '—',
                'Pemilik' => $profile?->owner ? $profile->owner->name.' — NIK '.$profile->owner->nik : '—',
            ] as $label => $value)
                <div class="flex gap-4 px-4 py-2.5">
                    <dt class="w-32 shrink-0 text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                    <dd class="min-w-0 flex-1 font-medium text-gray-950 dark:text-white">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>
    </div>

    <p class="rounded-xl bg-gray-50 px-4 py-3 text-gray-600 dark:bg-white/5 dark:text-gray-300">
        Menyetujui produk ini akan <span class="font-semibold">menayangkannya di katalog publik</span>
        selama lapak pemiliknya aktif.
    </p>
</div>
