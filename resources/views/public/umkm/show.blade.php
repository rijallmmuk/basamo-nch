@extends('public.layouts.app')

@section('title', $product->nama_produk)
@section('meta_description', Str::limit($product->nama_produk.'. '.($product->deskripsi ?? 'Produk UMKM, hubungi penjual langsung via WhatsApp.'), 150))
@section('canonical', \App\Support\PublicSeo::umkmProduct($product))
@section('og_type', 'product')
@section('meta_image', $product->coverUrl())
@section('meta_image_alt', 'Foto produk '.$product->nama_produk)
@section('main-class', 'w-full')

@push('structured-data')
    @php
        $product->loadMissing('umkmProfile.nagari');
        $productSchema = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->nama_produk,
            'description' => strip_tags((string) $product->deskripsi) ?: null,
            'image' => $product->getMedia('photos')->map(fn ($media) => $media->getUrl('detail'))->values()->all() ?: [$product->coverUrl()],
            'category' => $product->category?->nama,
            'url' => \App\Support\PublicSeo::umkmProduct($product),
            'brand' => [
                '@type' => 'Brand',
                'name' => $product->umkmProfile->nama_usaha,
            ],
            'offers' => $product->harga ? [
                '@type' => 'Offer',
                'url' => \App\Support\PublicSeo::umkmProduct($product),
                'priceCurrency' => 'IDR',
                'price' => (string) $product->harga,
                'seller' => [
                    '@type' => 'Organization',
                    'name' => $product->umkmProfile->nama_usaha,
                ],
            ] : null,
        ]);
    @endphp
    <x-public.structured-data :data="$productSchema" />
@endpush

@section('content')
@php
    $profile = $product->umkmProfile;
    $photos = $product->getMedia('photos');
    $fotoUtama = $photos->first();
    $waText = 'Halo, saya tertarik dengan produk "'.$product->nama_produk.'" di katalog UMKM '.$profile->nama_usaha.'.';
    $isFallback = request()->routeIs('*.fallback');
    $global = $global ?? false;
    $etalaseUrl = $global
        ? route('public.umkm.etalase', $profile)
        : ($isFallback
            ? route('public.nagari.umkm.etalase.fallback', [$nagari, $profile])
            : route('public.nagari.umkm.etalase', [$nagari, $profile]));
@endphp

