<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Portal') — Basamo NCH</title>
    <style>[x-cloak]{display:none!important}</style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-full bg-background text-on-surface antialiased">

    @php
        $onHome = request()->routeIs('portal.home');
        $onModule = request()->routeIs('portal.modules.*');
        $onUmkm = request()->routeIs('portal.umkm.*');
        $user = auth()->user();
        $isUmkmOwner = $user->hasUmkmAccess();
        $unreadCount = $user->unreadNotifications()->count();
    @endphp

    {{-- ── DESKTOP SIDEBAR (lg+) ───────────────────────────────────── --}}
    <aside class="hidden lg:fixed lg:inset-y-0 lg:left-0 lg:z-40 lg:flex lg:w-64 lg:flex-col rounded-r-xl bg-surface-container-low p-lg shadow-sm">
        {{-- Brand --}}
        <div class="mb-xl flex items-center gap-md px-lg pt-1">
            <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary shadow-sm">
                <x-heroicon-s-academic-cap class="h-5 w-5 text-on-primary" />
            </div>
            <div class="leading-tight">
                <p class="text-headline-sm font-bold text-sdg-17">Basamo NCH</p>
                <p class="text-label-md text-on-surface-variant">Smart Learning Center</p>
            </div>
        </div>

        {{-- CTA --}}
        <a href="{{ route('portal.modules.index') }}"
            class="mb-xl block rounded-lg bg-primary py-md text-center text-headline-sm font-bold text-on-primary shadow-sm transition-colors hover:bg-surface-tint">
            Mulai Belajar
        </a>

        {{-- Nav --}}
        <nav class="flex-1 space-y-sm">
            <a href="{{ route('portal.home') }}"
                class="flex items-center gap-md rounded-xl px-lg py-md text-headline-sm font-medium transition-all duration-200 active:translate-x-1 {{ $onHome ? 'bg-primary-container text-on-primary-container' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                <x-dynamic-component :component="$onHome ? 'heroicon-s-squares-2x2' : 'heroicon-o-squares-2x2'" class="h-5 w-5" />
                Beranda
            </a>
            <a href="{{ route('portal.modules.index') }}"
                class="flex items-center gap-md rounded-xl px-lg py-md text-headline-sm font-medium transition-all duration-200 active:translate-x-1 {{ $onModule ? 'bg-primary-container text-on-primary-container' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                <x-dynamic-component :component="$onModule ? 'heroicon-s-book-open' : 'heroicon-o-book-open'" class="h-5 w-5" />
                Belajar
            </a>
            <a href="{{ route('portal.leaderboard') }}"
                class="flex items-center gap-md rounded-xl px-lg py-md text-headline-sm font-medium transition-all duration-200 active:translate-x-1 {{ request()->routeIs('portal.leaderboard') ? 'bg-primary-container text-on-primary-container' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                <x-dynamic-component :component="request()->routeIs('portal.leaderboard') ? 'heroicon-s-trophy' : 'heroicon-o-trophy'" class="h-5 w-5" />
                Peringkat
            </a>
            @if($isUmkmOwner)
                <a href="{{ route('portal.umkm.index') }}"
                    class="flex items-center gap-md rounded-xl px-lg py-md text-headline-sm font-medium transition-all duration-200 active:translate-x-1 {{ $onUmkm ? 'bg-primary-container text-on-primary-container' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                    <x-dynamic-component :component="$onUmkm ? 'heroicon-s-building-storefront' : 'heroicon-o-building-storefront'" class="h-5 w-5" />
                    Produk Saya
                </a>
            @endif
        </nav>

        {{-- Bottom --}}
        <div class="mt-auto space-y-sm border-t border-outline-variant pt-lg">
            <a href="{{ route('portal.profile.edit') }}"
                class="flex items-center gap-md rounded-xl px-lg py-md text-headline-sm font-medium text-on-surface-variant transition-all duration-200 hover:bg-surface-container-high active:translate-x-1">
                <x-heroicon-o-user-circle class="h-5 w-5" />
                Profil Saya
            </a>
            <form method="POST" action="{{ route('portal.logout') }}">
                @csrf
                <button type="submit"
                    class="flex w-full items-center gap-md rounded-xl px-lg py-md text-headline-sm font-medium text-danger transition-all duration-200 hover:bg-error-container active:translate-x-1">
                    <x-heroicon-o-arrow-right-on-rectangle class="h-5 w-5" />
                    Keluar
                </button>
            </form>
        </div>
    </aside>

    {{-- ── CONTENT (offset by sidebar on lg) ───────────────────────── --}}
    <div class="lg:pl-64">

        {{-- TOP HEADER --}}
        <header class="sticky top-0 z-30 border-b border-outline-variant bg-surface/95 backdrop-blur">
            <div class="flex h-16 items-center justify-between px-margin-mobile sm:px-6 lg:px-margin-desktop">
                {{-- Brand (mobile) / spacer (desktop) --}}
                <a href="{{ route('portal.home') }}" class="flex items-center gap-md lg:hidden">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary shadow-sm">
                        <x-heroicon-s-academic-cap class="h-5 w-5 text-on-primary" />
                    </div>
                    <div class="leading-tight">
                        <p class="text-headline-sm font-bold text-primary">Basamo NCH</p>
                        <p class="text-label-md text-on-surface-variant">{{ $user->desa?->nama_lengkap ?? 'Portal Warga' }}</p>
                    </div>
                </a>
                <div class="hidden lg:block"></div>

                {{-- Right: bell + user --}}
                <div class="flex items-center gap-sm">
                    <a href="{{ route('portal.notifications') }}"
                        class="relative flex h-10 w-10 items-center justify-center rounded-xl transition-colors {{ request()->routeIs('portal.notifications') ? 'bg-primary-container text-on-primary-container' : 'text-on-surface-variant hover:bg-surface-container-high' }}"
                        aria-label="Notifikasi">
                        <x-dynamic-component :component="request()->routeIs('portal.notifications') ? 'heroicon-s-bell' : 'heroicon-o-bell'" class="h-6 w-6" />
                        @if($unreadCount > 0)
                            <span class="absolute right-1.5 top-1.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-danger px-1 text-[10px] font-bold text-white">
                                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                            </span>
                        @endif
                    </a>

                    <div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false">
                        <button @click="open = !open"
                            class="flex items-center gap-sm rounded-xl border border-outline-variant bg-surface-container-lowest px-1.5 py-1.5 transition-colors hover:bg-surface-container-high lg:pl-2 lg:pr-3">
                            <x-portal.avatar :name="$user->name" :src="$user->avatarUrl()" variant="solid" size="sm" />
                            <span class="hidden max-w-[8rem] truncate text-body-md font-semibold text-on-surface lg:block">{{ explode(' ', $user->name)[0] }}</span>
                            <x-heroicon-s-chevron-down class="hidden h-3.5 w-3.5 shrink-0 text-on-surface-variant transition-transform duration-200 lg:block"
                                ::class="{ 'rotate-180': open }" />
                        </button>
                        <div x-show="open" x-cloak
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                            class="absolute right-0 top-full z-50 mt-2 w-56 origin-top-right rounded-xl bg-surface-container-lowest p-1.5 shadow-lg ring-1 ring-black/5">
                            <div class="flex items-center gap-md px-3 py-3">
                                <x-portal.avatar :name="$user->name" :src="$user->avatarUrl()" variant="solid" size="lg" />
                                <div class="min-w-0">
                                    <p class="truncate text-body-md font-semibold text-on-surface">{{ $user->name }}</p>
                                    <p class="truncate text-label-md text-on-surface-variant">{{ $user->desa?->nama_lengkap ?? 'Warga' }}</p>
                                </div>
                            </div>
                            <div class="border-t border-outline-variant pt-1.5">
                                <a href="{{ route('portal.profile.edit') }}"
                                    class="flex w-full items-center gap-md rounded-xl px-3 py-2.5 text-body-md font-medium text-on-surface transition-colors hover:bg-surface-container-high">
                                    <x-heroicon-o-user-circle class="h-5 w-5 shrink-0 text-on-surface-variant" />
                                    Profil Saya
                                </a>
                                <form method="POST" action="{{ route('portal.logout') }}">
                                    @csrf
                                    <button type="submit"
                                        class="flex w-full items-center gap-md rounded-xl px-3 py-2.5 text-body-md font-medium text-on-surface transition-colors hover:bg-error-container hover:text-danger">
                                        <x-heroicon-o-arrow-right-on-rectangle class="h-5 w-5 shrink-0 text-on-surface-variant" />
                                        Keluar dari Akun
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        {{-- Flash --}}
        @if(session('error') || session('info') || session('success'))
            <div class="mx-auto w-full max-w-5xl px-margin-mobile pt-lg sm:px-6 lg:px-margin-desktop">
                <div class="space-y-sm">
                    @if(session('success'))
                        <div class="flex items-start gap-sm rounded-xl border border-secondary-container bg-secondary-container/30 px-4 py-3 text-body-md text-on-secondary-container">
                            <x-heroicon-s-check-circle class="mt-0.5 h-4 w-4 shrink-0" /> {{ session('success') }}
                        </div>
                    @endif
                    @if(session('info'))
                        <div class="flex items-start gap-sm rounded-xl border border-primary-fixed bg-primary-fixed/40 px-4 py-3 text-body-md text-primary">
                            <x-heroicon-s-information-circle class="mt-0.5 h-4 w-4 shrink-0" /> {{ session('info') }}
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="flex items-start gap-sm rounded-xl border border-error-container bg-error-container/40 px-4 py-3 text-body-md text-on-error-container">
                            <x-heroicon-s-exclamation-triangle class="mt-0.5 h-4 w-4 shrink-0" /> {{ session('error') }}
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Hero --}}
        @yield('hero')

        {{-- Main --}}
        <main class="mx-auto w-full max-w-5xl px-margin-mobile @yield('main-class', 'py-6 pb-28 lg:pb-10') sm:px-6 lg:px-margin-desktop">
            @yield('content')
        </main>
    </div>

    {{-- ── MOBILE BOTTOM NAVIGATION (below lg) ──────────────────────── --}}
    @section('bottom-navigation')
    <nav class="fixed inset-x-0 bottom-0 z-30 border-t border-outline-variant bg-surface/95 backdrop-blur lg:hidden">
        <div class="flex h-16 items-stretch">
            <a href="{{ route('portal.home') }}"
                class="flex flex-1 flex-col items-center justify-center gap-1 transition-colors {{ $onHome ? 'text-primary' : 'text-on-surface-variant' }}">
                <x-dynamic-component :component="$onHome ? 'heroicon-s-squares-2x2' : 'heroicon-o-squares-2x2'" class="h-6 w-6" />
                <span class="text-[10px] font-semibold">Beranda</span>
            </a>
            <a href="{{ route('portal.modules.index') }}"
                class="flex flex-1 flex-col items-center justify-center gap-1 transition-colors {{ $onModule ? 'text-primary' : 'text-on-surface-variant' }}">
                <x-dynamic-component :component="$onModule ? 'heroicon-s-book-open' : 'heroicon-o-book-open'" class="h-6 w-6" />
                <span class="text-[10px] font-semibold">Belajar</span>
            </a>
            <a href="{{ route('portal.leaderboard') }}"
                class="flex flex-1 flex-col items-center justify-center gap-1 transition-colors {{ request()->routeIs('portal.leaderboard') ? 'text-primary' : 'text-on-surface-variant' }}">
                <x-dynamic-component :component="request()->routeIs('portal.leaderboard') ? 'heroicon-s-trophy' : 'heroicon-o-trophy'" class="h-6 w-6" />
                <span class="text-[10px] font-semibold">Peringkat</span>
            </a>
            @if($isUmkmOwner)
                <a href="{{ route('portal.umkm.index') }}"
                    class="flex flex-1 flex-col items-center justify-center gap-1 transition-colors {{ $onUmkm ? 'text-primary' : 'text-on-surface-variant' }}">
                    <x-dynamic-component :component="$onUmkm ? 'heroicon-s-building-storefront' : 'heroicon-o-building-storefront'" class="h-6 w-6" />
                    <span class="text-[10px] font-semibold">Produk Saya</span>
                </a>
            @endif
        </div>
    </nav>
    @show

    <x-portal.toast />

    @livewireScripts
</body>
</html>
