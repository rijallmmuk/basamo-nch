{{-- Detail pengajuan UMKM di modal "Tinjau & Setujui" (read-only). --}}
@php
    $product = $profile->products->first();
    $photos = $product?->getMedia('photos') ?? collect();
@endphp

<div class="space-y-5 text-sm">
    <div>
        <p class="mb-2 font-bold text-gray-950 dark:text-white">Profil Usaha</p>
        <dl class="divide-y divide-gray-100 rounded-xl border border-gray-200 dark:divide-white/10 dark:border-white/10">
            @foreach([
                'Pengaju' => $profile->owner?->name.' — NIK '.$profile->owner?->nik,
                'Nama usaha' => $profile->nama_usaha,
                'Kategori' => $profile->category?->nama ?? '—',
                'No. WhatsApp' => $profile->whatsapp,
                'Alamat lengkap' => $profile->alamat,
            ] as $label => $value)
                <div class="flex gap-4 px-4 py-2.5">
                    <dt class="w-32 shrink-0 text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                    <dd class="min-w-0 flex-1 font-medium text-gray-950 dark:text-white">{{ $value }}</dd>
                </div>
            @endforeach
        </dl>
    </div>

    <div>
        <p class="mb-2 font-bold text-gray-950 dark:text-white">Produk Unggulan</p>
        @if($product)
            <dl class="divide-y divide-gray-100 rounded-xl border border-gray-200 dark:divide-white/10 dark:border-white/10">
                @foreach([
                    'Nama produk' => $product->nama_produk,
                    'Harga' => $product->harga !== null ? 'Rp '.number_format($product->harga, 0, ',', '.') : '—',
                    'Deskripsi' => $product->deskripsi,
                ] as $label => $value)
                    <div class="flex gap-4 px-4 py-2.5">
                        <dt class="w-32 shrink-0 text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                        <dd class="min-w-0 flex-1 font-medium text-gray-950 dark:text-white">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>

            @if($photos->isNotEmpty())
                @include('filament.partials.photo-lightbox', ['photos' => $photos])
            @endif
        @else
            <p class="text-gray-500 dark:text-gray-400">— Tidak ada produk terlampir —</p>
        @endif
    </div>

    <p class="rounded-xl bg-gray-50 px-4 py-3 text-gray-600 dark:bg-white/5 dark:text-gray-300">
        Menyetujui pengajuan ini akan <span class="font-semibold">mengaktifkan akses UMKM</span> warga,
        menayangkan lapaknya, dan <span class="font-semibold">menyetujui produk di atas</span> ke katalog publik.
    </p>
</div>
