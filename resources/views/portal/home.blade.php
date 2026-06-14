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

    $completedCount = $modules->filter(fn ($m) => ($statusMap[$m->id] ?? '') === 'completed')->count();
    $inProgressCount = $modules->filter(fn ($m) => ($statusMap[$m->id] ?? '') === 'in_progress')->count();
    $totalCount = $modules->count();
@endphp

{{-- ── HERO ──────────────────────────────────────────────────────── --}}
@section('hero')
<div class="bg-slate-50">
    <div class="mx-auto max-w-5xl px-4 pt-6 sm:px-6 lg:px-8">
        <div class="overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-700 via-indigo-700 to-violet-700 p-6 sm:p-8">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <h1 class="text-2xl font-bold text-white sm:text-3xl">{{ $greeting }}, {{ $firstName }}! 👋</h1>
                    <p class="mt-2 max-w-prose text-sm text-indigo-100">
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
                    <div class="w-full shrink-0 rounded-xl bg-white/10 p-4 ring-1 ring-white/15 backdrop-blur sm:w-64">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-semibold uppercase tracking-wider text-indigo-200">Progres Keseluruhan</span>
                            <span class="text-sm font-bold text-white">{{ $overallPct }}%</span>
                        </div>
                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-white/20">
                            <div class="h-full rounded-full bg-white transition-all" style="width: {{ $overallPct }}%"></div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

{{-- ── CONTENT ───────────────────────────────────────────────────── --}}
@section('content')

    {{-- Stat cards --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        @php
            $stats = [
                ['label' => 'Modul Selesai', 'value' => $completedCount, 'icon' => 'heroicon-s-check-badge', 'bg' => 'bg-emerald-100', 'fg' => 'text-emerald-600'],
                ['label' => 'XP Terkumpul', 'value' => number_format($user->total_points), 'icon' => 'heroicon-s-sparkles', 'bg' => 'bg-amber-100', 'fg' => 'text-amber-600'],
                ['label' => 'Peringkat Nagari', 'value' => '#'.$myRank, 'sub' => 'dari '.$totalWarga.' warga', 'icon' => 'heroicon-s-trophy', 'bg' => 'bg-indigo-100', 'fg' => 'text-indigo-600'],
            ];
        @endphp
        @foreach($stats as $s)
            <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm">
                <div class="flex items-center gap-2.5">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg {{ $s['bg'] }}">
                        <x-dynamic-component :component="$s['icon']" class="h-5 w-5 {{ $s['fg'] }}" />
                    </span>
                    <span class="text-sm font-medium text-gray-500">{{ $s['label'] }}</span>
                </div>
                <p class="mt-3 text-3xl font-bold text-gray-900">
                    {{ $s['value'] }}
                    @if(! empty($s['sub']))<span class="text-sm font-normal text-gray-400">{{ $s['sub'] }}</span>@endif
                </p>
            </div>
        @endforeach
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-5">

        {{-- Lanjutkan Belajar --}}
        <div class="lg:col-span-3">
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <div class="flex items-center gap-2.5">
                        <x-heroicon-s-academic-cap class="h-5 w-5 text-indigo-500" />
                        <h2 class="font-semibold text-gray-800">Lanjutkan Belajar</h2>
                    </div>
                    <a href="{{ route('portal.modules.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Lihat semua</a>
                </div>

                @if($featured->isEmpty())
                    <div class="px-5 py-12 text-center">
                        <x-heroicon-o-book-open class="mx-auto h-10 w-10 text-gray-300" />
                        <p class="mt-3 font-semibold text-gray-700">Belum ada modul</p>
                        <p class="mt-1 text-sm text-gray-400">Modul akan muncul setelah admin mempublikasikannya.</p>
                    </div>
                @else
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
                                    <p class="truncate text-[15px] font-semibold {{ $locked ? 'text-gray-500' : 'text-gray-900' }}">{{ $module->title }}</p>
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
                @endif
            </div>
        </div>

        {{-- Peringkat XP Nagari --}}
        <div class="lg:col-span-2">
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm">
                <div class="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <div class="flex items-center gap-2.5">
                        <x-heroicon-s-trophy class="h-5 w-5 text-amber-500" />
                        <h2 class="font-semibold text-gray-800">Peringkat XP</h2>
                    </div>
                    <a href="{{ route('portal.leaderboard') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">Lihat semua</a>
                </div>

                @if($topUsers->isEmpty())
                    <div class="px-5 py-12 text-center">
                        <x-heroicon-o-trophy class="mx-auto h-10 w-10 text-gray-300" />
                        <p class="mt-3 text-sm text-gray-400">Belum ada peringkat.</p>
                    </div>
                @else
                    <ul class="divide-y divide-gray-100">
                        @foreach($topUsers as $w)
                            @php
                                $rank = $loop->iteration;
                                $isMe = $w->id === $user->id;
                                $medal = match ($rank) { 1 => 'bg-amber-400 text-white', 2 => 'bg-gray-300 text-white', 3 => 'bg-orange-300 text-white', default => 'bg-gray-100 text-gray-500' };
                            @endphp
                            <li class="flex items-center gap-3 px-5 py-3 {{ $isMe ? 'bg-indigo-50/60' : '' }}">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold {{ $medal }}">{{ $rank }}</span>
                                <x-portal.avatar :name="$w->name" :variant="$isMe ? 'solid' : 'gray'" size="sm" />
                                <p class="min-w-0 flex-1 truncate text-sm font-semibold {{ $isMe ? 'text-indigo-700' : 'text-gray-800' }}">
                                    {{ $w->name }}@if($isMe) <span class="text-xs font-normal text-indigo-500">(kamu)</span>@endif
                                </p>
                                <span class="shrink-0 text-sm font-bold text-indigo-600">{{ number_format($w->total_points) }}</span>
                            </li>
                        @endforeach
                    </ul>
                    @if($myRank > $topUsers->count())
                        <div class="border-t border-gray-100 bg-indigo-50/40 px-5 py-3">
                            <div class="flex items-center gap-3">
                                <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-indigo-100 text-xs font-bold text-indigo-600">{{ $myRank }}</span>
                                <x-portal.avatar :name="$user->name" variant="solid" size="sm" />
                                <p class="min-w-0 flex-1 truncate text-sm font-semibold text-indigo-700">{{ $user->name }} <span class="text-xs font-normal text-indigo-500">(kamu)</span></p>
                                <span class="shrink-0 text-sm font-bold text-indigo-600">{{ number_format($user->total_points) }}</span>
                            </div>
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>

@endsection
