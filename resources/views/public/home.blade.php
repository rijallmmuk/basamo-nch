@extends('public.layouts.app')

@section('title', 'Beranda')

{{-- Full-bleed: section punya latar & container sendiri --}}
@section('main-class', '')

@section('content')
    {{-- ── HERO ──────────────────────────────────────────────────── --}}
    <section class="gonjong-bg relative overflow-hidden pt-20 pb-28">
        <div class="absolute right-0 top-0 z-0 h-full w-1/3 -skew-x-12 translate-x-20 bg-primary/5"></div>
        <div class="absolute -bottom-20 -left-20 z-0 h-64 w-64 rounded-full bg-secondary-container/20 blur-3xl"></div>

        <div class="relative z-10 mx-auto grid max-w-7xl grid-cols-1 items-center gap-16 px-margin-mobile lg:grid-cols-12 lg:px-margin-desktop">
            <div class="space-y-8 lg:col-span-7">
                <div class="inline-flex items-center gap-2 rounded-full border border-secondary/30 bg-secondary-container/20 px-5 py-2 text-label-md font-bold uppercase tracking-widest text-secondary">
                    <x-heroicon-s-check-badge class="h-4 w-4" />
                    Smart Nagari Sumatera Barat
                </div>
                <h1 class="text-[40px] font-extrabold leading-[1.1] tracking-tight text-primary sm:text-[56px]">
                    Ekosistem digital yang berakar di nagari, tumbuh ke seluruh negeri.
                </h1>
                <p class="max-w-2xl text-lg font-light leading-relaxed tracking-wide text-on-surface-variant sm:text-xl">
                    Satu platform untuk belajar, berdaya, dan berjualan — menyatukan kearifan lokal
                    Minangkabau dengan teknologi yang dirancang untuk masyarakat nagari.
                </p>
                <div class="flex flex-wrap gap-4 pt-2">
                    <a href="{{ route('public.umkm.index') }}"
                        class="flex items-center gap-3 rounded-full bg-primary px-8 py-4 font-bold text-on-primary shadow-2xl shadow-primary/30 transition-all hover:scale-105">
                        Jelajahi Katalog UMKM <x-heroicon-s-shopping-bag class="h-5 w-5" />
                    </a>
                    <a href="#pilar"
                        class="rounded-full border-2 border-primary px-8 py-4 font-bold text-primary transition-all hover:bg-primary hover:text-on-primary">
                        4 Pilar NCH
                    </a>
                </div>
            </div>

            {{-- Panel dekoratif + overlay statistik (modul = data nyata) --}}
            <div class="relative lg:col-span-5">
                <div class="tech-glow relative z-10 aspect-[4/5] rotate-2 overflow-hidden rounded-3xl border-4 border-surface-container-lowest bg-gradient-to-br from-primary via-primary-container to-primary transition-transform duration-700 hover:rotate-0">
                    <div class="songket-pattern absolute inset-0 opacity-20"></div>
                    <div class="absolute inset-0 flex flex-col items-center justify-center gap-4 text-secondary-container">
                        <x-heroicon-o-building-library class="h-24 w-24" />
                        <span class="text-label-md font-bold uppercase tracking-widest">Rumah Gadang Digital</span>
                    </div>
                </div>
                <div class="glass-card absolute -bottom-8 -left-6 z-20 rounded-3xl border-b-4 border-b-secondary-container p-6 shadow-2xl">
                    <div class="flex items-center gap-4">
                        <div class="flex h-14 w-14 items-center justify-center bg-primary text-secondary-container gonjong-peak">
                            <x-heroicon-s-academic-cap class="h-7 w-7" />
                        </div>
                        <div>
                            <div class="text-[36px] font-extrabold leading-none text-primary">{{ number_format($stats['modul']) }}</div>
                            <div class="mt-1 text-label-sm font-bold uppercase tracking-widest text-on-surface-variant">Modul belajar siap diakses</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="minang-divider bg-surface-container-lowest"></div>

    {{-- ── 4 PILAR ───────────────────────────────────────────────── --}}
    <section id="pilar" class="relative overflow-hidden bg-surface py-section-gap">
        <div class="absolute right-0 top-0 h-64 w-full -translate-y-32 bg-primary/5 gonjong-peak"></div>
        <div class="relative z-10 mx-auto max-w-7xl px-margin-mobile lg:px-margin-desktop">
            <div class="mb-16 max-w-2xl space-y-3">
                <span class="text-label-md font-bold uppercase tracking-widest text-secondary">4 Pilar NCH</span>
                <h2 class="text-[32px] font-extrabold tracking-tight text-primary sm:text-[42px]">Empat modul, satu ekosistem nagari.</h2>
                <p class="text-lg tracking-wide text-on-surface-variant">LMS, SDGs Desa, UMKM, dan Sensor IoT dirancang terintegrasi membentuk Smart Learning Center Nagari.</p>
                <div class="h-1 w-32 rounded-full bg-secondary-container"></div>
            </div>

            <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-4">
                @php
                    // Class literal penuh (Tailwind v4 tak generate class rakitan-string).
                    $pilar = [
                        ['icon' => 'academic-cap', 'wrap' => 'bg-primary/10 text-primary', 'peak' => 'bg-primary/10', 'cta' => 'text-primary', 'judul' => 'LMS Hub', 'desc' => 'Pusat pembelajaran digital nagari — modul, kuis interaktif, dan papan peringkat warga.', 'label' => 'MULAI BELAJAR', 'href' => route('login')],
                        ['icon' => 'building-storefront', 'wrap' => 'bg-secondary/10 text-secondary', 'peak' => 'bg-secondary/10', 'cta' => 'text-secondary', 'judul' => 'Katalog UMKM', 'desc' => 'Repositori UMKM nagari — katalog produk dan kontak langsung penjual via WhatsApp.', 'label' => 'LIHAT KATALOG', 'href' => route('public.umkm.index')],
                        ['icon' => 'chart-bar', 'wrap' => 'bg-tertiary/10 text-tertiary', 'peak' => 'bg-tertiary/10', 'judul' => 'SDGs Desa', 'desc' => 'Pencatatan dan visualisasi capaian pembangunan berkelanjutan tingkat nagari.', 'href' => null],
                        ['icon' => 'cpu-chip', 'wrap' => 'bg-error/10 text-error', 'peak' => 'bg-error/10', 'judul' => 'Sensor IoT', 'desc' => 'Pemantauan sensor desa — suhu, kelembaban tanah, curah hujan, dan kualitas udara.', 'href' => null],
                    ];
                @endphp
                @foreach ($pilar as $p)
                    <div class="songket-pattern card-hover group relative overflow-hidden rounded-2xl border border-outline-variant/50 bg-surface-container-lowest p-8 card-shadow">
                        <div class="pucuk-rabuang absolute -bottom-4 -right-4 h-24 w-24 {{ $p['peak'] }}"></div>
                        <div class="mb-6 flex h-16 w-16 items-center justify-center rounded-2xl {{ $p['wrap'] }} transition-transform group-hover:scale-110">
                            <x-dynamic-component :component="'heroicon-o-'.$p['icon']" class="h-8 w-8" />
                        </div>
                        <h3 class="mb-3 text-headline-md font-extrabold text-primary">{{ $p['judul'] }}</h3>
                        <p class="mb-6 leading-relaxed text-on-surface-variant">{{ $p['desc'] }}</p>
                        @if ($p['href'])
                            <a href="{{ $p['href'] }}" class="inline-flex items-center gap-2 font-bold tracking-wide {{ $p['cta'] }} transition-all group-hover:gap-4">
                                {{ $p['label'] }} <x-heroicon-o-arrow-right class="h-5 w-5" />
                            </a>
                        @else
                            <span class="inline-flex items-center gap-2 rounded-full bg-surface-container px-3 py-1 text-label-sm font-bold uppercase tracking-widest text-on-surface-variant">
                                <x-heroicon-o-clock class="h-4 w-4" /> Segera hadir
                            </span>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ── STATISTIK ─────────────────────────────────────────────── --}}
    <section class="relative overflow-hidden bg-primary py-16">
        <div class="gonjong-bg absolute inset-0 scale-150 opacity-5"></div>
        <div class="relative z-10 mx-auto max-w-7xl px-margin-mobile lg:px-margin-desktop">
            <div class="grid grid-cols-1 gap-10 text-center md:grid-cols-3">
                @foreach ([['v' => number_format($stats['desa']), 'l' => 'Nagari Aktif'], ['v' => number_format($stats['produk']), 'l' => 'Produk UMKM'], ['v' => number_format($stats['modul']), 'l' => 'Modul Belajar']] as $s)
                    <div class="space-y-2">
                        <div class="text-[48px] font-extrabold tracking-tight text-secondary-container">{{ $s['v'] }}</div>
                        <div class="text-label-sm font-bold uppercase tracking-widest text-on-primary/60">{{ $s['l'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- ── PRODUK UNGGULAN ───────────────────────────────────────── --}}
    @if ($produkUnggulan->isNotEmpty())
        <section class="relative overflow-hidden bg-surface-container-lowest py-section-gap">
            <div class="absolute -top-10 left-10 h-40 w-40 border-l-4 border-t-4 border-secondary/20"></div>
            <div class="mx-auto max-w-7xl px-margin-mobile lg:px-margin-desktop">
                <div class="mb-12 flex flex-col items-start justify-between gap-6 md:flex-row md:items-end">
                    <div class="space-y-2">
                        <span class="text-label-md font-bold uppercase tracking-widest text-secondary">Katalog UMKM</span>
                        <h2 class="text-[32px] font-extrabold tracking-tight text-primary sm:text-[42px]">Produk unggulan nagari.</h2>
                    </div>
                    <a href="{{ route('public.umkm.index') }}" class="flex items-center gap-3 text-label-md font-extrabold uppercase tracking-wider text-primary transition-all hover:gap-5">
                        Lihat semua produk <x-heroicon-o-arrow-long-right class="h-5 w-5" />
                    </a>
                </div>
                <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-4">
                    @foreach ($produkUnggulan as $produk)
                        <div class="group overflow-hidden rounded-3xl border border-outline-variant/50 bg-surface card-shadow transition-all hover:shadow-2xl">
                            <a href="{{ route('public.umkm.show', $produk) }}" class="relative block h-56 overflow-hidden">
                                <img src="{{ $produk->coverUrl() }}" alt="{{ $produk->nama_produk }}"
                                    class="h-full w-full object-cover transition-transform duration-700 group-hover:scale-105">
                                @if ($produk->umkmProfile?->desa)
                                    <div class="absolute left-4 top-4 rounded-full bg-surface-container-lowest/90 px-4 py-1.5 text-label-sm font-bold uppercase tracking-widest text-primary backdrop-blur-md">{{ $produk->umkmProfile->desa->nama_lengkap }}</div>
                                @endif
                            </a>
                            <div class="p-5">
                                <h4 class="mb-2 text-headline-sm font-extrabold text-primary">
                                    <a href="{{ route('public.umkm.show', $produk) }}" class="hover:underline">{{ $produk->nama_produk }}</a>
                                </h4>
                                <p class="mb-4 line-clamp-2 text-body-md leading-relaxed text-on-surface-variant">{{ $produk->deskripsi }}</p>
                                <div class="flex flex-col gap-3">
                                    <span class="text-headline-sm font-extrabold text-secondary">{{ $produk->harga ? 'Rp '.number_format($produk->harga, 0, ',', '.') : 'Hubungi penjual' }}</span>
                                    @if ($produk->umkmProfile?->whatsapp)
                                        <a href="{{ $produk->umkmProfile->whatsappUrl('Halo, saya tertarik dengan produk '.$produk->nama_produk) }}" target="_blank" rel="noopener"
                                            class="flex w-full items-center justify-center gap-2 rounded-full bg-tertiary px-4 py-2.5 text-body-md font-bold text-on-tertiary transition-all hover:shadow-lg">
                                            <x-heroicon-s-chat-bubble-left-right class="h-4 w-4" /> WhatsApp
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    {{-- ── AJAKAN PETA ───────────────────────────────────────────── --}}
    <section class="relative overflow-hidden bg-surface py-section-gap">
        <div class="mx-auto grid max-w-7xl grid-cols-1 items-center gap-16 px-margin-mobile lg:grid-cols-12 lg:px-margin-desktop">
            <div class="space-y-6 lg:col-span-6">
                <span class="text-label-md font-bold uppercase tracking-widest text-secondary">Peta Nagari</span>
                <h2 class="text-[32px] font-extrabold tracking-tight text-primary sm:text-[42px]">Jelajahi sebaran nagari mitra.</h2>
                <p class="text-lg leading-relaxed tracking-wide text-on-surface-variant">Lihat profil dan lokasi nagari di Sumatera Barat lewat peta interaktif — lengkap dengan wilayah dan titik UMKM.</p>
                <a href="{{ route('public.peta') }}"
                    class="inline-flex items-center gap-3 rounded-full bg-primary px-8 py-4 font-bold text-on-primary transition-all hover:scale-105">
                    <x-heroicon-s-map class="h-5 w-5" /> Buka Peta Interaktif
                </a>
            </div>
            <div class="relative lg:col-span-6">
                <div class="tech-glow relative flex aspect-[16/10] items-center justify-center overflow-hidden rounded-3xl border border-outline-variant/50 bg-gradient-to-br from-primary to-primary-container">
                    <div class="songket-pattern absolute inset-0 opacity-20"></div>
                    <x-heroicon-o-map class="relative h-24 w-24 text-secondary-container/60" />
                </div>
            </div>
        </div>
    </section>

    {{-- ── CTA ───────────────────────────────────────────────────── --}}
    <section class="relative overflow-hidden border-t border-outline-variant/50 bg-surface-container-lowest py-24">
        <div class="gonjong-bg absolute inset-0 opacity-20"></div>
        <div class="relative z-10 mx-auto max-w-7xl px-margin-mobile text-center lg:px-margin-desktop">
            <div class="group relative overflow-hidden rounded-[3rem] bg-primary p-12 md:p-20">
                <div class="absolute right-0 top-0 h-64 w-64 rounded-full bg-secondary-container/10 blur-3xl transition-transform duration-1000 group-hover:scale-150"></div>
                <div class="relative z-10 space-y-8">
                    <span class="text-label-md font-bold uppercase tracking-widest text-secondary-container">Mulai Sekarang</span>
                    <h2 class="text-[32px] font-extrabold leading-tight tracking-tight text-on-primary sm:text-[48px]">Jadi bagian dari ekosistem nagari.</h2>
                    <p class="mx-auto max-w-2xl text-lg font-light leading-relaxed text-on-primary/70 sm:text-xl">Masuk ke portal warga untuk belajar di LMS, dan kelola produk UMKM-mu agar terhubung langsung dengan pembeli.</p>
                    <div class="flex flex-wrap justify-center gap-4 pt-2">
                        <a href="{{ route('login') }}"
                            class="flex items-center gap-3 rounded-full bg-secondary-container px-10 py-5 text-label-md font-extrabold uppercase tracking-widest text-primary shadow-2xl transition-all hover:scale-105 active:scale-95">
                            Masuk Portal Warga <x-heroicon-s-arrow-right class="h-5 w-5" />
                        </a>
                        <a href="{{ route('public.umkm.index') }}"
                            class="rounded-full border-2 border-on-primary/20 px-10 py-5 text-label-md font-extrabold uppercase tracking-widest text-on-primary shadow-2xl transition-all hover:bg-on-primary/10 active:scale-95">
                            Lihat Katalog
                        </a>
                    </div>
                </div>
                <div class="absolute bottom-0 left-1/2 h-12 w-48 -translate-x-1/2 bg-secondary-container opacity-20 gonjong-peak"></div>
            </div>
        </div>
    </section>
@endsection
