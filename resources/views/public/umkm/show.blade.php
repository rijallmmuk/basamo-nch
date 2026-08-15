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
            'image' => $product->getMedia('photos')->map(fn ($media) => $product->detailPhotoUrl($media))->values()->all() ?: [$product->coverUrl()],
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
        <nav aria-label="Breadcrumb" class="mb-6 flex min-w-0 items-center gap-2 text-sm text-on-surface-variant">
            <a href="{{ $etalaseUrl }}" class="inline-flex min-w-0 items-center gap-2 font-semibold text-primary hover:underline">
                <x-heroicon-o-arrow-left class="h-4 w-4 shrink-0" />
                <span class="truncate">{{ $profile->nama_usaha }}</span>
            </a>
            <span aria-hidden="true">/</span>
            <span class="truncate" aria-current="page">{{ $product->nama_produk }}</span>
        </nav>

        <div class="grid gap-8 lg:grid-cols-12 lg:gap-12">
            {{-- ── GALERI (kiri, sticky di desktop) ── --}}
            <div class="lg:col-span-5">
                <div class="lg:sticky lg:top-24">
                    <div class="overflow-hidden rounded-3xl border border-outline-variant bg-background shadow-sm">
                        <img id="foto-utama"
                             src="{{ $fotoUtama ? $product->detailPhotoUrl($fotoUtama) : $product->coverUrl() }}"
                             alt="{{ $product->nama_produk }}"
                             width="1200" height="1200" fetchpriority="high" decoding="async"
                             class="aspect-square w-full object-cover">
                    </div>

                    @if($photos->count() > 1)
                        {{-- Thumbnail: klik ganti foto utama (JS ringan; tanpa JS foto pertama tetap tampil) --}}
                        <div class="mt-3 flex gap-2 overflow-x-auto pb-1" role="list" aria-label="Foto produk lainnya">
                            @foreach($photos as $i => $media)
                                <button type="button" role="listitem"
                                    data-galeri-thumb data-full="{{ $product->detailPhotoUrl($media) }}"
                                    data-alt="Foto {{ $i + 1 }} {{ $product->nama_produk }}"
                                    aria-label="Lihat foto {{ $i + 1 }}"
                                    @if($i === 0) aria-current="true" @endif
                                    class="{{ $i === 0 ? 'ring-2 ring-primary' : 'ring-1 ring-outline-variant' }} h-16 w-16 shrink-0 overflow-hidden rounded-xl transition-shadow hover:ring-2 hover:ring-primary/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary sm:h-20 sm:w-20">
                                    <img src="{{ $product->thumbnailPhotoUrl($media) }}" alt="Foto {{ $i + 1 }} {{ $product->nama_produk }}" loading="lazy" class="h-full w-full object-cover">
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- ── INFO PRODUK (kanan) ── --}}
            <div class="lg:col-span-7">
                <div class="flex flex-wrap items-center gap-2 text-sm text-on-surface-variant">
                    @if($product->category)
                        <span class="font-semibold text-primary">{{ $product->category->nama }}</span>
                    @endif
                </div>

                <h1 class="mt-2 text-3xl font-extrabold tracking-tight text-primary sm:text-4xl">{{ $product->nama_produk }}</h1>

                <div class="mt-5 border-y border-outline-variant py-5">
                    <p class="text-sm font-semibold text-on-surface-variant">Harga</p>
                    <p class="mt-1 text-3xl font-black tracking-tight text-primary">
                        {{ $product->harga ? 'Rp '.number_format($product->harga, 0, ',', '.') : 'Hubungi penjual' }}
                    </p>
                </div>

                {{-- CTA utama --}}
                <div class="mt-5 flex flex-col gap-3 sm:flex-row">
                    <a href="{{ $profile->whatsappUrl($waText) }}" target="_blank" rel="noopener"
                        class="inline-flex min-h-12 flex-1 items-center justify-center gap-2 rounded-xl bg-success px-6 py-3.5 font-bold text-on-success shadow-sm transition hover:bg-success/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-success focus-visible:ring-offset-2 sm:flex-none sm:px-10">
                        <x-heroicon-s-chat-bubble-left-ellipsis class="h-5 w-5" /> Hubungi via WhatsApp
                    </a>
                </div>
                <p class="mt-3 text-xs text-on-surface-variant">Transaksi dilakukan langsung dengan penjual melalui WhatsApp. Platform tidak memproses pembayaran.</p>

                @if($product->deskripsi)
                    <div class="mt-7 border-t border-outline-variant pt-6">
                        <h2 class="text-lg font-extrabold text-on-surface">Deskripsi produk</h2>
                        <x-public.teks-lipat :baris="6" class="mt-3">
                            <p class="whitespace-pre-line text-pretty leading-relaxed text-on-surface">{{ $product->deskripsi }}</p>
                        </x-public.teks-lipat>
                    </div>
                @endif

                @php($produkLinks = $product->tautanLinks())
                @if($produkLinks->isNotEmpty())
                    <div class="mt-7 border-t border-outline-variant pt-6">
                        <h2 class="mb-3 text-lg font-extrabold text-on-surface">Tersedia juga di</h2>
                        <x-umkm.tautan-links :links="$produkLinks" />
                    </div>
                @endif

                {{-- Kartu penjual → tautan ke etalase lapau usaha. --}}
                <a href="{{ $etalaseUrl }}"
                   class="group mt-7 flex flex-wrap items-center gap-4 rounded-2xl border border-outline-variant bg-background p-5 shadow-sm transition hover:border-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                    <img src="{{ $profile->logoUrl() }}" alt="Logo {{ $profile->nama_usaha }}" class="h-14 w-14 shrink-0 rounded-xl bg-white object-cover">
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
                        <span class="text-xs font-bold uppercase tracking-widest text-secondary">Dari lapau usaha yang sama</span>
                        <h2 class="mt-1 text-section text-primary">Produk lain {{ $profile->nama_usaha }}</h2>
                    </div>
                    <a href="{{ $etalaseUrl }}" class="inline-flex items-center gap-1 text-sm font-bold text-secondary hover:text-primary">
                        Kunjungi etalase <x-heroicon-o-arrow-right class="h-4 w-4" />
                    </a>
                </div>
                <div class="grid grid-cols-2 gap-3 sm:gap-5 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">
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
                utama.alt = thumb.dataset.alt;
                thumbs.forEach((t) => {
                    t.classList.remove('ring-2', 'ring-primary');
                    t.classList.add('ring-1', 'ring-outline-variant');
                    t.removeAttribute('aria-current');
                });
                thumb.classList.remove('ring-1', 'ring-outline-variant');
                thumb.classList.add('ring-2', 'ring-primary');
                thumb.setAttribute('aria-current', 'true');
            });
        });
    });
</script>
@endpush
