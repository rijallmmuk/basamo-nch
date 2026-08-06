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
    $directoryUrl = $global
        ? route('public.umkm', ['nagari' => $nagari->getKey()])
        : ($isFallback
            ? route('public.nagari.umkm.fallback', $nagari)
            : route('public.nagari.umkm', $nagari));
    $etalaseUrl = $global
        ? route('public.umkm.etalase', $profile)
        : ($isFallback
            ? route('public.nagari.umkm.etalase.fallback', [$nagari, $profile])
            : route('public.nagari.umkm.etalase', [$nagari, $profile]));
    $hasProductFilters = collect($productFilters)->filter()->isNotEmpty();
@endphp
<article>
    {{-- ══ Sampul / banner etalase ══ --}}
    <div class="relative h-44 w-full overflow-hidden bg-surface-container-high sm:h-56 lg:h-72">
        <img src="{{ $profile->sampulUrl() }}" alt="Sampul {{ $profile->nama_usaha }}" class="h-full w-full object-cover" fetchpriority="high">
        <div class="absolute inset-x-0 bottom-0 h-24 bg-gradient-to-t from-black/35 to-transparent" aria-hidden="true"></div>
        <div class="absolute inset-x-0 top-0 mx-auto max-w-container-page px-margin-mobile pt-5 lg:px-margin-page">
            <a href="{{ $directoryUrl }}" class="inline-flex items-center gap-2 rounded-full bg-black/45 px-4 py-2 text-xs font-bold text-white backdrop-blur-sm transition hover:bg-black/60 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white">
                <x-heroicon-o-arrow-left class="h-4 w-4" /> Kembali ke Lapau Nagari
            </a>
        </div>
    </div>

    <section class="bg-surface-container-lowest pb-12">
        <div class="mx-auto max-w-container-page px-margin-mobile lg:px-margin-page">
            <div class="lg:grid lg:grid-cols-[1fr_20rem] lg:gap-10">
                {{-- ── Kolom utama: identitas + tautan ── --}}
                <div>
                    <div class="-mt-10 flex items-end gap-4 sm:-mt-12">
                        <img src="{{ $profile->logoUrl() }}" alt="Logo {{ $profile->nama_usaha }}" class="h-24 w-24 shrink-0 rounded-2xl border-4 border-surface-container-lowest bg-white object-cover shadow-md sm:h-28 sm:w-28">
                    </div>

                    <h1 class="mt-4 text-2xl font-extrabold tracking-tight text-primary sm:text-3xl">{{ $profile->nama_usaha }}</h1>

                    @if($profile->deskripsi)
                        <div class="mt-4 max-w-2xl" id="umkm-desc-container">
                            <p class="whitespace-pre-line text-pretty leading-relaxed text-on-surface line-clamp-4 transition-all duration-300" id="umkm-desc-content">
                                {{ $profile->deskripsi }}
                            </p>
                            <button type="button"
                                    id="umkm-desc-toggle"
                                    class="hidden mt-2 text-sm font-bold text-primary hover:underline focus:outline-none inline-flex items-center gap-1">
                                <span class="toggle-text">Baca selengkapnya</span>
                                <x-heroicon-s-chevron-down class="h-4 w-4 transition-transform duration-300 toggle-icon" />
                            </button>
                        </div>
                        <script>
                            document.addEventListener('DOMContentLoaded', () => {
                                const content = document.getElementById('umkm-desc-content');
                                const toggle = document.getElementById('umkm-desc-toggle');
                                const textNode = toggle?.querySelector('.toggle-text');
                                const iconNode = toggle?.querySelector('.toggle-icon');

                                if (content && toggle) {
                                    if (content.scrollHeight > content.clientHeight) {
                                        toggle.classList.remove('hidden');

                                        toggle.addEventListener('click', () => {
                                            const isExpanded = !content.classList.contains('line-clamp-4');

                                            if (isExpanded) {
                                                content.classList.add('line-clamp-4');
                                                textNode.textContent = 'Baca selengkapnya';
                                                iconNode.classList.remove('rotate-180');
                                            } else {
                                                content.classList.remove('line-clamp-4');
                                                textNode.textContent = 'Tampilkan lebih sedikit';
                                                iconNode.classList.add('rotate-180');
                                            }
                                        });
                                    }
                                }
                            });
                        </script>
                    @endif

                    {{-- Data usaha hanya dirender bila benar-benar tersedia. --}}
                    @php
                        $detailUsaha = collect([
                            ['heroicon-o-map-pin', 'Alamat', $profile->alamat],
                            ['heroicon-o-map', 'Nagari', $nagari->nama_lengkap],
                            ['heroicon-o-clock', 'Jam operasional', $profile->jam_operasional],
                            ['heroicon-o-calendar-days', 'Berdiri sejak', $profile->tahun_berdiri ? (string) $profile->tahun_berdiri : null],
                        ])->filter(fn (array $detail): bool => filled($detail[2]));
                    @endphp
                    <dl class="mt-6 grid max-w-3xl gap-x-6 gap-y-4 sm:grid-cols-2">
                        @foreach($detailUsaha as [$ikon, $label, $nilai])
                            <div class="flex items-start gap-3">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary/8 text-primary">
                                    <x-dynamic-component :component="$ikon" class="h-4 w-4" />
                                </span>
                                <div class="min-w-0">
                                    <dt class="text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">{{ $label }}</dt>
                                    <dd class="mt-0.5 text-sm font-semibold text-on-surface">{{ $nilai }}</dd>
                                </div>
                            </div>
                        @endforeach
                    </dl>

                    @if($profile->email || $links->isNotEmpty())
                        <div class="mt-5">
                            <p class="mb-2 text-xs font-bold uppercase tracking-widest text-on-surface-variant">Kontak dan tautan</p>
                            <div class="flex flex-wrap gap-2">
                                @if($profile->email)
                                    <a href="mailto:{{ $profile->email }}" class="inline-flex min-h-10 items-center gap-2 rounded-lg border border-outline-variant bg-white px-3 py-2 text-xs font-semibold text-on-surface transition hover:border-primary/45 hover:bg-primary/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                                        <x-heroicon-o-envelope class="h-4 w-4 text-primary" /> {{ $profile->email }}
                                    </a>
                                @endif
                                <x-umkm.tautan-links :links="$links" />
                            </div>
                        </div>
                    @endif
                </div>

                {{-- ── Sidebar: kontak + QR ── --}}
                <aside class="mt-8 space-y-4 lg:mt-4">
                    <a href="{{ $profile->whatsappUrl('Halo, saya tertarik dengan usaha '.$profile->nama_usaha) }}" target="_blank" rel="noopener"
                       class="flex min-h-12 w-full items-center justify-center gap-2 rounded-xl bg-success px-5 py-3.5 text-sm font-bold text-on-success shadow-sm transition hover:bg-success/90 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-success focus-visible:ring-offset-2">
                        <x-heroicon-s-chat-bubble-left-ellipsis class="h-5 w-5" /> Hubungi via WhatsApp
                    </a>

                    <p class="text-xs leading-relaxed text-on-surface-variant">
                        Transaksi dilakukan langsung dengan penjual melalui WhatsApp. Platform tidak memproses pembayaran.
                    </p>



                    @if($qr = $profile->qrUrl())
                        <div class="rounded-2xl border border-outline-variant bg-background p-4 text-center">
                            <p class="mb-3 text-sm font-bold text-on-surface">QR lapau usaha</p>
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

                @if($products->total() > 0 || $hasProductFilters)
                    <form method="GET" action="{{ $etalaseUrl }}" class="mt-6 grid gap-3 rounded-2xl border border-outline-variant bg-background p-4 lg:grid-cols-[minmax(16rem,1fr)_18rem_auto] lg:items-end">
                        <label class="block min-w-0">
                            <span class="mb-2 block text-sm font-bold text-on-surface">Cari produk</span>
                            <span class="relative block">
                                <x-heroicon-o-magnifying-glass class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-on-surface-variant" />
                                <input type="search" name="q" value="{{ $productFilters['q'] }}" placeholder="Cari nama produk" autocomplete="off"
                                       class="min-h-12 w-full rounded-xl border border-control-border bg-white py-3 pl-11 pr-4 text-sm text-on-surface shadow-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20">
                            </span>
                        </label>

                        <label class="block">
                            <span class="mb-2 block text-sm font-bold text-on-surface">Kategori</span>
                            <select name="kategori" class="min-h-12 w-full rounded-xl border border-control-border bg-white py-3 pl-4 pr-10 text-sm text-on-surface shadow-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20">
                                <option value="">Semua kategori</option>
                                @foreach($categoryOptions as $category)
                                    <option value="{{ $category->id }}" @selected($productFilters['category'] === (string) $category->id)>{{ $category->nama }}</option>
                                @endforeach
                            </select>
                        </label>

                        <div class="flex gap-2">
                            <button type="submit" class="min-h-12 flex-1 rounded-xl bg-primary px-6 text-sm font-bold text-on-primary transition hover:bg-primary-highlight focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 lg:flex-none">Tampilkan</button>
                            @if($hasProductFilters)
                                <a href="{{ $etalaseUrl }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl border border-control-border bg-white px-4 text-sm font-bold text-primary transition hover:border-primary hover:bg-primary/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                                    <x-heroicon-o-x-mark class="h-4 w-4" /> <span class="hidden sm:inline">Hapus filter</span>
                                </a>
                            @endif
                        </div>
                    </form>
                @endif

                @if($products->isEmpty())
                    <x-public.empty-state
                        class="mt-6"
                        icon="heroicon-o-shopping-bag"
                        :title="$hasProductFilters ? 'Produk tidak ditemukan' : 'Belum ada produk'"
                        :description="$hasProductFilters ? 'Coba kata kunci atau kategori lain, atau hapus filter yang aktif.' : 'Pemilik usaha belum menambahkan produk. Anda tetap dapat menghubunginya lewat WhatsApp.'" />
                @else
                    <div class="mt-6 grid grid-cols-2 gap-3 sm:gap-5 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">
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
