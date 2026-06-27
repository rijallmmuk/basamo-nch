@extends('portal.layouts.app')

@section('title', 'Riwayat Poin (XP)')

@section('content')
    <x-portal.breadcrumb :items="[
        ['label' => 'Beranda', 'url' => route('portal.home')],
        ['label' => 'Riwayat Poin'],
    ]" />

    <div class="mb-5">
        <h1 class="text-xl font-bold text-on-surface sm:text-2xl">Riwayat Poin (XP)</h1>
        <p class="mt-1 text-sm text-on-surface-variant">Rincian poin yang kamu kumpulkan dari belajar.</p>
    </div>

    {{-- Ringkasan total (gaya selaras banner "Posisi Anda" di halaman Peringkat) --}}
    <div class="relative mb-5 flex items-center justify-between gap-4 overflow-hidden rounded-2xl bg-primary p-5 text-on-primary shadow-sm">
        <div class="relative z-10 flex items-center gap-3">
            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-white/20">
                <x-heroicon-s-star class="h-6 w-6 text-secondary-container" />
            </span>
            <div>
                <p class="text-sm text-white/70">Total Poin Anda</p>
                <p class="text-3xl font-bold">{{ number_format($totalXp) }} <span class="text-base font-medium text-white/70">XP</span></p>
            </div>
        </div>
        <div class="relative z-10 hidden shrink-0 space-y-0.5 text-right text-xs text-white/70 sm:block">
            <p><span class="font-bold text-white">+50</span> selesai modul</p>
            <p><span class="font-bold text-white">+100</span> lulus kuis</p>
            <p><span class="font-bold text-white">+20</span> ikut diskusi</p>
        </div>
        <div class="pointer-events-none absolute -right-12 -top-12 z-0 h-48 w-48 rounded-full bg-white/10 blur-3xl"></div>
    </div>

    @if($logs->isEmpty())
        <x-portal.empty
            icon="heroicon-o-star"
            title="Belum ada poin"
            subtitle="Selesaikan modul, lulus kuis, atau ikut diskusi untuk mulai mengumpulkan XP.">
            <x-portal.button :href="route('portal.modules.index')">
                <x-heroicon-o-play class="h-4 w-4" /> Mulai Belajar
            </x-portal.button>
        </x-portal.empty>
    @else
        <x-portal.card :padded="false">
            <div class="divide-y divide-outline-variant">
                @foreach($logs as $log)
                    @php
                        // Tiap entri terkait modul: kuis via map quiz→module, lainnya langsung.
                        $moduleId = $log->sumber === 'quiz' ? ($quizModuleMap[$log->sumber_id] ?? null) : $log->sumber_id;
                        $module = $moduleId ? ($modules[$moduleId] ?? null) : null;
                        $meta = match ($log->sumber) {
                            'quiz' => ['icon' => 'heroicon-s-clipboard-document-check', 'tile' => 'bg-sdg-3/10 text-sdg-3', 'label' => 'Lulus kuis', 'title' => 'Kuis: '.($module?->judul ?? '')],
                            'module' => ['icon' => null, 'tile' => null, 'label' => 'Menyelesaikan modul', 'title' => $module?->judul ?? 'Modul'],
                            'discussion' => ['icon' => 'heroicon-s-chat-bubble-left-right', 'tile' => 'bg-secondary-container text-on-secondary-container', 'label' => 'Berpartisipasi diskusi', 'title' => $module?->judul ?? 'Modul'],
                            default => ['icon' => 'heroicon-s-star', 'tile' => 'bg-surface-container-high text-on-surface-variant', 'label' => 'Poin', 'title' => 'Aktivitas'],
                        };
                    @endphp
                    <div class="flex items-center gap-4 px-5 py-4">
                        @if($log->sumber === 'module')
                            {{-- Hanya entri "menyelesaikan modul": cover modul asli (fallback default), ukuran tetap. --}}
                            <img src="{{ $module ? $module->coverUrl() : asset('images/default-module-cover.svg') }}" alt="" loading="lazy"
                                class="h-11 w-11 shrink-0 rounded-xl object-cover ring-1 ring-outline-variant">
                        @else
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $meta['tile'] }}">
                                <x-dynamic-component :component="$meta['icon']" class="h-6 w-6" />
                            </span>
                        @endif
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-semibold text-on-surface">{{ $meta['title'] }}</p>
                            <p class="mt-0.5 text-xs text-on-surface-variant">{{ $meta['label'] }} · {{ $log->created_at->diffForHumans() }}</p>
                        </div>
                        <span class="shrink-0 rounded-full bg-sdg-3/10 px-3 py-1 text-sm font-bold text-sdg-3">+{{ $log->jumlah }} XP</span>
                    </div>
                @endforeach
            </div>
        </x-portal.card>

        <div class="mt-5">
            {{ $logs->links() }}
        </div>
    @endif
@endsection
