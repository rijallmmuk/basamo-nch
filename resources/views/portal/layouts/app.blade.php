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
<body class="flex min-h-full flex-col bg-slate-50 antialiased">

    @php
        $onHome = request()->routeIs('portal.home');
        $onModule = request()->routeIs('portal.modules.*');
        $user = auth()->user();
    @endphp

    {{-- ── TOP NAVIGATION ──────────────────────────────────────────── --}}
    <header class="sticky top-0 z-30 border-b border-gray-200/80 bg-white/95 backdrop-blur supports-[backdrop-filter]:bg-white/80">
        <div class="mx-auto flex h-16 max-w-5xl items-center justify-between px-4 sm:px-6 lg:px-8">

            {{-- Brand --}}
            <a href="{{ route('portal.home') }}" class="flex items-center gap-2.5">
                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-indigo-600 shadow-sm">
                    <span class="text-base font-bold text-white">B</span>
                </div>
                <div class="leading-tight">
                    <p class="text-sm font-bold text-gray-900">Basamo NCH</p>
                    <p class="text-[11px] text-gray-400">{{ $user->nagari?->nama ?? 'Portal Warga' }}</p>
                </div>
            </a>

            {{-- Desktop nav --}}
            <nav class="hidden items-center gap-1 md:flex">
                <a href="{{ route('portal.home') }}"
                    class="rounded-lg px-4 py-2 text-sm font-semibold transition-colors {{ $onHome ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                    Beranda
                </a>
                <a href="{{ route('portal.modules.index') }}"
                    class="rounded-lg px-4 py-2 text-sm font-semibold transition-colors {{ $onModule ? 'bg-indigo-50 text-indigo-700' : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900' }}">
                    Modul
                </a>
            </nav>

            {{-- User dropdown --}}
            <div class="relative" x-data="{ open: false }" @keydown.escape.window="open = false" @click.outside="open = false">
                <button @click="open = !open"
                    class="flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-1.5 py-1.5 transition-colors hover:bg-gray-50 md:pl-2 md:pr-3">
                    <x-portal.avatar :name="$user->name" variant="solid" size="sm" />
                    <span class="hidden max-w-[8rem] truncate text-sm font-semibold text-gray-700 md:block">
                        {{ explode(' ', $user->name)[0] }}
                    </span>
                    <x-heroicon-s-chevron-down class="hidden h-3.5 w-3.5 shrink-0 text-gray-400 transition-transform duration-200 md:block"
                        ::class="{ 'rotate-180': open }" />
                </button>

                <div x-show="open" x-cloak
                    x-transition:enter="transition ease-out duration-100"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-75"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                    class="absolute right-0 top-full z-50 mt-2 w-60 origin-top-right rounded-2xl bg-white p-1.5 shadow-lg ring-1 ring-black/5">

                    <div class="flex items-center gap-3 px-3 py-3">
                        <x-portal.avatar :name="$user->name" variant="solid" size="lg" />
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-gray-900">{{ $user->name }}</p>
                            <p class="truncate text-xs text-gray-400">{{ $user->nagari?->nama ?? 'Warga' }}</p>
                        </div>
                    </div>

                    <div class="border-t border-gray-100 pt-1.5">
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
    </header>

    {{-- ── FLASH MESSAGES ──────────────────────────────────────────── --}}
    @if(session('error') || session('info') || session('success'))
        <div class="mx-auto w-full max-w-5xl px-4 pt-4 sm:px-6 lg:px-8">
            <div class="space-y-2">
                @if(session('success'))
                    <div class="flex items-start gap-2.5 rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                        <x-heroicon-s-check-circle class="mt-0.5 h-4 w-4 shrink-0" />
                        {{ session('success') }}
                    </div>
                @endif
                @if(session('info'))
                    <div class="flex items-start gap-2.5 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3 text-sm text-blue-700">
                        <x-heroicon-s-information-circle class="mt-0.5 h-4 w-4 shrink-0" />
                        {{ session('info') }}
                    </div>
                @endif
                @if(session('error'))
                    <div class="flex items-start gap-2.5 rounded-xl border border-red-100 bg-red-50 px-4 py-3 text-sm text-red-700">
                        <x-heroicon-s-exclamation-triangle class="mt-0.5 h-4 w-4 shrink-0" />
                        {{ session('error') }}
                    </div>
                @endif
            </div>
        </div>
    @endif

    {{-- ── HERO (optional, injected by child view) ─────────────────── --}}
    @yield('hero')

    {{-- ── MAIN CONTENT ────────────────────────────────────────────── --}}
    <main class="mx-auto w-full max-w-5xl flex-1 px-4 @yield('main-class', 'py-6 pb-28 md:pb-10') sm:px-6 lg:px-8">
        @yield('content')
    </main>

    {{-- ── MOBILE BOTTOM NAVIGATION ────────────────────────────────── --}}
    @section('bottom-navigation')
    <nav class="fixed inset-x-0 bottom-0 z-30 border-t border-gray-200 bg-white/95 backdrop-blur md:hidden">
        <div class="mx-auto flex h-16 max-w-5xl items-stretch">
            <a href="{{ route('portal.home') }}"
                class="flex flex-1 flex-col items-center justify-center gap-1 transition-colors {{ $onHome ? 'text-indigo-600' : 'text-gray-400' }}">
                <x-dynamic-component :component="$onHome ? 'heroicon-s-home' : 'heroicon-o-home'" class="h-6 w-6" />
                <span class="text-[10px] font-semibold">Beranda</span>
            </a>
            <a href="{{ route('portal.modules.index') }}"
                class="flex flex-1 flex-col items-center justify-center gap-1 transition-colors {{ $onModule ? 'text-indigo-600' : 'text-gray-400' }}">
                <x-dynamic-component :component="$onModule ? 'heroicon-s-book-open' : 'heroicon-o-book-open'" class="h-6 w-6" />
                <span class="text-[10px] font-semibold">Modul</span>
            </a>
        </div>
    </nav>
    @show

    @livewireScripts
</body>
</html>
