<!DOCTYPE html>
<html lang="id" class="h-full scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Katalog UMKM') — Basamo NCH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="min-h-full bg-background text-on-surface antialiased">

    {{-- ── HEADER ─────────────────────────────────────────────────── --}}
    <header class="sticky top-0 z-50 border-b border-secondary/20 bg-surface-container-lowest/90 backdrop-blur-xl">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-margin-mobile py-4 lg:px-margin-desktop">
            {{-- Brand (logo final belum ada — pakai mark sederhana dulu) --}}
            <a href="{{ route('public.home') }}" class="flex items-center gap-3">
                <div class="flex h-10 w-10 items-center justify-center bg-primary gonjong-peak">
                    <x-heroicon-s-academic-cap class="h-5 w-5 text-secondary-container" />
                </div>
                <div class="text-2xl font-extrabold tracking-tight text-primary">Basamo <span class="text-secondary">NCH</span></div>
            </a>

            {{-- Nav (desktop) --}}
            <nav class="hidden items-center gap-10 md:flex">
                @php $isHome = request()->routeIs('public.home'); @endphp
                <a href="{{ route('public.home') }}" class="pb-1 font-bold tracking-wide {{ $isHome ? 'border-b-2 border-secondary text-primary' : 'text-on-surface-variant hover:text-primary' }} transition-colors">Beranda</a>
                <a href="{{ route('public.umkm.index') }}" class="font-semibold tracking-wide {{ request()->routeIs('public.umkm.*') ? 'text-primary' : 'text-on-surface-variant hover:text-primary' }} transition-colors">Katalog UMKM</a>
                <a href="{{ route('public.peta') }}" class="font-semibold tracking-wide {{ request()->routeIs('public.peta') ? 'text-primary' : 'text-on-surface-variant hover:text-primary' }} transition-colors">Peta Nagari</a>
            </nav>

            {{-- Actions --}}
            <div class="flex items-center gap-2 sm:gap-4">
                <a href="{{ route('login') }}" class="rounded-full bg-primary px-6 py-2.5 font-bold text-on-primary transition-all hover:-translate-y-0.5 hover:shadow-lg hover:shadow-primary/20 sm:px-8">Masuk Portal</a>
            </div>
        </div>
    </header>

    {{-- ── MAIN (full-bleed bila main-class kosong) ───────────────── --}}
    <main class="@yield('main-class', 'mx-auto w-full max-w-6xl px-margin-mobile py-6 sm:px-6')">
        @yield('content')
    </main>

    {{-- ── FOOTER ─────────────────────────────────────────────────── --}}
    <footer class="relative overflow-hidden border-t-8 border-secondary-container bg-primary pb-10 pt-16">
        <div class="songket-pattern absolute inset-0 opacity-5"></div>
        <div class="relative z-10 mx-auto flex max-w-7xl flex-col gap-10 px-margin-mobile text-on-primary md:flex-row md:items-center md:justify-between lg:px-margin-desktop">
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <div class="flex h-11 w-11 items-center justify-center bg-secondary-container gonjong-peak">
                        <x-heroicon-s-academic-cap class="h-5 w-5 text-primary" />
                    </div>
                    <div class="text-2xl font-extrabold tracking-tight text-on-primary">Basamo <span class="text-secondary-container">NCH</span></div>
                </div>
                <p class="max-w-[22rem] leading-relaxed text-on-primary/60">Berakar pada tradisi, digerakkan oleh inovasi — platform digital nagari Sumatera Barat.</p>
            </div>
            <nav class="flex flex-wrap gap-x-8 gap-y-3 font-semibold text-on-primary/70">
                <a href="{{ route('public.umkm.index') }}" class="transition-colors hover:text-secondary-container">Katalog UMKM</a>
                <a href="{{ route('public.peta') }}" class="transition-colors hover:text-secondary-container">Peta Nagari</a>
                <a href="{{ route('login') }}" class="transition-colors hover:text-secondary-container">Masuk Portal</a>
            </nav>
        </div>
        <div class="relative z-10 mx-auto mt-12 max-w-7xl border-t border-on-primary/10 px-margin-mobile pt-6 text-label-sm font-bold uppercase tracking-widest text-on-primary/40 lg:px-margin-desktop">
            © {{ date('Y') }} Basamo NCH — Tradisi Berpadu Inovasi
        </div>
    </footer>

    @stack('scripts')
</body>
</html>
