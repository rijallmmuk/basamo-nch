@extends('portal.layouts.app')

@section('title', $page->judul)
@section('main-class', 'py-6 pb-28')

@php
    $totalPages = $pages->count();
    $done = count($pagesCompleted);
    $pct = $totalPages > 0 ? (int) ($done / $totalPages * 100) : 0;
    $currentIdx = $pages->search(fn ($p) => $p->id === $page->id) + 1;
    // Indeks materi pertama yang belum selesai = batas akses (materi setelahnya terkunci).
    $firstIncomplete = $pages->search(fn ($p) => ! in_array($p->id, $pagesCompleted));
@endphp

{{-- Reader nav menggantikan bottom nav mobile --}}
@section('bottom-navigation')
<div class="fixed inset-x-0 bottom-0 z-30 border-t border-outline-variant bg-surface-container-lowest shadow-[0_-1px_3px_rgba(0,0,0,0.04)] lg:left-64">
    <div class="h-1 bg-surface-container-high">
        <div class="h-full bg-primary transition-all" style="width: {{ $pct }}%"></div>
    </div>
    <div class="mx-auto flex h-16 max-w-[120rem] items-center justify-between gap-3 px-4 sm:px-6 lg:px-margin-desktop">
        {{-- KIRI: Sebelumnya (bukan halaman pertama) / Ke Modul (halaman pertama) --}}
        <a href="{{ $prevPage ? route('portal.modules.pages.show', [$module, $prevPage]) : route('portal.modules.show', $module) }}"
            class="flex items-center gap-2 rounded-xl border border-outline-variant bg-surface-container-lowest px-4 py-2.5 text-sm font-semibold text-on-surface-variant transition-colors hover:bg-surface-container-low">
            <x-heroicon-o-arrow-left class="h-4 w-4" />
            <span class="hidden sm:inline">{{ $prevPage ? 'Sebelumnya' : 'Ke Modul' }}</span>
        </a>

        {{-- Indikator progres materi (redundan dgn bar atas → sembunyikan di HP, cegah luber) --}}
        <div class="hidden items-center gap-1.5 sm:flex">
            @foreach($pages as $p)
                @php $isDone = in_array($p->id, $pagesCompleted); $isCurrent = $p->id === $page->id; @endphp
                <span class="rounded-full transition-all
                    @if($isCurrent) h-2 w-6 bg-primary
                    @elseif($isDone) h-2 w-2 bg-sdg-3
                    @else h-2 w-2 bg-surface-dim @endif"></span>
            @endforeach
        </div>

        {{-- KANAN: Selanjutnya (ada materi berikutnya) / Selesaikan (materi terakhir).
             Bila belum ditandai selesai → POST menandai selesai lebih dulu, lalu lanjut. --}}
        @php
            $currentDone = in_array($page->id, $pagesCompleted);
            $isFinish = ! $nextPage;
            $fwdLabel = $isFinish ? 'Selesaikan' : 'Selanjutnya';
            $fwdClass = $isFinish ? 'bg-sdg-3 hover:opacity-90' : 'bg-primary hover:bg-surface-tint';
        @endphp
        @if($currentDone)
            <a href="{{ $isFinish ? route('portal.modules.show', $module) : route('portal.modules.pages.show', [$module, $nextPage]) }}"
                class="flex items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-bold text-white transition-colors {{ $fwdClass }}">
                <span>{{ $fwdLabel }}</span>
                <x-dynamic-component :component="$isFinish ? 'heroicon-s-check-circle' : 'heroicon-o-arrow-right'" class="h-4 w-4" />
            </a>
        @else
            <form method="POST" action="{{ route('portal.modules.pages.complete', [$module, $page]) }}">
                @csrf
                <button type="submit"
                    class="flex items-center gap-2 rounded-xl px-5 py-2.5 text-sm font-bold text-white transition-colors {{ $fwdClass }}">
                    <span>{{ $fwdLabel }}</span>
                    <x-dynamic-component :component="$isFinish ? 'heroicon-s-check-circle' : 'heroicon-o-arrow-right'" class="h-4 w-4" />
                </button>
            </form>
        @endif
    </div>
