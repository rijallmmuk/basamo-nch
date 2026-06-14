@extends('portal.layouts.app')

@section('title', 'Beranda')
@section('main-class', 'py-6 pb-28 md:pb-10')

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

    $completedCount = $modules->filter(fn ($m) => ($statusMap[$m->id] ?? '') === 'completed')->count();
    $inProgressCount = $modules->filter(fn ($m) => ($statusMap[$m->id] ?? '') === 'in_progress')->count();
    $totalCount = $modules->count();

    // Modul yang dapat dilanjutkan (prioritas tertinggi dari controller)
    $spotlight = $featured->first(fn ($m) => in_array($statusMap[$m->id] ?? '', ['in_progress', 'available']));
@endphp

{{-- ── HERO ──────────────────────────────────────────────────────── --}}
@section('hero')
<div class="bg-gradient-to-br from-indigo-700 via-indigo-700 to-violet-700">
    <div class="mx-auto max-w-5xl px-4 py-8 sm:px-6 sm:py-10 lg:px-8">
        <p class="text-xs font-semibold uppercase tracking-wider text-indigo-200/90">
            {{ $user->nagari?->nama ?? 'Basamo NCH' }}
        </p>
        <h1 class="mt-1.5 text-2xl font-bold text-white sm:text-3xl">
            {{ $greeting }}, {{ $firstName }}! 👋
        </h1>
        <p class="mt-2 max-w-prose text-sm text-indigo-100">
            @if($totalCount === 0)
                Belum ada modul tersedia saat ini.
            @elseif($completedCount === $totalCount)
                Luar biasa! Kamu sudah menuntaskan semua {{ $totalCount }} modul. 🎉
            @elseif($completedCount > 0)
                Sudah <span class="font-semibold text-white">{{ $completedCount }}</span> dari {{ $totalCount }} modul selesai. Lanjutkan momentummu!
            @elseif($inProgressCount > 0)
                Kamu sedang mengerjakan {{ $inProgressCount }} modul. Tetap semangat!
            @else
                Yuk mulai belajar dan tingkatkan pengetahuanmu hari ini.
            @endif
        </p>

        {{-- Stat chips --}}
        @if($totalCount > 0)
            <div class="mt-5 grid grid-cols-3 gap-2 sm:max-w-md sm:gap-3">
                <div class="rounded-xl bg-white/10 px-3 py-2.5 ring-1 ring-white/15 backdrop-blur">
                    <p class="text-xl font-bold text-white sm:text-2xl">{{ $completedCount }}</p>
                    <p class="text-[11px] font-medium text-indigo-100">Selesai</p>
                </div>
                <div class="rounded-xl bg-white/10 px-3 py-2.5 ring-1 ring-white/15 backdrop-blur">
                    <p class="text-xl font-bold text-white sm:text-2xl">{{ $inProgressCount }}</p>
                    <p class="text-[11px] font-medium text-indigo-100">Berjalan</p>
                </div>
                <div class="rounded-xl bg-white/10 px-3 py-2.5 ring-1 ring-white/15 backdrop-blur">
                    <p class="text-xl font-bold text-white sm:text-2xl">{{ $totalCount }}</p>
                    <p class="text-[11px] font-medium text-indigo-100">Total Modul</p>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection

{{-- ── CONTENT ───────────────────────────────────────────────────── --}}
@section('content')

    @if($featured->isEmpty())
        <x-portal.empty
            icon="heroicon-o-book-open"
            title="Belum ada modul tersedia"
            subtitle="Modul akan muncul setelah admin mempublikasikannya." />
    @else

        {{-- Spotlight: lanjutkan belajar --}}
        @if($spotlight)
            @php
                $sStatus = $statusMap[$spotlight->id] ?? 'available';
                $sProgress = $spotlight->progress->first();
                $sDone = count($sProgress?->pages_completed ?? []);
                $sPct = $spotlight->pages_count > 0 ? (int) ($sDone / $spotlight->pages_count * 100) : 0;
            @endphp
            <a href="{{ route('portal.modules.show', $spotlight) }}"
                class="group relative block overflow-hidden rounded-2xl border border-indigo-100 bg-white p-5 shadow-sm transition hover:shadow-md sm:p-6">
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wide text-indigo-500">
                            {{ $sStatus === 'in_progress' ? 'Lanjutkan belajar' : 'Mulai belajar' }}
                        </p>
                        <h2 class="mt-1 text-lg font-bold leading-snug text-gray-900 sm:text-xl">{{ $spotlight->title }}</h2>
                    </div>
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-indigo-600 text-white shadow-sm transition group-hover:bg-indigo-700">
                        <x-heroicon-s-play class="h-5 w-5" />
                    </span>
                </div>

                @if($sStatus === 'in_progress' && $spotlight->pages_count > 0)
                    <div class="mt-4">
                        <div class="mb-1.5 flex items-center justify-between text-xs">
                            <span class="font-medium text-gray-500">{{ $sDone }} dari {{ $spotlight->pages_count }} materi</span>
                            <span class="font-bold text-indigo-600">{{ $sPct }}%</span>
                        </div>
                        <div class="h-2 overflow-hidden rounded-full bg-gray-100">
                            <div class="h-full rounded-full bg-indigo-500 transition-all" style="width: {{ $sPct }}%"></div>
                        </div>
                    </div>
                @endif
            </a>
        @endif

        {{-- Aktivitas belajar --}}
        <div class="mt-6 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
            <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                <div class="flex items-center gap-2.5">
                    <x-heroicon-s-academic-cap class="h-5 w-5 text-indigo-500" />
                    <h2 class="font-semibold text-gray-800">Aktivitas Belajar</h2>
                </div>
                <a href="{{ route('portal.modules.index') }}"
                    class="text-sm font-medium text-indigo-600 transition-colors hover:text-indigo-800">
                    Lihat semua
                </a>
            </div>

            <div class="divide-y divide-gray-100">
                @foreach($featured as $module)
                    @php
                        $status = $statusMap[$module->id] ?? 'available';
                        $progress = $module->progress->first();
                        $pagesDone = count($progress?->pages_completed ?? []);
                        $pct = $module->pages_count > 0 ? (int) ($pagesDone / $module->pages_count * 100) : 0;
                        $locked = $status === 'locked';
                    @endphp

                    <a @if(! $locked) href="{{ route('portal.modules.show', $module) }}" @endif
                        class="flex items-center gap-4 px-5 py-4 transition-colors {{ $locked ? 'cursor-not-allowed opacity-60' : 'hover:bg-slate-50' }}">

                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl
                            @if($status === 'completed') bg-emerald-100 text-emerald-600
                            @elseif($status === 'in_progress') bg-indigo-100 text-indigo-600
                            @elseif($locked) bg-gray-100 text-gray-400
                            @else bg-sky-50 text-sky-600 @endif">
                            @if($status === 'completed')
                                <x-heroicon-s-check class="h-5 w-5" />
                            @elseif($locked)
                                <x-heroicon-s-lock-closed class="h-4 w-4" />
                            @else
                                <x-heroicon-s-book-open class="h-5 w-5" />
                            @endif
                        </span>

                        <div class="min-w-0 flex-1">
                            <p class="truncate text-[15px] font-semibold {{ $locked ? 'text-gray-500' : 'text-gray-900' }}">
                                {{ $module->title }}
                            </p>
                            @if($status === 'in_progress' && $module->pages_count > 0)
                                <div class="mt-2 flex items-center gap-2">
                                    <div class="h-1.5 w-28 overflow-hidden rounded-full bg-gray-100">
                                        <div class="h-full rounded-full bg-indigo-500" style="width: {{ $pct }}%"></div>
                                    </div>
                                    <span class="text-xs text-gray-400">{{ $pct }}%</span>
                                </div>
                            @else
                                <p class="mt-0.5 text-xs font-medium
                                    @if($status === 'completed') text-emerald-600
                                    @elseif($locked) text-gray-400
                                    @else text-sky-600 @endif">
                                    @if($status === 'completed') Telah diselesaikan
                                    @elseif($locked) Terkunci
                                    @else Belum dimulai @endif
                                </p>
                            @endif
                        </div>

                        @unless($locked)
                            <x-heroicon-o-chevron-right class="h-5 w-5 shrink-0 text-gray-300" />
                        @endunless
                    </a>
                @endforeach
            </div>
        </div>

    @endif

@endsection
