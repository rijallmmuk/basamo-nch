@extends('portal.layouts.app')

@section('title', 'Semua Modul')

@section('content')
    {{-- Header --}}
    <div class="mb-5">
        <h1 class="text-xl font-bold text-on-surface sm:text-2xl">Daftar Modul</h1>
        <p class="mt-1 text-sm text-on-surface-variant">Pilih modul untuk mulai atau melanjutkan belajar.</p>
    </div>

    @if($modules->isEmpty())
        <x-portal.empty
            icon="heroicon-o-book-open"
            title="Belum ada modul tersedia"
            subtitle="Modul akan muncul setelah admin mempublikasikannya." />
    @else
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach($modules as $module)
                @php
                    $status = $statusMap[$module->id] ?? 'available';
                    $progress = $module->progress->first();
                    $pagesDone = count($progress?->halaman_selesai ?? []);
                    $pct = $module->pages_count > 0 ? (int) ($pagesDone / $module->pages_count * 100) : 0;
                    $locked = $status === 'locked';
                    // Materi tuntas tapi kuis modul ini belum lulus → masih ada langkah tersisa.
                    $quizPending = $status === 'completed' && ($quizPendingMap[$module->id] ?? false);
                @endphp

                <div class="flex flex-col overflow-hidden rounded-2xl border border-outline-variant bg-surface-container-lowest shadow-sm transition {{ $locked ? 'opacity-70' : 'hover:shadow-md' }}">

                    {{-- Cover --}}
                    <img src="{{ $module->coverUrl() }}" alt="" loading="lazy"
                        class="h-32 w-full object-cover {{ $locked ? 'grayscale' : '' }}">

                    <div class="flex flex-1 flex-col p-5">

                    {{-- Status modul (nomor urut internal tak ditampilkan ke warga —
                         urutannya sudah tercermin dari susunan kartu). --}}
                    <div class="mb-3 flex items-center justify-end gap-3">
                        <x-portal.status-badge :status="$status" />
                    </div>

                    {{-- Title + desc --}}
                    <h2 class="text-base font-bold leading-snug {{ $locked ? 'text-on-surface-variant' : 'text-on-surface' }}">
                        {{ $module->judul }}
                    </h2>
                    @if($module->deskripsi)
                        <p class="mt-1.5 line-clamp-2 text-sm text-on-surface-variant">{{ strip_tags($module->deskripsi) }}</p>
                    @endif

                    @if($module->estimasi_menit)
                        <p class="mt-2 inline-flex items-center gap-1.5 text-xs font-medium text-on-surface-variant">
                            <x-heroicon-o-clock class="h-4 w-4" />
                            ± {{ $module->estimasi_menit }} menit
                        </p>
                    @endif

                    {{-- Prerequisite warning --}}
                    @if($locked && $module->prerequisite)
                        <p class="mt-3 flex items-start gap-1.5 rounded-lg bg-secondary-container px-3 py-2 text-xs font-medium text-on-secondary-container">
                            <x-heroicon-s-lock-closed class="mt-px h-3.5 w-3.5 shrink-0" />
                            Selesaikan dulu: {{ $module->prerequisite->judul }}
                        </p>
                    @endif

                    {{-- Progress --}}
                    @if($status === 'in_progress' && $module->pages_count > 0)
                        <div class="mt-4">
                            <div class="mb-1.5 flex items-center justify-between text-xs">
                                <span class="font-medium text-on-surface-variant">{{ $pagesDone }}/{{ $module->pages_count }} materi</span>
                                <span class="font-bold text-primary">{{ $pct }}%</span>
                            </div>
                            <div class="h-1.5 overflow-hidden rounded-full bg-surface-container-high">
                                <div class="h-full rounded-full bg-primary transition-all" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @else
                        <p class="mt-3 inline-flex items-center gap-1.5 text-xs text-on-surface-variant">
                            <x-heroicon-o-document-text class="h-4 w-4" />
                            {{ $module->pages_count }} materi
                        </p>
                    @endif

                    {{-- CTA --}}
                    <div class="mt-4 pt-1">
                        @if($locked)
                            <span class="flex w-full items-center justify-center gap-1.5 rounded-xl bg-surface-container-high px-4 py-2.5 text-sm font-semibold text-outline">
                                <x-heroicon-s-lock-closed class="h-4 w-4" />
                                Terkunci
                            </span>
                        @else
                            <a href="{{ route('portal.modules.show', $module) }}"
                                class="flex w-full items-center justify-center gap-1.5 rounded-xl px-4 py-2.5 text-sm font-bold transition-colors
                                    @if($status === 'completed' && ! $quizPending) bg-surface-container-high text-on-surface-variant hover:bg-surface-container-highest
                                    @else bg-primary text-on-primary hover:bg-surface-tint @endif">
                                @if($quizPending)
                                    <x-heroicon-s-clipboard-document-check class="h-4 w-4" /> Kerjakan Kuis
                                @elseif($status === 'completed')
                                    <x-heroicon-o-eye class="h-4 w-4" /> Lihat Kembali
                                @elseif($status === 'in_progress')
                                    <x-heroicon-o-play class="h-4 w-4" /> Lanjutkan
                                @else
                                    <x-heroicon-o-play class="h-4 w-4" /> Mulai Belajar
                                @endif
                            </a>
                        @endif
                    </div>
                    </div>{{-- /padded content --}}
                </div>
            @endforeach
        </div>
    @endif
@endsection