<section class="bg-surface-container-lowest py-8 lg:py-12">
    <div class="mx-auto max-w-container-page px-margin-mobile lg:px-margin-page">
        <div class="grid gap-8 lg:grid-cols-12 lg:gap-12">
            {{-- ── GALERI (kiri, sticky di desktop) ── --}}
            <div class="lg:col-span-5">
                <div class="lg:sticky lg:top-24">
                    <div class="overflow-hidden rounded-3xl border border-outline-variant bg-background shadow-sm">
                        <img id="foto-utama"
                             src="{{ $fotoUtama ? $fotoUtama->getUrl('detail') : $product->coverUrl() }}"
                             alt="{{ $product->nama_produk }}"
                             width="1200" height="1200" fetchpriority="high" decoding="async"
                             class="aspect-square w-full object-cover">
                    </div>

                    @if($photos->count() > 1)
                        {{-- Thumbnail: klik ganti foto utama (JS ringan; tanpa JS foto pertama tetap tampil) --}}
                        <div class="mt-3 flex gap-2 overflow-x-auto pb-1" role="list" aria-label="Foto produk lainnya">
                            @foreach($photos as $i => $media)
                                <button type="button" role="listitem"
                                    data-galeri-thumb data-full="{{ $media->getUrl('detail') }}"
                                    aria-label="Lihat foto {{ $i + 1 }}"
                                    class="{{ $i === 0 ? 'ring-2 ring-primary' : 'ring-1 ring-outline-variant' }} h-20 w-20 shrink-0 overflow-hidden rounded-xl transition-shadow hover:ring-2 hover:ring-primary/60">
                                    <img src="{{ $media->getUrl('card') }}" alt="Foto {{ $i + 1 }} {{ $product->nama_produk }}" loading="lazy" class="h-full w-full object-cover">
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- ── INFO PRODUK (kanan) ── --}}
            <div class="lg:col-span-7">
                <div class="flex flex-wrap items-center gap-2">
                    @if($product->category)
                        <span class="inline-flex items-center rounded-full bg-primary/5 px-3 py-1 text-xs font-semibold text-primary ring-1 ring-primary/10">{{ $product->category->nama }}</span>
                    @endif
                    <span class="inline-flex items-center gap-1 text-xs text-on-surface-variant">
                        <x-heroicon-m-eye class="h-3.5 w-3.5 text-outline" /> {{ number_format($product->jumlah_dilihat, 0, ',', '.') }}× dilihat
                    </span>
                </div>

                <h1 class="mt-3 text-2xl font-extrabold tracking-tight text-primary sm:text-3xl">{{ $product->nama_produk }}</h1>

                <div class="mt-4 rounded-2xl bg-background px-5 py-4 ring-1 ring-outline-variant">
                    <p class="text-xs font-semibold uppercase tracking-widest text-on-surface-variant">Harga</p>
                    <p class="mt-0.5 text-3xl font-black tracking-tight text-secondary">
                        {{ $product->harga ? 'Rp '.number_format($product->harga, 0, ',', '.') : 'Hubungi penjual' }}
                    </p>
                </div>

                {{-- CTA utama --}}
                <div class="mt-5 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ $profile->whatsappUrl($waText) }}" target="_blank" rel="noopener"
                        class="inline-flex flex-1 items-center justify-center gap-2 rounded-full bg-success px-6 py-3.5 font-bold text-on-success shadow-lg shadow-success/20 transition-all hover:-translate-y-0.5 sm:flex-none sm:px-10">
                        <x-heroicon-s-chat-bubble-left-ellipsis class="h-5 w-5" /> Hubungi via WhatsApp
                    </a>
                </div>
                <p class="mt-3 text-xs text-on-surface-variant">Transaksi dilakukan langsung dengan penjual melalui WhatsApp. Platform tidak memproses pembayaran.</p>

                @if($product->deskripsi)
                    <div class="mt-7 border-t border-outline-variant pt-6">
                        <h2 class="text-sm font-bold uppercase tracking-widest text-on-surface-variant">Deskripsi Produk</h2>
                        <p class="mt-3 whitespace-pre-line text-pretty leading-relaxed text-on-surface">{{ $product->deskripsi }}</p>
                    </div>
                @endif

                @php($produkLinks = $product->tautanLinks())
                @if($produkLinks->isNotEmpty())
                    <div class="mt-7 border-t border-outline-variant pt-6">
                        <h2 class="mb-3 text-sm font-bold uppercase tracking-widest text-on-surface-variant">Juga tersedia di</h2>
                        <x-umkm.tautan-links :links="$produkLinks" />
                    </div>
                @endif

                {{-- Kartu penjual → tautan ke etalase toko (rumah pemilik) --}}
                <a href="{{ $etalaseUrl }}"
                   class="group mt-7 flex flex-wrap items-center gap-4 rounded-2xl border border-outline-variant bg-background p-5 shadow-sm transition-colors hover:border-primary">
                    @if($logo = $profile->logoUrl())
                        <img src="{{ $logo }}" alt="Logo {{ $profile->nama_usaha }}" class="h-14 w-14 shrink-0 rounded-2xl object-cover">
                    @else
                        <span class="flex h-14 w-14 shrink-0 items-center justify-center rounded-2xl bg-primary text-xl font-extrabold text-secondary-container">
                            {{ Str::upper(Str::substr($profile->nama_usaha, 0, 1)) }}
                        </span>
                    @endif
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-bold text-on-surface group-hover:text-primary">{{ $profile->nama_usaha }}</p>
                        @if($profile->alamat)
                            <p class="mt-0.5 truncate text-sm text-on-surface-variant">{{ $profile->alamat }}</p>
                        @endif
                    </div>
                    <span class="inline-flex items-center gap-1 text-sm font-bold text-secondary transition-all group-hover:gap-2">
                        Kunjungi etalase <x-heroicon-o-arrow-right class="h-4 w-4" />
                    </span>
                </a>
            </div>
        </div>

        {{-- ── PRODUK TERKAIT ── --}}
        @if($terkait->isNotEmpty())
            <div class="mt-14 border-t border-outline-variant pt-10 lg:mt-20">
                <div class="mb-6 flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-widest text-secondary">Dari Usaha yang Sama</span>
                        <h2 class="mt-1 text-section text-primary">Produk lain {{ $profile->nama_usaha }}</h2>
                    </div>
                    <a href="{{ $etalaseUrl }}" class="inline-flex items-center gap-1 text-sm font-bold text-secondary hover:text-primary">
                        Kunjungi etalase <x-heroicon-o-arrow-right class="h-4 w-4" />
                    </a>
                </div>
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    @foreach($terkait as $produkLain)
                        <a href="{{ $global ? route('public.produk', $produkLain) : ($isFallback ? route('public.nagari.produk.fallback', [$nagari, $produkLain]) : route('public.nagari.produk', [$nagari, $produkLain])) }}" class="block h-full">
                            <x-umkm.product-card :product="$produkLain" />
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</section>
@endsection

@push('scripts')
<script>
    // Galeri produk: klik thumbnail → ganti foto utama (aman-degradasi: tanpa JS,
    // foto pertama tetap tampil dan semua thumbnail terlihat).
    document.addEventListener('DOMContentLoaded', () => {
        const utama = document.getElementById('foto-utama');
        const thumbs = document.querySelectorAll('[data-galeri-thumb]');
        if (!utama || thumbs.length === 0) return;

        thumbs.forEach((thumb) => {
            thumb.addEventListener('click', () => {
                utama.src = thumb.dataset.full;
                thumbs.forEach((t) => { t.classList.remove('ring-2', 'ring-primary'); t.classList.add('ring-1', 'ring-outline-variant'); });
                thumb.classList.remove('ring-1', 'ring-outline-variant');
                thumb.classList.add('ring-2', 'ring-primary');
            });
        });
    });
</script>
@endpush
