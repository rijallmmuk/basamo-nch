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
@props(['product', 'kategori' => true, 'penjual' => true, 'dilihat' => true])

<div class="group flex h-full flex-col overflow-hidden rounded-2xl border border-outline-variant bg-surface-container-lowest shadow-sm transition-all hover:border-primary/40 hover:shadow-md">
    <div class="relative overflow-hidden">
        <img
            src="{{ $product->coverUrl() }}"
            alt="{{ $product->nama_produk }}"
            loading="lazy"
            class="aspect-square w-full object-cover transition-transform duration-300 group-hover:scale-105"
        >
        {{ $badge ?? '' }}
        @if ($kategori && $product->category)
            <span class="absolute bottom-2 left-2 rounded-full bg-surface-container-lowest/90 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-primary backdrop-blur-sm">
                {{ $product->category->nama }}
            </span>
        @endif
    </div>

    <div class="flex flex-1 flex-col p-3.5">
        <p class="line-clamp-2 min-h-10 text-sm font-semibold leading-5 text-on-surface">{{ $product->nama_produk }}</p>

        <div class="mt-1 min-h-8">
            @if ($product->harga)
                <p class="text-lg font-extrabold tracking-tight text-primary">Rp {{ number_format($product->harga, 0, ',', '.') }}</p>
            @else
                <p class="pt-1 text-xs font-medium italic text-on-surface-variant">Harga tidak dicantumkan</p>
            @endif
        </div>

        @if ($penjual || $dilihat)
            <div class="mt-2.5 flex items-center justify-between gap-2 border-t border-outline-variant pt-2 text-xs text-on-surface-variant">
                @if ($penjual)
                    <span class="inline-flex min-w-0 items-center gap-1" title="{{ $product->umkmProfile?->nama_usaha }}">
                        <x-umkm.icon-toko />
                        <span class="truncate">{{ $product->umkmProfile?->nama_usaha }}</span>
                    </span>
                @endif
                @if ($dilihat)
                    <span class="inline-flex shrink-0 items-center gap-1" title="Jumlah kali produk dilihat">
                        <x-heroicon-c-eye class="h-3.5 w-3.5 text-outline" />
                        {{ number_format($product->jumlah_dilihat ?? 0, 0, ',', '.') }}
                    </span>
                @endif
            </div>
        @endif

        {{ $slot }}
    </div>
</div>