</div>
@endsection

@section('content')
    {{-- Breadcrumb --}}
    <x-portal.breadcrumb :items="[
        ['label' => $module->judul, 'url' => route('portal.modules.show', $module)],
        ['label' => $page->judul],
    ]" />

    @if($c = session('celebrate'))
        <script>
            window.addEventListener('load', () => {
                window.fireConfetti?.();
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'xp', title: @json($c['title']), message: @json($c['message']) } }));
            });
        </script>
    @endif

    <div class="flex flex-col gap-5 lg:flex-row lg:items-start">

        {{-- Content — fokus utama, mengambil sisa lebar --}}
        <div class="min-w-0 flex-1">
            <x-portal.card :padded="false">
                <div class="border-b border-outline-variant">
                    <div class="px-5 py-5 sm:px-8">
                        <p class="text-sm font-medium text-on-surface-variant">Materi {{ $currentIdx }} dari {{ $totalPages }}</p>
                        <h1 class="mt-1 text-xl font-bold leading-snug text-on-surface sm:text-2xl">{{ $page->judul }}</h1>
                    </div>
                </div>

                <div class="space-y-5 px-5 py-6 sm:px-8 lg:py-8">
                    @forelse($page->blocks ?? [] as $block)
                        <x-portal.module-block :block="$block" />
                    @empty
                        <p class="text-base text-on-surface-variant">Belum ada isi materi.</p>
                    @endforelse
                </div>
            </x-portal.card>
        </div>

        {{-- Sidebar outline — ramping, sekunder terhadap isi materi --}}
        <aside class="hidden w-72 shrink-0 lg:block">
            <x-portal.card :padded="false" class="sticky top-20">
                <div class="border-b border-outline-variant px-5 py-4">
                    <p class="font-bold text-on-surface">Daftar Materi</p>
                    <p class="mt-0.5 text-xs text-on-surface-variant">{{ $done }} dari {{ $totalPages }} selesai</p>
                </div>
                <ol class="divide-y divide-outline-variant">
                    @foreach($pages as $p)
                        @php
                            $isDone = in_array($p->id, $pagesCompleted);
                            $isCurrent = $p->id === $page->id;
                            $locked = $firstIncomplete !== false && $loop->index > $firstIncomplete;
                        @endphp
                        <li>
                            @if($locked)
                                {{-- Terkunci: harus selesaikan materi sebelumnya dulu --}}
                                <div class="flex cursor-not-allowed items-center gap-3 px-5 py-3 opacity-60" title="Selesaikan materi sebelumnya dulu">
                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-surface-container-high text-outline">
                                        <x-heroicon-s-lock-closed class="h-3 w-3" />
                                    </span>
                                    <span class="truncate text-sm font-medium text-on-surface-variant">{{ $p->judul }}</span>
                                </div>
                            @else
                                <a href="{{ route('portal.modules.pages.show', [$module, $p]) }}"
                                    class="flex items-center gap-3 px-5 py-3 transition-colors {{ $isCurrent ? 'bg-primary/5' : 'hover:bg-surface-container-low' }}">
                                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[11px] font-bold
                                        {{ $isDone ? 'bg-sdg-3/10 text-sdg-3' : ($isCurrent ? 'bg-primary/10 text-primary' : 'bg-surface-container-high text-on-surface-variant') }}">
                                        @if($isDone)
                                            <x-heroicon-s-check class="h-3.5 w-3.5" />
                                        @else
                                            {{ $loop->iteration }}
                                        @endif
                                    </span>
                                    <span class="truncate text-sm font-medium {{ $isCurrent ? 'text-primary' : ($isDone ? 'text-on-surface-variant' : 'text-on-surface') }}">
                                        {{ $p->judul }}
                                    </span>
                                </a>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </x-portal.card>
        </aside>
    </div>
@endsection
