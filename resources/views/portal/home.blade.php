@extends('portal.layouts.app')

@section('title', 'Beranda')
@section('main-class', 'py-6 pb-28 lg:pb-10')

@php
    $user = auth()->user();
    $hour = now()->hour;
    $greeting = match (true) {
        $hour >= 4 && $hour < 11 => 'Selamat pagi',
        $hour >= 11 && $hour < 15 => 'Selamat siang',
        $hour >= 15 && $hour < 19 => 'Selamat sore',
        default => 'Selamat malam',
    };
    $firstName = explode(' ', $user->name)[0];

    $initials = fn ($name) => collect(explode(' ', trim($name)))
        ->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->join('');

    $completedCount = $modules->filter(fn ($m) => ($statusMap[$m->id] ?? '') === 'completed')->count();
    $totalCount = $modules->count();
@endphp

{{-- ── HERO: Welcome banner ─────────────────────────────────────── --}}
@section('hero')
<div class="bg-background">
    <div class="mx-auto max-w-[120rem] px-margin-mobile pt-6 sm:px-6 lg:px-margin-desktop">
        <section class="relative flex flex-col items-start justify-between gap-lg overflow-hidden rounded-xl bg-primary-container p-lg text-on-primary-container shadow-sm md:flex-row md:items-center md:p-xl">
            <div class="relative z-10 min-w-0">
                <h1 class="text-headline-lg">{{ $greeting }}, {{ $firstName }}! 👋</h1>
                <p class="mt-sm max-w-2xl text-body-md opacity-90">
                    @if($totalCount === 0)
                        Belum ada modul tersedia saat ini.
                    @elseif($completedCount === $totalCount)
                        Luar biasa! Kamu sudah menuntaskan semua modul. 🎉
                    @else
                        Mari lanjutkan perjalanan belajarmu hari ini. Setiap langkah kecil berarti.
                    @endif
                </p>
            </div>

            @if($totalCount > 0)
                <div class="relative z-10 w-full md:w-auto">
                    <div class="rounded-lg border border-white/10 bg-white/20 p-md backdrop-blur-sm md:w-64">
                        <div class="mb-xs flex items-center justify-between">
                            <span class="text-label-md font-bold uppercase tracking-wider">Progress Keseluruhan</span>
                            <span class="text-label-md font-bold">{{ $overallPct }}%</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-white/30">
                            <div class="h-2 rounded-full bg-white transition-all" style="width: {{ $overallPct }}%"></div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Decorative blur --}}
            <div class="absolute -right-16 -top-16 z-0 h-64 w-64 rounded-full bg-white/10 blur-3xl"></div>
        </section>
    </div>
</div>
@endsection

