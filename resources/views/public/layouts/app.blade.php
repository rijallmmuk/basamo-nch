<!DOCTYPE html>
<html lang="id" class="h-full scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @php
        /* Situs nagari punya identitasnya sendiri; tanpa ini judul tab tiap nagari
           sama persis sehingga pengunjung tak tahu situs siapa yang dibukanya. */
        $situsNagari = request()->route('nagari');
        $situsNagari = $situsNagari instanceof \App\Models\Nagari ? $situsNagari : null;
        $namaSitus = $situsNagari
            ? $situsNagari->nama_lengkap.' · Basamo NCH'
            : 'Basamo NCH';
        $deskripsiSitus = $situsNagari
            ? 'Wajah digital '.$situsNagari->nama_lengkap.': data nagari, pelatihan warga, dan etalase UMKM dalam satu tempat.'
            : 'BASAMO Nagari Creative Hub menghubungkan Teras Nagari, Medan Nan Balinduang, Medan Nan Bapaneh, dan Lapau Nagari dalam satu ekosistem digital.';

        /* `@section('nama', 'isi')` menyimpan isinya lewat e(), jadi keluaran
           yieldContent() sudah ter-escape sekali. Dikembalikan ke teks mentah di sini
           supaya {{ }} menjadi satu-satunya lapis escape. */
        $seksi = fn (string $nama, string $bawaan = ''): string => trim(
            html_entity_decode($__env->yieldContent($nama, $bawaan), ENT_QUOTES, 'UTF-8')
        );

        $judulHalaman = $seksi('title', 'Beranda');
        $judulLengkap = $judulHalaman.' · '.$namaSitus;
        $metaDescription = $seksi('meta_description', $deskripsiSitus);
        $canonicalUrl = $seksi('canonical') ?: \App\Support\PublicSeo::canonical(request());
        $robotsContent = $seksi('robots') ?: \App\Support\PublicSeo::robots(request());
        $metaImage = $seksi('meta_image');
        $metaImage = $metaImage !== ''
            ? $metaImage
            : ($situsNagari?->sampulUrls()->first() ?: asset('images/brand/basamo-nch-mark.png'));
        $metaImage = str_starts_with($metaImage, 'http') ? $metaImage : url($metaImage);

        $situsHome = $situsNagari
            ? route('public.nagari.home', $situsNagari)
            : rtrim((string) config('app.url'), '/');
        $indukHome = rtrim((string) config('app.url'), '/');
        $indukEntityId = $indukHome.'/#organization';
        $publisher = $situsNagari
            ? array_filter([
                '@type' => 'GovernmentOrganization',
                '@id' => $situsHome.'#organization',
                'name' => $situsNagari->nama_lengkap,
                'url' => $situsHome,
                'address' => array_filter([
                    '@type' => 'PostalAddress',
                    'addressLocality' => $situsNagari->kecamatan,
                    'addressRegion' => $situsNagari->provinsi,
                    'addressCountry' => 'ID',
                ]),
                'parentOrganization' => ['@id' => $indukEntityId],
            ])
            : [
                '@type' => 'Organization',
                '@id' => $indukEntityId,
                'name' => 'BASAMO Nagari Creative Hub',
                'alternateName' => 'Basamo NCH',
                'url' => $indukHome,
                'logo' => asset('images/brand/basamo-nch-mark.png'),
            ];
        $structuredData = [
            '@context' => 'https://schema.org',
            '@graph' => [
                $publisher,
                [
                    '@type' => 'WebSite',
                    '@id' => $situsHome.'#website',
                    'url' => $situsHome,
                    'name' => $namaSitus,
                    'inLanguage' => 'id-ID',
                    'publisher' => ['@id' => $publisher['@id']],
                ],
                [
                    '@type' => 'WebPage',
                    '@id' => $canonicalUrl.'#webpage',
                    'url' => $canonicalUrl,
                    'name' => $judulLengkap,
                    'description' => $metaDescription,
                    'inLanguage' => 'id-ID',
                    'isPartOf' => ['@id' => $situsHome.'#website'],
                    'about' => ['@id' => $publisher['@id']],
                ],
            ],
        ];
    @endphp
    <title>{{ $judulLengkap }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    <meta name="robots" content="{{ $robotsContent }}">
    <meta name="theme-color" content="#003857">
    <meta name="color-scheme" content="light">
    <meta name="format-detection" content="telephone=no">
    <link rel="canonical" href="{{ $canonicalUrl }}">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="/favicon.ico" sizes="32x32">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta property="og:site_name" content="{{ $namaSitus }}">
    <meta property="og:locale" content="id_ID">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:title" content="{{ $judulLengkap }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:image" content="{{ $metaImage }}">
    <meta property="og:image:alt" content="@yield('meta_image_alt', $judulHalaman)">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $judulLengkap }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    <meta name="twitter:image" content="{{ $metaImage }}">
    <x-public.structured-data :data="$structuredData" />
    @stack('structured-data')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="flex min-h-full flex-col bg-background text-on-surface antialiased">
    <a href="#konten" class="sr-only z-[60] rounded-full bg-primary px-5 py-2 font-bold text-on-primary focus:not-sr-only focus:absolute focus:left-4 focus:top-4">Lewati ke konten</a>

    {{-- ── HEADER ─────────────────────────────────────────────────── --}}
    @php
        /* Navigasi, brand, dan URL induk dibaca dari satu sumber: PublicNavigation. */
        $nagariSitus = $situsNagari;
        $navItems = \App\Support\PublicNavigation::items($nagariSitus);
        $footerPillars = $nagariSitus ? array_slice($navItems, 1) : $navItems;
        $brandUrl = \App\Support\PublicNavigation::brandUrl($nagariSitus);
        $situsIndukUrl = \App\Support\PublicNavigation::indukUrl();

        $kabupatenLogoSitus = $nagariSitus?->kabupatenLogoUrl();

        /* Tautan akun dirakit di host milik pengguna, bukan host yang sedang dibuka.
           Rute portal tidak terikat domain, jadi `route()` di sini akan mengikuti
           hostname situs yang ditampilkan dan berakhir 403 di situs nagari lain. */
        $akun = auth()->user();
        $tujuanAkun = $akun ? app(\App\Support\Auth\TujuanSetelahLogin::class) : null;
        $dashboardUrl = $akun ? $tujuanAkun->dasbor($akun) : null;
        $logoutUrl = $akun ? $tujuanAkun->keluar($akun) : null;
    @endphp
    <header id="site-header" class="header-shrink sticky top-0 z-50 border-b border-white/20 bg-white/70 backdrop-blur-2xl transition-all duration-500 shadow-sm dark:bg-gray-900/70 dark:border-gray-700/50">
        <div class="mx-auto flex max-w-container-page items-center justify-between gap-3 px-margin-mobile py-3.5 sm:py-4 lg:px-margin-page transition-all duration-300">
            {{-- Brand --}}
            <a href="{{ $brandUrl }}" class="group flex shrink-0 items-center gap-3 transition-transform duration-300 hover:scale-105" aria-label="Beranda {{ $namaSitus }}">
                @include('filament.brand')

                {{-- Penanda nagari yang sedang dibuka. --}}
                @if($nagariSitus)
                    <span class="hidden items-center gap-2 border-l border-outline-variant pl-3 md:flex">
                        @if($kabupatenLogoSitus)
                            <img src="{{ $kabupatenLogoSitus }}" alt="" class="h-8 w-8 shrink-0 object-contain" loading="lazy">
                        @endif
                        <span class="min-w-0">
                            <span class="block truncate text-sm font-black leading-tight text-primary">{{ $nagariSitus->nama_lengkap }}</span>
                            @if($nagariSitus->kabupaten)
                                <span class="block truncate text-[11px] font-semibold text-on-surface-variant">{{ $nagariSitus->kabupaten }}</span>
                            @endif
                        </span>
                    </span>
                @endif
            </a>

            {{-- Navigasi utama desktop --}}
            <nav class="hidden min-w-0 items-center justify-center gap-1 xl:flex" aria-label="Navigasi utama">
                @foreach($navItems as ['href' => $href, 'label' => $label, 'route' => $activeRoute])
                    @php($active = $activeRoute && request()->routeIs(...(array) $activeRoute))
                    <a href="{{ $href }}"
                       @class([
                           'rounded-full px-3.5 py-2 text-[13px] font-bold transition-all duration-200',
                           'bg-primary text-on-primary shadow-sm' => $active,
                           'text-on-surface-variant hover:bg-primary/7 hover:text-primary' => ! $active,
                       ])
                       @if($active) aria-current="page" @endif>
                        {{ $label }}
                    </a>
                @endforeach
            </nav>

            {{-- Aksi (desktop & mobile) --}}
            <div class="flex shrink-0 items-center gap-2 sm:gap-3">
                @auth
                    <a href="{{ $dashboardUrl }}" class="hidden rounded-full bg-primary/10 px-5 py-2.5 text-sm font-bold text-primary transition-colors hover:bg-primary/20 sm:inline-flex">Dashboard</a>
                    <form method="POST" action="{{ $logoutUrl }}">@csrf
                        <button type="submit" class="hidden rounded-full bg-primary px-6 py-2.5 text-sm font-bold text-on-primary shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg active:translate-y-0 sm:inline-flex">Keluar</button>
                    </form>
                @else
                    <a href="{{ \App\Support\PublicNavigation::masukUrl() }}" class="shimmer-hover rounded-full bg-primary px-6 py-2.5 text-xs sm:text-sm font-bold text-on-primary shadow-sm transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg active:translate-y-0">
                        Masuk
                    </a>
                @endauth

                <button type="button"
                        class="flex h-10 w-10 items-center justify-center rounded-full border border-outline-variant bg-surface-container-lowest text-primary xl:hidden"
                        data-public-nav-toggle aria-expanded="false" aria-controls="public-mobile-nav" aria-label="Buka menu">
                    <x-heroicon-o-bars-3 class="h-5 w-5" data-menu-open-icon />
                    <x-heroicon-o-x-mark class="hidden h-5 w-5" data-menu-close-icon />
                </button>
            </div>
        </div>

        {{-- Panel navigasi mobile/tablet --}}
        <div id="public-mobile-nav" class="hidden border-t border-outline-variant bg-surface-container-lowest px-margin-mobile pb-5 pt-3 shadow-xl xl:hidden">
            <nav class="mx-auto grid max-w-container-page gap-1 sm:grid-cols-2" aria-label="Navigasi mobile">
                @foreach($navItems as ['href' => $href, 'label' => $label, 'route' => $activeRoute, 'icon' => $icon])
                    @php($active = $activeRoute && request()->routeIs(...(array) $activeRoute))
                    <a href="{{ $href }}"
                       @class([
                           'flex items-center gap-3 rounded-2xl px-4 py-3 text-sm font-bold transition-colors',
                           'bg-primary text-on-primary' => $active,
                           'text-on-surface-variant hover:bg-primary/7 hover:text-primary' => ! $active,
                       ])
                       @if($active) aria-current="page" @endif>
                        <x-dynamic-component :component="$icon" class="h-5 w-5 shrink-0 opacity-80" />
                        <span class="min-w-0 flex-1 truncate">{{ $label }}</span>
                        <x-heroicon-o-arrow-up-right class="h-4 w-4 shrink-0 opacity-60" />
                    </a>
                @endforeach
                @auth
                    <a href="{{ $dashboardUrl }}" class="flex items-center justify-between rounded-2xl px-4 py-3 text-sm font-bold text-primary sm:hidden">
                        Dashboard <x-heroicon-o-squares-2x2 class="h-4 w-4" />
                    </a>
                    <form method="POST" action="{{ $logoutUrl }}" class="sm:hidden">@csrf
                        <button type="submit" class="flex w-full items-center justify-between rounded-2xl px-4 py-3 text-sm font-bold text-error">
                            Keluar <x-heroicon-o-arrow-right-start-on-rectangle class="h-4 w-4" />
                        </button>
                    </form>
                @endauth
            </nav>
        </div>
    </header>

    {{-- ── MAIN ───────────────────────────────────────────────────── --}}
    <main id="konten" class="@yield('main-class', 'mx-auto w-full max-w-6xl px-margin-mobile py-6 sm:px-6') flex-1">
        @yield('content')
    </main>

    {{-- ── FOOTER ─────────────────────────────────────────────────── --}}
    <footer class="relative overflow-hidden border-t-8 border-secondary-container bg-gradient-to-br from-primary to-gray-900 pb-10 pt-16">
        <div class="songket-pattern absolute inset-0 opacity-10 mix-blend-overlay" aria-hidden="true"></div>
        <div class="absolute -left-32 -top-32 h-96 w-96 rounded-full bg-secondary-container/20 blur-[120px]" aria-hidden="true"></div>
        <div class="absolute -bottom-32 -right-32 h-96 w-96 rounded-full bg-primary-400/20 blur-[120px]" aria-hidden="true"></div>

        <div class="relative z-10 mx-auto grid max-w-container-page grid-cols-1 gap-12 px-margin-mobile text-on-primary md:grid-cols-12 lg:px-margin-page">
            {{-- Identitas situs. Di subdomain nagari yang diperkenalkan adalah
                 nagarinya, bukan platformnya. --}}
            <div class="space-y-6 md:col-span-5">
                @if($nagariSitus)
                    <div class="flex items-center gap-4 rounded-2xl bg-white/5 p-4 backdrop-blur-sm border border-white/10">
                        @if($kabupatenLogoSitus)
                            <img src="{{ $kabupatenLogoSitus }}" alt="" class="h-14 w-14 shrink-0 object-contain drop-shadow-md" loading="lazy">
                        @endif
                        <div class="min-w-0">
                            <p class="text-xl font-black leading-tight bg-gradient-to-r from-white to-gray-300 bg-clip-text text-transparent">{{ $nagariSitus->nama_lengkap }}</p>
                            <p class="truncate text-sm text-on-primary/80 font-medium mt-1">
                                {{ collect([$nagariSitus->kecamatan, $nagariSitus->kabupaten, $nagariSitus->provinsi])->filter()->implode(', ') }}
                            </p>
                        </div>
                    </div>
                    <p class="max-w-sm text-sm font-light leading-relaxed text-on-primary/70">
                        Wajah digital <strong class="font-semibold text-white">{{ $nagariSitus->nama_lengkap }}</strong> dalam ekosistem Nagari Creative Hub Sumatera Barat. Mendorong inovasi dari desa untuk dunia.
                    </p>
                @else
                    <div class="public-footer-brand">
                        @include('filament.brand')
                    </div>
                    <p class="max-w-sm text-sm font-light leading-relaxed text-on-primary/70">
                        Ekosistem digital Nagari Creative Hub: berakar pada tradisi, digerakkan oleh inovasi untuk nagari Sumatera Barat.
                    </p>
                @endif
            </div>

            <div class="grid grid-cols-2 gap-10 md:col-span-7 md:grid-cols-3">
                {{-- Pilar dibaca dari sumber navigasi yang sama dengan header. --}}
                <div class="space-y-4">
                    <h2 class="text-xs font-bold uppercase tracking-widest text-secondary-container">
                        {{ $nagariSitus ? 'Empat Pilar' : 'Jelajah' }}
                    </h2>
                    <ul class="space-y-3 text-sm text-on-primary/70">
                        @foreach($footerPillars as ['href' => $href, 'label' => $label, 'icon' => $icon])
                            <li>
                                <a href="{{ $href }}" class="group inline-flex items-center gap-2.5 transition-colors hover:text-secondary-container">
                                    <x-dynamic-component :component="$icon" class="h-4 w-4 shrink-0 opacity-70 transition-transform group-hover:scale-110" />
                                    <span>{{ $label }}</span>
                                    @if($label === 'Medan Nan Bapaneh')
                                        <span class="rounded-full border border-secondary-container/30 px-1.5 py-0.5 text-[9px] font-black uppercase tracking-wider text-secondary-container">Segera</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>

                <div class="space-y-4">
                    <h2 class="text-xs font-bold uppercase tracking-widest text-secondary-container">Kemitraan</h2>
                    <ul class="space-y-3 text-sm text-on-primary/70">
                        {{-- Dari situs nagari, kedua tautan ini menuju induknya. --}}
                        <li><a href="{{ $situsIndukUrl }}" class="transition-colors hover:text-secondary-container">Beranda BASAMO NCH</a></li>
                        <li><a href="{{ $situsIndukUrl }}/teras-nagari" class="transition-colors hover:text-secondary-container">Peta Teras Nagari</a></li>
                        <li><a href="{{ $situsIndukUrl }}#faq" class="transition-colors hover:text-secondary-container">Pertanyaan Umum</a></li>
                    </ul>
                </div>

                <div class="space-y-4">
                    <h2 class="text-xs font-bold uppercase tracking-widest text-secondary-container">Akun</h2>
                    <ul class="space-y-3 text-sm text-on-primary/70">
                        @auth
                            <li><a href="{{ $dashboardUrl }}" class="transition-colors hover:text-secondary-container">Dashboard</a></li>
                            <li>
                                <form method="POST" action="{{ $logoutUrl }}">@csrf
                                    <button type="submit" class="transition-colors hover:text-secondary-container">Keluar</button>
                                </form>
                            </li>
                        @else
                            <li><a href="{{ \App\Support\PublicNavigation::masukUrl() }}" class="transition-colors hover:text-secondary-container">Masuk</a></li>
                        @endauth
                    </ul>

                    {{-- Alamat situs ditampilkan apa adanya agar mudah disalin. --}}
                    @if($nagariSitus?->slug && config('app.public_base_domain'))
                        <div class="pt-2">
                            <p class="text-[11px] font-bold uppercase tracking-widest text-secondary-container">Alamat Situs</p>
                            <p class="mt-1 break-all font-mono text-xs text-on-primary/70">
                                {{ $nagariSitus->slug }}.{{ config('app.public_base_domain') }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="relative z-10 mx-auto mt-16 flex max-w-container-page flex-col gap-4 border-t border-white/10 px-margin-mobile pt-8 text-xs font-bold uppercase tracking-widest text-on-primary/60 sm:flex-row sm:items-center sm:justify-between lg:px-margin-page">
            <span>© {{ date('Y') }} BASAMO Nagari Creative Hub</span>
            <span class="flex items-center gap-2">
                Tradisi Berpadu Inovasi
                <x-heroicon-s-sparkles class="h-4 w-4 text-secondary-container" />
            </span>
        </div>
    </footer>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Header shrink on scroll
            const header = document.getElementById('site-header');
            if (header) {
                const checkScroll = () => {
                    if (window.scrollY > 20) {
                        header.classList.add('scrolled');
                    } else {
                        header.classList.remove('scrolled');
                    }
                };
                window.addEventListener('scroll', checkScroll, { passive: true });
                checkScroll();
            }

            // Navigasi mobile publik
            const toggle = document.querySelector('[data-public-nav-toggle]');
            const panel = document.getElementById('public-mobile-nav');
            if (toggle && panel) {
                const setMenu = (open) => {
                    panel.classList.toggle('hidden', !open);
                    toggle.setAttribute('aria-expanded', String(open));
                    toggle.setAttribute('aria-label', open ? 'Tutup menu' : 'Buka menu');
                    toggle.querySelector('[data-menu-open-icon]')?.classList.toggle('hidden', open);
                    toggle.querySelector('[data-menu-close-icon]')?.classList.toggle('hidden', !open);
                };
                toggle.addEventListener('click', () => setMenu(toggle.getAttribute('aria-expanded') !== 'true'));
                panel.querySelectorAll('a').forEach((a) => a.addEventListener('click', () => setMenu(false)));
            }

            // Daftarkan juga elemen yang disisipkan kemudian oleh filter katalog.
            // Tanpa ini, elemen baru tetap transparan karena kelas `revealed`
            // sebelumnya hanya diberikan sekali saat halaman pertama kali dimuat.
            const kurangiGerak = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            const revealObserver = 'IntersectionObserver' in window && ! kurangiGerak
                ? new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) {
                            entry.target.classList.add('revealed');
                            revealObserver.unobserve(entry.target);
                        }
                    });
                }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' })
                : null;

            const nyalakanReveal = (root = document, langsung = false) => {
                root.querySelectorAll('.reveal-up:not(.revealed), .stagger-children:not(.revealed)').forEach((el) => {
                    if (revealObserver && ! langsung) {
                        revealObserver.observe(el);
                    } else {
                        el.classList.add('revealed');
                    }
                });
            };

            nyalakanReveal();
            document.addEventListener('public:content-updated', (event) => {
                nyalakanReveal(event.detail?.root ?? document, true);
            });
        });
    </script>
    @if(! ($nagariSitus instanceof \App\Models\Nagari) && request()->routeIs('public.home'))
        <script>
            // Scrollspy nav one-page: tandai menu sesuai section yang sedang tampil.
            document.addEventListener('DOMContentLoaded', () => {
                if (!('IntersectionObserver' in window)) return;

                const links = [...document.querySelectorAll('header nav a[href*="#"]')];
                const byHash = new Map(links.map((a) => [new URL(a.href).hash, a]));
                const homeUrl = @json(route('public.home'));
                const beranda = [...document.querySelectorAll('header nav a')].filter((a) => a.href === homeUrl);

                /* Penanda aktif jangkar WAJIB memakai kelas yang sama persis dengan
                   penanda aktif berbasis rute yang dipasang server. Sebelumnya di sini
                   dipakai gaya garis bawah yang sama sekali berbeda dari pil di nav,
                   dan cabang desktopnya bahkan tidak pernah jalan karena memeriksa
                   `lg:flex` sementara navnya memakai `xl:flex`. */
                const aktif = ['bg-primary', 'text-on-primary', 'shadow-sm'];
                const pasif = ['text-on-surface-variant', 'hover:bg-primary/7', 'hover:text-primary'];

                const setAktif = (hash) => {
                    links.concat(beranda).forEach((a) => {
                        const nyala = hash ? new URL(a.href).hash === hash : !new URL(a.href).hash;
                        a.classList.remove(...aktif, ...pasif);
                        a.classList.add(...(nyala ? aktif : pasif));
                    });
                };

                const observer = new IntersectionObserver((entries) => {
                    entries.forEach((entry) => {
                        if (entry.isIntersecting) setAktif('#' + entry.target.id);
                    });
                }, { rootMargin: '-40% 0px -55% 0px' });

                byHash.forEach((_, hash) => {
                    const section = document.querySelector(hash);
                    if (section) observer.observe(section);
                });

                // Kembali ke atas (hero) → penanda balik ke Beranda.
                const hero = document.querySelector('main section');
                if (hero) {
                    new IntersectionObserver((entries) => {
                        entries.forEach((entry) => { if (entry.isIntersecting) setAktif(null); });
                    }, { rootMargin: '-10% 0px -60% 0px' }).observe(hero);
                }
            });
        </script>
    @endif
    @stack('scripts')
</body>
</html>
