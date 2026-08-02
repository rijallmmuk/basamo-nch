@extends('public.layouts.app')

@section('title', $profile->nama_usaha)
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags($profile->deskripsi ?: 'Etalase '.$profile->nama_usaha.' di '.$nagari->nama_lengkap.'. Lihat produk dan hubungi penjual langsung.'), 155))
@section('canonical', \App\Support\PublicSeo::umkmProfile($profile))
@section('meta_image', $profile->sampulUrl())
@section('meta_image_alt', 'Sampul usaha '.$profile->nama_usaha)
@section('main-class', 'w-full')

@push('structured-data')
    @php
        $businessSchema = array_filter([
            '@context' => 'https://schema.org',
            '@type' => filled($profile->alamat) ? 'LocalBusiness' : 'Organization',
            'name' => $profile->nama_usaha,
            'description' => strip_tags((string) $profile->deskripsi) ?: null,
            'url' => \App\Support\PublicSeo::umkmProfile($profile),
            'logo' => $profile->logoUrl(),
            'image' => $profile->sampulUrl(),
            'telephone' => $profile->normalizedWhatsapp() ?: null,
            'address' => filled($profile->alamat) ? array_filter([
                '@type' => 'PostalAddress',
                'streetAddress' => $profile->alamat,
                'addressLocality' => $nagari->nama_lengkap,
                'addressRegion' => $nagari->provinsi,
                'addressCountry' => 'ID',
            ]) : null,
            'sameAs' => $profile->tautanLinks()->pluck('url')->values()->all() ?: null,
        ]);
    @endphp
    <x-public.structured-data :data="$businessSchema" />
@endpush

@section('content')
@php
    $links = $profile->tautanLinks();
    $isFallback = request()->routeIs('*.fallback');
    $global = $global ?? false;
