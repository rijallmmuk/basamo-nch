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
<body class="min-h-full bg-slate-50 antialiased">

    @php
        $onHome = request()->routeIs('portal.home');
        $onModule = request()->routeIs('portal.modules.*');
        $onUmkm = request()->routeIs('portal.umkm.*');
        $user = auth()->user();
        $isUmkmOwner = $user->hasUmkmAccess();
        $unreadCount = $user->unreadNotifications()->count();
    @endphp

    {{-- ── DESKTOP SIDEBAR (lg+) ───────────────────────────────────── --}}
    <aside class="hidden lg:fixed lg:inset-y-0 lg:left-0 lg:z-40 lg:flex lg:w-64 lg:flex-col lg:border-r lg:border-gray-200 lg:bg-white">
        <div class="flex items-center gap-3 px-6 py-5">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-600 shadow-sm">
                <x-heroicon-s-academic-cap class="h-6 w-6 text-white" />
            </div>
            <div class="leading-tight">
                <p class="text-sm font-bold text-gray-900">Basamo NCH</p>
                <p class="text-[11px] text-gray-400">Smart Learning Center</p>
            </div>
        </div>

        <nav class="mt-2 flex-1 space-y-1 px-4">
            <a href="{{ route('portal.home') }}"
                class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition-colors {{ $onHome ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                <x-dynamic-component :component="$onHome ? 'heroicon-s-squares-2x2' : 'heroicon-o-squares-2x2'" class="h-5 w-5" />
                Beranda
            </a>
            <a href="{{ route('portal.modules.index') }}"
                class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition-colors {{ $onModule ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                <x-dynamic-component :component="$onModule ? 'heroicon-s-book-open' : 'heroicon-o-book-open'" class="h-5 w-5" />
                Belajar
            </a>
            @if($isUmkmOwner)
                <a href="{{ route('portal.umkm.index') }}"
                    class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition-colors {{ $onUmkm ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                    <x-dynamic-component :component="$onUmkm ? 'heroicon-s-building-storefront' : 'heroicon-o-building-storefront'" class="h-5 w-5" />
                    Produk Saya
                </a>
            @endif
        </nav>
    </aside>

    {{-- ── CONTENT (offset by sidebar on lg) ───────────────────────── --}}
    <div class="lg:pl-64">

        {{-- TOP HEADER (selalu) --}}
        <header class="sticky top-0 z-30 border-b border-gray-200/80 bg-white/95 backdrop-blur">
            <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
                {{-- Brand (mobile) / spacer (desktop) --}}
                <a href="{{ route('portal.home') }}" class="flex items-center gap-2.5 lg:hidden">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-600 shadow-sm">
                        <x-heroicon-s-academic-cap class="h-5 w-5 text-white" />
                    </div>
                    <div class="leading-tight">
                        <p class="text-sm font-bold text-gray-900">Basamo NCH</p>
                        <p class="text-[11px] text-gray-400">{{ $user->nagari?->nama ?? 'Portal Warga' }}</p>
                    </div>
                </a>
                <div class="hidden lg:block"></div>

                {{-- Right: bell + user --}}
                <div class="flex items-center gap-1.5">
                    <a href="{{ route('portal.notifications') }}"
                        class="relative flex h-10 w-10 items-center justify-center rounded-xl transition-colors {{ request()->routeIs('portal.notifications') ? 'bg-indigo-50 text-indigo-600' : 'text-gray-500 hover:bg-gray-100' }}"
                        aria-label="Notifikasi">
                        <x-dynamic-component :component="request()->routeIs('portal.notifications') ? 'heroicon-s-bell' : 'heroicon-o-bell'" class="h-6 w-6" />
                        @if($unreadCount > 0)
                            <span class="absolute right-1.5 top-1.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white">
                                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
                            </span>
                        @endif
                    </a>

                    <div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false">
                        <button @click="open = !open"
                            class="flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-1.5 py-1.5 transition-colors hover:bg-gray-50 lg:pl-2 lg:pr-3">
                            <x-portal.avatar :name="$user->name" :src="$user->avatarUrl()" variant="solid" size="sm" />
                            <span class="hidden max-w-[8rem] truncate text-sm font-semibold text-gray-700 lg:block">{{ explode(' ', $user->name)[0] }}</span>
                            <x-heroicon-s-chevron-down class="hidden h-3.5 w-3.5 shrink-0 text-gray-400 transition-transform duration-200 lg:block"
                                ::class="{ 'rotate-180': open }" />
                        </button>
                        <div x-show="open" x-cloak
                            x-transition:enter="transition ease-out duration-100"
                            x-transition:enter-start="opacity-0 scale-95"
                            x-transition:enter-end="opacity-100 scale-100"
                            x-transition:leave="transition ease-in duration-75"
                            x-transition:leave-start="opacity-100 scale-100"
                            x-transition:leave-end="opacity-0 scale-95"
                            class="absolute right-0 top-full z-50 mt-2 w-56 origin-top-right rounded-2xl bg-white p-1.5 shadow-lg ring-1 ring-black/5">
                            <div class="flex items-center gap-3 px-3 py-3">
                                <x-portal.avatar :name="$user->name" :src="$user->avatarUrl()" variant="solid" size="lg" />
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-gray-900">{{ $user->name }}</p>
                                    <p class="truncate text-xs text-gray-400">{{ $user->nagari?->nama ?? 'Warga' }}</p>
                                </div>
                            </div>
                            <div class="border-t border-gray-100 pt-1.5">
                                <a href="{{ route('portal.profile.edit') }}"
                                    class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-gray-50">
                                    <x-heroicon-o-user-circle class="h-5 w-5 shrink-0 text-gray-400" />
                                    Profil Saya
                                </a>
                                <form method="POST" action="{{ route('portal.logout') }}">
                                    @csrf
                                    <button type="submit"
                                        class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-gray-700 transition-colors hover:bg-red-50 hover:text-red-600">
                                        <x-heroicon-o-arrow-right-on-rectangle class="h-5 w-5 shrink-0 text-gray-400" />
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
            <div class="mx-auto w-full max-w-5xl px-4 pt-4 sm:px-6 lg:px-8">
                <div class="space-y-2">
                    @if(session('success'))
                        <div class="flex items-start gap-2.5 rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                            <x-heroicon-s-check-circle class="mt-0.5 h-4 w-4 shrink-0" /> {{ session('success') }}
                        </div>
                    @endif
                    @if(session('info'))
                        <div class="flex items-start gap-2.5 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-700">
                            <x-heroicon-s-information-circle class="mt-0.5 h-4 w-4 shrink-0" /> {{ session('info') }}
                        </div>
                    @endif
                    @if(session('error'))
                        <div class="flex items-start gap-2.5 rounded-xl border border-red-100 bg-red-50 px-4 py-3 text-sm text-red-700">
                            <x-heroicon-s-exclamation-triangle class="mt-0.5 h-4 w-4 shrink-0" /> {{ session('error') }}
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Hero --}}
        @yield('hero')

        {{-- Main --}}
        <main class="mx-auto w-full max-w-5xl px-4 @yield('main-class', 'py-6 pb-28 lg:pb-10') sm:px-6 lg:px-8">
            @yield('content')
        </main>
    </div>

    {{-- ── MOBILE BOTTOM NAVIGATION (below lg) ──────────────────────── --}}
    @section('bottom-navigation')
    <nav class="fixed inset-x-0 bottom-0 z-30 border-t border-gray-200 bg-white/95 backdrop-blur lg:hidden">
        <div class="flex h-16 items-stretch">
            <a href="{{ route('portal.home') }}"
                class="flex flex-1 flex-col items-center justify-center gap-1 transition-colors {{ $onHome ? 'text-indigo-600' : 'text-gray-400' }}">
                <x-dynamic-component :component="$onHome ? 'heroicon-s-squares-2x2' : 'heroicon-o-squares-2x2'" class="h-6 w-6" />
                <span class="text-[10px] font-semibold">Beranda</span>
            </a>
            <a href="{{ route('portal.modules.index') }}"
                class="flex flex-1 flex-col items-center justify-center gap-1 transition-colors {{ $onModule ? 'text-indigo-600' : 'text-gray-400' }}">
                <x-dynamic-component :component="$onModule ? 'heroicon-s-book-open' : 'heroicon-o-book-open'" class="h-6 w-6" />
                <span class="text-[10px] font-semibold">Belajar</span>
            </a>
            @if($isUmkmOwner)
                <a href="{{ route('portal.umkm.index') }}"
                    class="flex flex-1 flex-col items-center justify-center gap-1 transition-colors {{ $onUmkm ? 'text-indigo-600' : 'text-gray-400' }}">
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
