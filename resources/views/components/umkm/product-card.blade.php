{{--
    Kartu produk UMKM — SATU sarang tampilan untuk semua konteks: katalog publik,
    katalog publik, panel Filament, pratinjau form, dst. Struktur ala e-commerce:
    foto 1:1 (zoom halus saat hover) → chip kategori → nama (2 baris) → harga →
    meta (penjual · dilihat). Variasi konteks lewat props & slot:
      - :kategori / :penjual / :dilihat  → matikan bagian yang tak relevan di konteksnya
      - <x-slot:badge>                   → overlay pojok foto (mis. status moderasi)
      - {{ $slot }}                      → footer bebas (aksi pemilik, tombol WhatsApp, …)
    Pemanggil WAJIB eager-load relasi: ->with(['category', 'umkmProfile']).
--}}
@props(['product', 'kategori' => true, 'penjual' => true, 'dilihat' => false])

<div class="group flex h-full flex-col overflow-hidden rounded-2xl border border-outline-variant bg-surface-container-lowest shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-primary/35 hover:shadow-lg">
    <div class="relative overflow-hidden aspect-square bg-surface-container-high">
        <img
            src="{{ $product->coverUrl() }}"
            alt="{{ $product->nama_produk }}"
            loading="lazy"
            class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.03]"
        >
        {{ $badge ?? '' }}
    </div>

    <div class="flex flex-1 flex-col p-4">
        @if ($kategori && $product->category)
            <p class="mb-1.5 truncate text-xs font-semibold text-on-surface-variant">
                {{ $product->category->nama }}
            </p>
        @endif

        <h3 class="line-clamp-2 min-h-10 text-sm font-semibold leading-5 text-on-surface transition-colors group-hover:text-primary">
            {{ $product->nama_produk }}
        </h3>

        <div class="mt-auto pt-3">
            @if ($product->harga)
                <p class="text-base font-extrabold tracking-tight text-primary">Rp {{ number_format($product->harga, 0, ',', '.') }}</p>
            @else
                <p class="text-xs font-semibold text-on-surface-variant">Hubungi penjual untuk harga</p>
            @endif

            @if ($penjual || $dilihat)
                <div class="mt-3 flex min-w-0 items-center gap-2 border-t border-outline-variant/70 pt-3 text-xs text-on-surface-variant">
                    @if ($penjual)
                        <span class="inline-flex min-w-0 items-center gap-1" title="{{ $product->umkmProfile?->nama_usaha }}">
                            <x-heroicon-o-building-storefront class="h-4 w-4 shrink-0" />
                            <span class="truncate">{{ $product->umkmProfile?->nama_usaha }}</span>
                        </span>
                    @endif

                    @if ($dilihat)
                        <span class="ml-auto inline-flex shrink-0 items-center gap-1" title="Dilihat {{ number_format($product->jumlah_dilihat ?? 0, 0, ',', '.') }} kali">
                            <x-heroicon-o-eye class="h-4 w-4" />
                            {{ number_format($product->jumlah_dilihat ?? 0, 0, ',', '.') }}
                        </span>
                    @endif
                </div>
            @endif

            {{ $slot }}
        </div>
    </div>
</div>