@endphp
<article>
    {{-- ══ Sampul / banner etalase ══ --}}
    <div class="relative h-40 w-full overflow-hidden bg-gradient-to-br from-primary via-primary to-primary-container sm:h-56 lg:h-72">
        @if($sampul = $profile->sampulUrl())
            <img src="{{ $sampul }}" alt="Sampul {{ $profile->nama_usaha }}" class="h-full w-full object-cover">
        @else
            <div class="gonjong-bg absolute inset-0 opacity-10" aria-hidden="true"></div>
        @endif
    </div>

    <section class="bg-surface-container-lowest pb-12">
        <div class="mx-auto max-w-container-page px-margin-mobile lg:px-margin-page">
            <div class="lg:grid lg:grid-cols-[1fr_20rem] lg:gap-10">
                {{-- ── Kolom utama: identitas + tautan ── --}}
                <div>
                    <div class="-mt-10 flex items-end gap-4 sm:-mt-12">
                        @if($logo = $profile->logoUrl())
                            <img src="{{ $logo }}" alt="Logo {{ $profile->nama_usaha }}" class="h-24 w-24 shrink-0 rounded-2xl border-4 border-surface-container-lowest object-cover shadow-md sm:h-28 sm:w-28">
                        @else
                            <span class="flex h-24 w-24 shrink-0 items-center justify-center rounded-2xl border-4 border-surface-container-lowest bg-primary/10 text-primary shadow-md sm:h-28 sm:w-28">
                                <x-umkm.icon-toko class="h-12 w-12" />
                            </span>
                        @endif
                    </div>

                    <h1 class="mt-4 text-2xl font-extrabold tracking-tight text-primary sm:text-3xl">{{ $profile->nama_usaha }}</h1>

                    @if($profile->deskripsi)
                        <p class="mt-4 max-w-2xl whitespace-pre-line text-pretty leading-relaxed text-on-surface">{{ $profile->deskripsi }}</p>
                    @endif

                    {{-- Data opsional hanya dirender bila benar-benar tersedia. --}}
                    @php
                        $detailUsaha = collect([
                            ['heroicon-o-map-pin', 'Alamat', $profile->alamat],
                            ['heroicon-o-clock', 'Jam Operasional', $profile->jam_operasional],
                            ['heroicon-o-calendar', 'Tahun Berdiri', $profile->tahun_berdiri],
                            ['heroicon-o-map', 'Nagari', $nagari->nama_lengkap],
                        ])->filter(fn (array $detail): bool => filled($detail[2]));
                    @endphp
                    <dl class="mt-6 grid max-w-2xl gap-3 sm:grid-cols-2">
                        @foreach($detailUsaha as [$ikon, $label, $nilai])
                            <div class="flex items-start gap-3 rounded-2xl border border-outline-variant bg-background p-4">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary/8 text-primary">
                                    <x-dynamic-component :component="$ikon" class="h-4 w-4" />
                                </span>
                                <div class="min-w-0">
                                    <dt class="text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">{{ $label }}</dt>
                                    <dd class="mt-0.5 text-sm font-semibold text-on-surface">{{ $nilai }}</dd>
                                </div>
                            </div>
                        @endforeach
                    </dl>

                    @if($links->isNotEmpty())
                        <div class="mt-5">
                            <p class="mb-2 text-xs font-bold uppercase tracking-widest text-on-surface-variant">Temukan kami di</p>
                            <x-umkm.tautan-links :links="$links" />
                        </div>
                    @endif
                </div>

                {{-- ── Sidebar: kontak + QR ── --}}
                <aside class="mt-8 space-y-4 lg:mt-4">
                    <a href="{{ $profile->whatsappUrl('Halo, saya tertarik dengan usaha '.$profile->nama_usaha) }}" target="_blank" rel="noopener"
                       class="flex w-full items-center justify-center gap-2 rounded-full bg-success px-5 py-3.5 text-sm font-bold text-on-success shadow-lg shadow-success/20 transition-all hover:-translate-y-0.5">
                        <x-heroicon-s-chat-bubble-left-ellipsis class="h-5 w-5" /> Hubungi via WhatsApp
                    </a>

                    <p class="text-xs leading-relaxed text-on-surface-variant">
                        Transaksi dilakukan langsung dengan penjual melalui WhatsApp. Platform tidak memproses pembayaran.
                    </p>

                    @if($profile->email)
                        <a href="mailto:{{ $profile->email }}" class="flex w-full items-center justify-center gap-2 rounded-full border border-outline-variant px-5 py-3 text-sm font-bold text-on-surface transition-colors hover:border-primary hover:text-primary">
                            <x-heroicon-o-envelope class="h-5 w-5" /> {{ $profile->email }}
                        </a>
                    @endif

                    @if($qr = $profile->qrUrl())
                        <div class="rounded-2xl border border-outline-variant bg-background p-4 text-center">
                            <p class="mb-3 text-xs font-bold uppercase tracking-widest text-on-surface-variant">Pindai QR</p>
                            <img src="{{ $qr }}" alt="QR code {{ $profile->nama_usaha }}" class="mx-auto h-44 w-44 rounded-lg object-contain">
                        </div>
                    @endif
                </aside>
            </div>

            {{-- ══ Produk usaha ini ══ --}}
            <div class="mt-12 border-t border-outline-variant pt-8">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-widest text-secondary">Etalase Produk</span>
                        <h2 class="mt-1 text-section text-primary">
                            {{ $products->total() > 0 ? number_format($products->total(), 0, ',', '.').' produk tersedia' : 'Produk usaha ini' }}
                        </h2>
                    </div>
                </div>

                @if($products->isEmpty())
                    <x-public.empty-state
                        class="mt-6"
                        icon="heroicon-o-shopping-bag"
                        title="Belum ada produk"
                        description="Pemilik usaha belum menambahkan produk. Anda tetap dapat menghubunginya lewat WhatsApp." />
                @else
                    <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        @foreach($products as $product)
                            <a href="{{ $global ? route('public.produk', $product) : ($isFallback ? route('public.nagari.produk.fallback', [$nagari, $product]) : route('public.nagari.produk', [$nagari, $product])) }}" class="block h-full">
                                <x-umkm.product-card :product="$product" :penjual="false" />
                            </a>
                        @endforeach
                    </div>

                    <div class="mt-10">{{ $products->onEachSide(1)->links('public.pagination', ['label' => 'produk']) }}</div>
                @endif
            </div>
        </div>
    </section>
</article>
@endsection
