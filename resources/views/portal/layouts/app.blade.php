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
        // Notifikasi terbaru untuk modal header + jumlah belum-dibaca (akurat, bukan dari 15 teratas).
        $notifications = $user->notifications()->latest()->limit(15)->get();
        $unreadCount = $user->unreadNotifications()->count();
    @endphp

    {{-- ── DESKTOP SIDEBAR (lg+) ───────────────────────────────────── --}}
    <aside class="hidden lg:fixed lg:inset-y-0 lg:left-0 lg:z-40 lg:flex lg:w-64 lg:flex-col rounded-r-xl bg-surface-container-low p-lg shadow-sm">
        {{-- Brand --}}
        <div class="mb-xl flex items-center gap-sm px-1 pt-1">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center bg-primary gonjong-peak">
                <x-heroicon-s-academic-cap class="h-5 w-5 text-secondary-container" />
            </div>
            <div class="leading-none">
                {{-- Wordmark jadi acuan lebar; subtitle di-justify agar kedua baris sama panjang. --}}
                <p class="w-fit whitespace-nowrap text-xl font-extrabold text-primary">Basamo <span class="text-secondary">NCH</span></p>
                <p class="mt-1 text-[11px] font-medium text-on-surface-variant" style="text-align: justify; text-align-last: justify;">Smart Learning Center</p>
            </div>
        </div>

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
            {{-- "Peringkat" tak lagi di nav — diakses lewat kartu interaktif di beranda. --}}
            {{-- Pemilik → kelola lapak; warga lain → ajukan akses UMKM. --}}
            <a href="{{ $isUmkmOwner ? route('portal.umkm.index') : route('portal.umkm.ajukan') }}"
                class="flex items-center gap-md rounded-xl px-lg py-md text-headline-sm font-medium transition-all duration-200 active:translate-x-1 {{ $onUmkm ? 'bg-primary-container text-on-primary-container' : 'text-on-surface-variant hover:bg-surface-container-high' }}">
                <x-dynamic-component :component="$onUmkm ? 'heroicon-s-building-storefront' : 'heroicon-o-building-storefront'" class="h-5 w-5" />
                {{ $isUmkmOwner ? 'Produk Saya' : 'UMKM' }}
            </a>
        </nav>

        {{-- Profil & Keluar tersedia di menu pengguna (pojok kanan atas) — tak diduplikasi di sini. --}}
    </aside>

    {{-- ── CONTENT (offset by sidebar on lg) ───────────────────────── --}}
    <div class="lg:pl-64">

        {{-- TOP HEADER --}}
        <header class="sticky top-0 z-30 border-b border-outline-variant bg-surface/95 backdrop-blur">
            <div class="flex h-16 items-center justify-between px-margin-mobile sm:px-6 lg:px-margin-desktop">
                {{-- Brand (mobile) / spacer (desktop) --}}
                <a href="{{ route('portal.home') }}" class="flex items-center gap-md lg:hidden">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center bg-primary gonjong-peak">
                        <x-heroicon-s-academic-cap class="h-5 w-5 text-secondary-container" />
                    </div>
                    <div class="leading-tight">
                        <p class="text-headline-sm font-bold text-primary">Basamo <span class="text-secondary">NCH</span></p>
                        <p class="text-label-md text-on-surface-variant">{{ $user->desa?->nama_lengkap ?? 'Portal Warga' }}</p>
                    </div>
                </a>
                <div class="hidden lg:block"></div>

                {{-- Right: bell + user --}}
                <div class="flex items-center gap-sm">
                    {{-- Lonceng → modal notifikasi (bukan halaman terpisah). Membuka modal
                         menandai semua sudah dibaca (fetch POST) & menolkan badge. --}}
                    <div x-data="{
                        open: false,
                        unread: {{ $unreadCount }},
                        marked: false,
                        openModal() {
                            this.open = true;
                            if (this.marked || this.unread === 0) return;
                            this.marked = true;
                            fetch('{{ route('portal.notifications.read') }}', {
                                method: 'POST',
                                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                                keepalive: true,
                            }).then(() => { this.unread = 0; }).catch(() => { this.marked = false; });
                        },
                    }">
                        <button type="button" @click="openModal()" aria-label="Notifikasi"
                            class="relative flex h-10 w-10 items-center justify-center rounded-xl text-on-surface-variant transition-colors hover:bg-surface-container-high"
                            :class="{ 'bg-primary-container text-on-primary-container': open }">
                            <x-heroicon-o-bell class="h-6 w-6" />
                            <span x-show="unread > 0" x-cloak
                                class="absolute right-1.5 top-1.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-danger px-1 text-[10px] font-bold text-white"
                                x-text="unread > 9 ? '9+' : unread"></span>
                        </button>

                        <template x-teleport="body">
                            <div x-show="open" x-cloak @keydown.escape.window="open = false"
                                class="fixed inset-0 z-[70] flex items-end justify-center sm:items-start sm:justify-end sm:p-4">

                                {{-- Overlay --}}
                                <div x-show="open" x-transition.opacity @click="open = false"
                                    class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>

                                {{-- Panel: bottom-sheet di HP, panel kanan-atas di desktop --}}
                                <div x-show="open"
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 translate-y-6 sm:translate-y-0 sm:-translate-y-2 sm:scale-95"
                                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                                    x-transition:leave="transition ease-in duration-150"
                                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                                    x-transition:leave-end="opacity-0 translate-y-6 sm:scale-95"
                                    class="relative flex max-h-[80vh] w-full flex-col overflow-hidden rounded-t-3xl border border-outline-variant bg-surface-container-lowest shadow-xl sm:mt-12 sm:max-w-[26rem] sm:rounded-3xl"
                                    role="dialog" aria-modal="true" aria-label="Notifikasi">

                                    {{-- Header --}}
                                    <div class="flex shrink-0 items-center justify-between gap-3 border-b border-outline-variant px-5 py-4">
                                        <div>
                                            <h2 class="text-base font-bold text-on-surface">Notifikasi</h2>
                                            <p class="text-xs text-on-surface-variant">Modul baru, kuis, & balasan diskusi.</p>
                                        </div>
                                        <button type="button" @click="open = false" aria-label="Tutup"
                                            class="flex h-8 w-8 items-center justify-center rounded-lg text-on-surface-variant transition-colors hover:bg-surface-container-high">
                                            <x-heroicon-o-x-mark class="h-5 w-5" />
                                        </button>
                                    </div>

                                    {{-- Daftar (scrollable) --}}
                                    <div class="min-h-0 flex-1 overflow-y-auto p-3">
                                        @forelse($notifications as $notification)
                                            @php
                                                $data = $notification->data;
                                                $isUnread = is_null($notification->read_at);
                                                $icon = $data['icon'] ?? 'heroicon-s-bell';
                                            @endphp
                                            <a href="{{ $data['url'] ?? route('portal.home') }}"
                                                class="mb-2 flex items-start gap-3 rounded-2xl border p-3.5 transition-colors last:mb-0
                                                    {{ $isUnread
                                                        ? 'border-primary/20 bg-primary/5 hover:bg-primary/10'
                                                        : 'border-transparent hover:bg-surface-container-low' }}">
                                                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl
                                                    {{ $isUnread ? 'bg-primary/10 text-primary' : 'bg-surface-container-high text-on-surface-variant' }}">
                                                    <x-dynamic-component :component="$icon" class="h-5 w-5" />
                                                </span>
                                                <div class="min-w-0 flex-1">
                                                    <div class="flex items-start justify-between gap-2">
                                                        <p class="text-sm font-semibold text-on-surface">{{ $data['title'] ?? 'Notifikasi' }}</p>
                                                        @if($isUnread)
                                                            <span class="mt-1 h-2 w-2 shrink-0 rounded-full bg-primary"></span>
                                                        @endif
                                                    </div>
                                                    @if(! empty($data['body']))
                                                        <p class="mt-0.5 text-sm text-on-surface-variant">{{ $data['body'] }}</p>
                                                    @endif
                                                    <p class="mt-1 text-xs text-on-surface-variant">{{ $notification->created_at->diffForHumans() }}</p>
                                                </div>
                                            </a>
                                        @empty
                                            <div class="flex flex-col items-center justify-center px-6 py-14 text-center">
                                                <span class="mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-surface-container-high text-on-surface-variant">
                                                    <x-heroicon-o-bell class="h-7 w-7" />
                                                </span>
                                                <p class="text-sm font-semibold text-on-surface">Belum ada notifikasi</p>
                                                <p class="mt-0.5 text-xs text-on-surface-variant">Info modul baru, kuis, atau balasan diskusi akan muncul di sini.</p>
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

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
                            class="absolute right-0 top-full z-50 mt-2 w-48 origin-top-right rounded-xl bg-surface-container-lowest p-1.5 shadow-lg ring-1 ring-black/5">
                            <a href="{{ route('portal.profile.edit') }}"
                                class="flex w-full items-center gap-md rounded-xl px-3 py-2.5 text-body-md font-medium text-on-surface transition-colors hover:bg-surface-container-high">
                                <x-heroicon-o-user-circle class="h-5 w-5 shrink-0 text-on-surface-variant" />
                                Profil
                            </a>
                            <form method="POST" action="{{ route('portal.logout') }}">
                                @csrf
                                <button type="submit"
                                    class="flex w-full items-center gap-md rounded-xl px-3 py-2.5 text-body-md font-medium text-on-surface transition-colors hover:bg-error-container hover:text-danger">
                                    <x-heroicon-o-arrow-right-on-rectangle class="h-5 w-5 shrink-0 text-on-surface-variant" />
                                    Keluar
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        {{-- Flash → toast: satu kanal alert (animasi, berikon, bisa ditutup). Error tampil
             lebih lama (panduan navigasi tak boleh terlewat). Lihat x-portal.toast. --}}
        @php
            $flashToasts = collect(['success', 'info', 'error'])
                ->filter(fn ($type) => filled(session($type)))
                ->map(fn ($type) => ['type' => $type, 'message' => session($type)])
                ->values();
        @endphp
        @if($flashToasts->isNotEmpty())
            <script>
                window.addEventListener('load', () => {
                    @foreach($flashToasts as $f)
                        window.dispatchEvent(new CustomEvent('toast', { detail: {
                            type: @json($f['type']),
                            title: @json($f['message']),
                            timeout: {{ $f['type'] === 'error' ? 6000 : 4000 }},
                        } }));
                    @endforeach
                });
            </script>
        @endif

        {{-- Hero --}}
        @yield('hero')

        {{-- Main --}}
        <main class="mx-auto w-full @yield('main-width', 'max-w-[120rem]') px-margin-mobile @yield('main-class', 'py-6 pb-28 lg:pb-10') sm:px-6 lg:px-margin-desktop">
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
            {{-- "Peringkat" tak lagi di nav — diakses lewat kartu interaktif di beranda. --}}
            {{-- Pemilik → kelola lapak; warga lain → ajukan akses UMKM. --}}
            <a href="{{ $isUmkmOwner ? route('portal.umkm.index') : route('portal.umkm.ajukan') }}"
                class="flex flex-1 flex-col items-center justify-center gap-1 transition-colors {{ $onUmkm ? 'text-primary' : 'text-on-surface-variant' }}">
                <x-dynamic-component :component="$onUmkm ? 'heroicon-s-building-storefront' : 'heroicon-o-building-storefront'" class="h-6 w-6" />
                <span class="text-[10px] font-semibold">{{ $isUmkmOwner ? 'Produk Saya' : 'UMKM' }}</span>
            </a>
        </div>
    </nav>
    @show

    <x-portal.toast />

    @livewireScripts
</body>
</html>