{{-- ── CONTENT ───────────────────────────────────────────────────── --}}
@section('content')

    {{-- Stat cards — mobile: kotak kecil 3 kolom; sm+: kartu penuh --}}
    <section class="grid grid-cols-3 gap-3 sm:gap-gutter">
        @php
            $stats = [
                ['label' => 'Modul Selesai', 'value' => $completedCount, 'sub' => 'dari '.$totalCount.' modul', 'icon' => 'heroicon-s-book-open', 'tile' => 'bg-sdg-4/10 text-sdg-4', 'url' => route('portal.modules.index')],
                ['label' => 'Poin Terkumpul', 'value' => number_format($user->total_xp), 'sub' => 'XP', 'icon' => 'heroicon-s-star', 'tile' => 'bg-sdg-7/10 text-sdg-7', 'url' => route('portal.xp')],
                ['label' => 'Peringkat', 'value' => '#'.$myRank, 'sub' => 'dari '.$totalWarga.' warga', 'icon' => 'heroicon-s-trophy', 'tile' => 'bg-sdg-10/10 text-sdg-10', 'url' => route('portal.leaderboard')],
            ];
        @endphp
        @foreach($stats as $s)
            <a href="{{ $s['url'] }}"
                class="group flex flex-col rounded-xl border border-outline-variant bg-surface p-3 shadow-sm transition-all hover:border-primary/30 hover:shadow-md active:translate-y-px sm:p-4">
                <div class="flex items-center gap-2 sm:mb-2.5 sm:gap-sm">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg {{ $s['tile'] }}">
                        <x-dynamic-component :component="$s['icon']" class="h-5 w-5" />
                    </div>
                    <h3 class="hidden text-sm font-semibold text-on-surface-variant sm:block">{{ $s['label'] }}</h3>
                    <x-heroicon-o-chevron-right class="ml-auto hidden h-5 w-5 text-outline-variant transition-all group-hover:translate-x-0.5 group-hover:text-primary sm:block" />
                </div>
                <div class="mt-2 sm:mt-0 sm:flex sm:items-baseline sm:gap-sm">
                    <span class="text-xl font-bold text-on-surface sm:text-2xl">{{ $s['value'] }}</span>
                    <span class="hidden text-label-md text-on-surface-variant sm:inline">{{ $s['sub'] }}</span>
                </div>
                {{-- Label ringkas khusus mobile (header label disembunyikan) --}}
                <span class="mt-0.5 line-clamp-2 text-[11px] font-medium leading-tight text-on-surface-variant sm:hidden">{{ $s['label'] }}</span>
            </a>
        @endforeach
    </section>

    {{-- Main grid --}}
    <div class="mt-xl grid grid-cols-1 gap-xl lg:grid-cols-3">

        {{-- Peringkat 5 teratas (kanan, sempit) — mobile: tampil setelah modul (order-2) --}}
        <section class="order-2 flex flex-col overflow-hidden rounded-xl border border-outline-variant bg-surface shadow-sm">
            <div class="flex items-center justify-between gap-2 border-b border-outline-variant bg-surface-bright p-lg">
                <h3 class="flex min-w-0 items-center gap-sm text-headline-sm text-on-surface">
                    <x-heroicon-s-trophy class="h-5 w-5 shrink-0 text-primary" />
                    <span class="truncate">Peringkat 5 Teratas</span>
                </h3>
                <a href="{{ route('portal.leaderboard') }}" class="shrink-0 text-label-md font-bold uppercase tracking-wider text-primary transition-colors hover:text-surface-tint">Lihat Semua</a>
            </div>

            @if($topUsers->isEmpty())
                <div class="flex flex-1 flex-col items-center justify-center px-lg py-12 text-center">
                    <x-heroicon-o-trophy class="h-10 w-10 text-outline-variant" />
                    <p class="mt-3 text-body-md text-on-surface-variant">Belum ada peringkat.</p>
                </div>
            @else
                <div class="flex flex-1 flex-col divide-y divide-outline-variant">
                    @foreach($topUsers as $w)
                        @php
                            $rank = $loop->iteration;
                            $isMe = $w->id === $user->id;
                            $rankColor = match ($rank) {
                                1 => 'text-sdg-2', 2 => 'text-on-surface-variant', 3 => 'text-sdg-12',
                                default => 'text-on-surface-variant',
                            };
                            $avatarBg = match ($rank) {
                                1 => 'bg-sdg-2 text-white', 2 => 'bg-surface-dim text-on-surface', 3 => 'bg-sdg-12 text-white',
                                default => 'bg-surface-dim text-on-surface',
                            };
                        @endphp
                        <div class="flex flex-1 items-center gap-md px-lg py-md transition-colors {{ $isMe ? 'bg-primary-fixed' : '' }}">
                            <span class="w-6 shrink-0 text-center text-sm font-bold {{ $isMe ? 'text-primary' : $rankColor }}">{{ $rank }}</span>
                            @if($w->avatarUrl())
                                <img src="{{ $w->avatarUrl() }}" alt="{{ $w->name }}" class="h-9 w-9 shrink-0 rounded-full object-cover">
                            @else
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full text-xs font-bold {{ $isMe ? 'bg-primary text-white' : $avatarBg }}">{{ $initials($w->name) }}</div>
                            @endif
                            <span class="min-w-0 flex-1 truncate text-sm font-semibold {{ $isMe ? 'text-primary' : 'text-on-surface' }}">{{ $w->name }}@if($isMe) <span class="text-xs font-normal">(Anda)</span>@endif</span>
                            <span class="shrink-0 text-sm font-bold {{ $isMe ? 'text-primary' : 'text-on-surface' }}">{{ number_format($w->total_xp) }} <span class="text-xs font-normal text-on-surface-variant">XP</span></span>
                        </div>
                    @endforeach

                    {{-- Baris posisi Anda (bila di luar 5 besar) → mengisi kartu jadi 6 baris. --}}
                    @if($myRank > $topUsers->count())
                        <div class="flex flex-1 items-center gap-md bg-primary-fixed px-lg py-md">
                            <span class="w-6 shrink-0 text-center text-sm font-bold text-primary">{{ $myRank }}</span>
                            @if($user->avatarUrl())
                                <img src="{{ $user->avatarUrl() }}" alt="{{ $user->name }}" class="h-9 w-9 shrink-0 rounded-full object-cover">
                            @else
                                <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-white">{{ $initials($user->name) }}</div>
                            @endif
                            <span class="min-w-0 flex-1 truncate text-sm font-semibold text-primary">{{ $user->name }} <span class="text-xs font-normal">(Anda)</span></span>
                            <span class="shrink-0 text-sm font-bold text-primary">{{ number_format($user->total_xp) }} <span class="text-xs font-normal text-on-surface-variant">XP</span></span>
                        </div>
                    @endif
                </div>
            @endif
        </section>

        {{-- Lanjutkan Belajar (kiri, lebar) — mobile: tampil duluan (order-1) --}}
        <section class="order-1 flex flex-col overflow-hidden rounded-xl border border-outline-variant bg-surface shadow-sm lg:col-span-2">
            <div class="flex items-center justify-between gap-2 border-b border-outline-variant bg-surface-bright p-lg">
                <h3 class="flex min-w-0 items-center gap-sm text-headline-sm text-on-surface">
                    <x-heroicon-s-academic-cap class="h-5 w-5 shrink-0 text-primary" />
                    <span class="truncate">Lanjutkan Belajar</span>
                </h3>
                <a href="{{ route('portal.modules.index') }}" class="shrink-0 text-label-md font-bold uppercase tracking-wider text-primary transition-colors hover:text-surface-tint">Lihat Semua</a>
            </div>

            @if($featured->isEmpty())
                <div class="flex flex-1 flex-col items-center justify-center px-lg py-12 text-center">
                    <x-heroicon-o-book-open class="h-10 w-10 text-outline-variant" />
                    <p class="mt-3 font-semibold text-on-surface">Belum ada modul</p>
                    <p class="mt-1 text-body-md text-on-surface-variant">Modul muncul setelah admin mempublikasikannya.</p>
                </div>
            @else
                <div class="flex flex-1 flex-col divide-y divide-outline-variant">
                    @foreach($featured as $module)
                        @php
                            $status = $statusMap[$module->id] ?? 'available';
                            $progress = $module->progress->first();
                            $pagesDone = count($progress?->halaman_selesai ?? []);
                            $pct = $module->pages_count > 0 ? (int) ($pagesDone / $module->pages_count * 100) : 0;
                            // Materi tuntas tapi kuis belum lulus → tampilkan sbg langkah tersisa.
                            $quizPending = $status === 'completed' && ($quizPendingMap[$module->id] ?? false);
                        @endphp
                        <a href="{{ route('portal.modules.show', $module) }}"
                            class="flex flex-1 items-center gap-md px-lg py-md transition-colors hover:bg-surface-container-lowest">
                            {{-- Thumbnail asli modul (fallback default ditangani coverUrl()) --}}
                            <div class="relative h-14 w-20 shrink-0 overflow-hidden rounded-lg ring-1 ring-outline-variant">
                                <img src="{{ $module->coverUrl() }}" alt="" loading="lazy"
                                    class="h-full w-full object-cover {{ $status === 'completed' && ! $quizPending ? 'opacity-90' : '' }}">
                                @if($quizPending)
                                    <span class="absolute right-1 top-1 flex h-5 w-5 items-center justify-center rounded-full bg-primary text-on-primary ring-2 ring-surface">
                                        <x-heroicon-s-clipboard-document-check class="h-3 w-3" />
                                    </span>
                                @elseif($status === 'completed')
                                    <span class="absolute right-1 top-1 flex h-5 w-5 items-center justify-center rounded-full bg-sdg-3 text-white ring-2 ring-surface">
                                        <x-heroicon-s-check class="h-3 w-3" />
                                    </span>
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-headline-sm font-medium text-on-surface">{{ $module->judul }}</p>
                                @if($status === 'in_progress' && $module->pages_count > 0)
                                    <div class="mt-2 flex items-center gap-sm">
                                        <div class="h-1.5 w-28 overflow-hidden rounded-full bg-surface-container-high">
                                            <div class="h-full rounded-full bg-primary" style="width: {{ $pct }}%"></div>
                                        </div>
                                        <span class="text-label-md text-on-surface-variant">{{ $pct }}%</span>
                                    </div>
                                @else
                                    <p class="mt-0.5 text-label-md font-medium {{ $quizPending ? 'text-primary' : ($status === 'completed' ? 'text-sdg-3' : 'text-sdg-14') }}">
                                        {{ $quizPending ? 'Kuis belum dikerjakan' : ($status === 'completed' ? 'Telah diselesaikan' : 'Belum dimulai') }}
                                    </p>
                                @endif
                            </div>
                            <x-heroicon-o-chevron-right class="h-5 w-5 shrink-0 text-outline-variant" />
                        </a>
                    @endforeach
                </div>
            @endif
        </section>
    </div>

@endsection
