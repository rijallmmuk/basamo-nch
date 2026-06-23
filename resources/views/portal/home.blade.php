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
    <div class="mx-auto max-w-5xl px-margin-mobile pt-6 sm:px-6 lg:px-margin-desktop">
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

    {{-- Stat cards --}}
    <section class="grid grid-cols-1 gap-gutter md:grid-cols-3">
        @php
            $stats = [
                ['label' => 'Modul Selesai', 'value' => $completedCount, 'sub' => 'dari '.$totalCount.' modul', 'icon' => 'heroicon-s-book-open', 'tile' => 'bg-sdg-4/10 text-sdg-4'],
                ['label' => 'Poin Terkumpul', 'value' => number_format($user->total_xp), 'sub' => 'XP', 'icon' => 'heroicon-s-star', 'tile' => 'bg-sdg-7/10 text-sdg-7'],
                ['label' => 'Peringkat Desa', 'value' => '#'.$myRank, 'sub' => 'dari '.$totalWarga.' warga', 'icon' => 'heroicon-s-trophy', 'tile' => 'bg-sdg-10/10 text-sdg-10'],
            ];
        @endphp
        @foreach($stats as $s)
            <div class="rounded-xl border border-outline-variant bg-surface p-lg shadow-sm transition-shadow hover:shadow-md">
                <div class="mb-md flex items-center gap-sm">
                    <div class="flex h-10 w-10 items-center justify-center rounded-lg {{ $s['tile'] }}">
                        <x-dynamic-component :component="$s['icon']" class="h-5 w-5" />
                    </div>
                    <h3 class="text-headline-sm text-on-surface-variant">{{ $s['label'] }}</h3>
                </div>
                <div class="flex items-baseline gap-sm">
                    <span class="text-metric-lg text-on-surface">{{ $s['value'] }}</span>
                    <span class="text-label-md text-on-surface-variant">{{ $s['sub'] }}</span>
                </div>
            </div>
        @endforeach
    </section>

    {{-- Main grid --}}
    <div class="mt-xl grid grid-cols-1 gap-xl lg:grid-cols-3">

        {{-- Leaderboard --}}
        <section class="flex flex-col overflow-hidden rounded-xl border border-outline-variant bg-surface shadow-sm lg:col-span-2">
            <div class="flex items-center justify-between border-b border-outline-variant bg-surface-bright p-lg">
                <h3 class="flex items-center gap-sm text-headline-md text-on-surface">
                    <x-heroicon-s-trophy class="h-5 w-5 text-primary" />
                    Peringkat Warga Desa
                </h3>
                <a href="{{ route('portal.leaderboard') }}" class="text-label-md font-bold uppercase tracking-wider text-primary transition-colors hover:text-surface-tint">Lihat Semua</a>
            </div>

            @if($topUsers->isEmpty())
                <div class="px-lg py-12 text-center">
                    <x-heroicon-o-trophy class="mx-auto h-10 w-10 text-outline-variant" />
                    <p class="mt-3 text-body-md text-on-surface-variant">Belum ada peringkat.</p>
                </div>
            @else
                <div class="flex-1 overflow-x-auto">
                    <table class="w-full border-collapse text-left">
                        <thead>
                            <tr class="border-b border-outline-variant bg-surface-container-lowest text-label-md text-on-surface-variant">
                                <th class="w-16 p-md text-center font-medium">Posisi</th>
                                <th class="p-md font-medium">Warga</th>
                                <th class="p-md text-right font-medium">Poin</th>
                            </tr>
                        </thead>
                        <tbody class="text-body-md">
                            @foreach($topUsers as $w)
                                @php
                                    $rank = $loop->iteration;
                                    $isMe = $w->id === $user->id;
                                    $rankColor = match ($rank) {
                                        1 => 'text-sdg-2', 2 => 'text-outline', 3 => 'text-sdg-12',
                                        default => 'text-on-surface-variant',
                                    };
                                    $avatarBg = match ($rank) {
                                        1 => 'bg-sdg-2 text-white', 2 => 'bg-surface-dim text-on-surface', 3 => 'bg-sdg-12 text-white',
                                        default => 'bg-surface-dim text-on-surface',
                                    };
                                @endphp
                                <tr class="{{ $isMe ? 'bg-primary-fixed hover:bg-primary-fixed-dim' : 'border-b border-outline-variant hover:bg-surface-container-lowest' }} transition-colors">
                                    <td class="p-md text-center font-bold {{ $isMe ? 'text-primary' : $rankColor }}">#{{ $rank }}</td>
                                    <td class="p-md">
                                        <div class="flex items-center gap-md">
                                            <div class="flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold {{ $isMe ? 'bg-primary text-white' : $avatarBg }}">{{ $initials($w->name) }}</div>
                                            <span class="{{ $isMe ? 'font-bold text-primary' : 'font-medium' }}">{{ $w->name }}@if($isMe) <span class="font-normal">(Anda)</span>@endif</span>
                                        </div>
                                    </td>
                                    <td class="p-md text-right {{ $isMe ? 'font-bold text-primary' : 'font-medium' }}">{{ number_format($w->total_xp) }}</td>
                                </tr>
                            @endforeach
                            @if($myRank > $topUsers->count())
                                <tr class="bg-primary-fixed">
                                    <td class="p-md text-center font-bold text-primary">#{{ $myRank }}</td>
                                    <td class="p-md">
                                        <div class="flex items-center gap-md">
                                            <div class="flex h-8 w-8 items-center justify-center rounded-full bg-primary text-xs font-bold text-white">{{ $initials($user->name) }}</div>
                                            <span class="font-bold text-primary">{{ $user->name }} <span class="font-normal">(Anda)</span></span>
                                        </div>
                                    </td>
                                    <td class="p-md text-right font-bold text-primary">{{ number_format($user->total_xp) }}</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            @endif
        </section>

        {{-- Lanjutkan Belajar --}}
        <section class="flex flex-col overflow-hidden rounded-xl border border-outline-variant bg-surface shadow-sm">
            <div class="flex items-center justify-between border-b border-outline-variant bg-surface-bright p-lg">
                <h3 class="flex items-center gap-sm text-headline-md text-on-surface">
                    <x-heroicon-s-academic-cap class="h-5 w-5 text-primary" />
                    Lanjutkan Belajar
                </h3>
                <a href="{{ route('portal.modules.index') }}" class="text-label-md font-bold uppercase tracking-wider text-primary transition-colors hover:text-surface-tint">Semua</a>
            </div>

            @if($featured->isEmpty())
                <div class="px-lg py-12 text-center">
                    <x-heroicon-o-book-open class="mx-auto h-10 w-10 text-outline-variant" />
                    <p class="mt-3 font-semibold text-on-surface">Belum ada modul</p>
                    <p class="mt-1 text-body-md text-on-surface-variant">Modul muncul setelah admin mempublikasikannya.</p>
                </div>
            @else
                <div class="divide-y divide-outline-variant">
                    @foreach($featured as $module)
                        @php
                            $status = $statusMap[$module->id] ?? 'available';
                            $progress = $module->progress->first();
                            $pagesDone = count($progress?->halaman_selesai ?? []);
                            $pct = $module->pages_count > 0 ? (int) ($pagesDone / $module->pages_count * 100) : 0;
                            $locked = $status === 'locked';
                            $tile = match ($status) {
                                'completed' => 'bg-sdg-3/10 text-sdg-3',
                                'in_progress' => 'bg-primary/10 text-primary',
                                'locked' => 'bg-surface-container-high text-outline',
                                default => 'bg-sdg-14/10 text-sdg-14',
                            };
                        @endphp
                        <a @if(! $locked) href="{{ route('portal.modules.show', $module) }}" @endif
                            class="flex items-center gap-md px-lg py-md transition-colors {{ $locked ? 'cursor-not-allowed opacity-60' : 'hover:bg-surface-container-lowest' }}">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg {{ $tile }}">
                                @if($status === 'completed')
                                    <x-heroicon-s-check class="h-5 w-5" />
                                @elseif($locked)
                                    <x-heroicon-s-lock-closed class="h-4 w-4" />
                                @else
                                    <x-heroicon-s-book-open class="h-5 w-5" />
                                @endif
                            </span>
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-headline-sm font-medium {{ $locked ? 'text-on-surface-variant' : 'text-on-surface' }}">{{ $module->judul }}</p>
                                @if($status === 'in_progress' && $module->pages_count > 0)
                                    <div class="mt-2 flex items-center gap-sm">
                                        <div class="h-1.5 w-28 overflow-hidden rounded-full bg-surface-container-high">
                                            <div class="h-full rounded-full bg-primary" style="width: {{ $pct }}%"></div>
                                        </div>
                                        <span class="text-label-md text-on-surface-variant">{{ $pct }}%</span>
                                    </div>
                                @else
                                    <p class="mt-0.5 text-label-md font-medium
                                        @if($status === 'completed') text-sdg-3
                                        @elseif($locked) text-outline
                                        @else text-sdg-14 @endif">
                                        @if($status === 'completed') Telah diselesaikan
                                        @elseif($locked) Terkunci
                                        @else Belum dimulai @endif
                                    </p>
                                @endif
                            </div>
                            @unless($locked)
                                <x-heroicon-o-chevron-right class="h-5 w-5 shrink-0 text-outline-variant" />
                            @endunless
                        </a>
                    @endforeach
                </div>
            @endif
        </section>
    </div>

@endsection
