@extends('portal.layouts.app')

@section('title', $materi->judul . ' - ' . $module->judul)
@section('main-class', 'py-6 pb-28')

@php
    $totalMateris = $materis->count();
    $done = count($materiSelesai);
    $pct = $totalMateris > 0 ? (int) round($done / $totalMateris * 100) : 0;
    $currentIdx = $materis->search(fn ($p) => $p->id === $materi->id) + 1;
    // Indeks materi pertama yang belum selesai = batas akses (materi setelahnya terkunci).
    $firstIncomplete = $materis->search(fn ($p) => ! in_array($p->id, $materiSelesai));
    $currentDone = in_array($materi->id, $materiSelesai);
    $isFinish = ! $nextMateri;
    $isPreview = ! empty($isPreview);
    $moduleUrl = $isPreview
        ? \App\Filament\Resources\Modules\ModuleResource::getUrl('view', ['record' => $module])
        : route('portal.modules.show', $module);
    $materiUrl = fn ($item) => $isPreview
        ? route('admin.preview.modules.materi.show', ['module' => $module, 'materi' => $item])
        : route('portal.modules.materi.show', [$module, $item]);
    $fwdLabel = $isFinish
        ? ($isPreview ? 'Kembali ke Detail Modul' : 'Selesaikan Modul')
        : 'Materi Selanjutnya';
    $fwdClass = $isFinish 
        ? 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-xs' 
        : 'bg-primary hover:bg-surface-tint text-on-primary shadow-xs';
@endphp
@section('bottom-navigation')
<div class="fixed inset-x-0 bottom-0 z-30 border-t border-outline-variant bg-surface-container-lowest/95 backdrop-blur-md shadow-lg lg:left-64">
    <div class="mx-auto flex h-16 max-w-[120rem] items-center justify-between gap-3 px-4 sm:px-6 lg:px-margin-desktop">
        
        {{-- Left: Previously / Back to Module --}}
        <a href="{{ $prevMateri ? $materiUrl($prevMateri) : $moduleUrl }}"
            class="inline-flex items-center gap-2 rounded-xl border border-outline-variant bg-surface-container-low px-4 py-2.5 text-xs sm:text-sm font-bold text-on-surface hover:bg-surface-container-high transition-all shrink-0">
            <x-heroicon-s-arrow-left class="h-4 w-4 text-primary" />
            <span>{{ $prevMateri ? 'Sebelumnya' : 'Detail Modul' }}</span>
        </a>

        {{-- Center Progress Dots --}}
        <div class="hidden items-center gap-2 sm:flex">
            <span class="text-xs font-bold text-on-surface-variant mr-1">Materi {{ $currentIdx }}/{{ $totalMateris }}</span>
            <div class="flex items-center gap-1.5">
                @foreach($materis as $p)
                    @php 
                        $pDone = in_array($p->id, $materiSelesai);
                        $pCurrent = $p->id === $materi->id;
                    @endphp
                    <span class="rounded-full transition-all duration-300
                        @if($pCurrent) h-2.5 w-7 bg-primary
                        @elseif($pDone) h-2.5 w-2.5 bg-emerald-500
                        @else h-2.5 w-2.5 bg-surface-container-high @endif"
                        title="{{ $p->judul }}"></span>
                @endforeach
            </div>
        </div>

        {{-- Right: Next / Complete Action --}}
        @if($currentDone)
            <a href="{{ $isFinish ? $moduleUrl : $materiUrl($nextMateri) }}"
                class="inline-flex items-center gap-2 rounded-xl px-5 py-2.5 text-xs sm:text-sm font-extrabold transition-all shrink-0 {{ $fwdClass }}">
                <span>{{ $fwdLabel }}</span>
                <x-dynamic-component :component="$isFinish ? 'heroicon-s-check-circle' : 'heroicon-s-arrow-right'" class="h-4 w-4" />
            </a>
        @else
            <form method="POST" action="{{ route('portal.modules.materi.complete', [$module, $materi]) }}" class="shrink-0">
                @csrf
                <button type="submit"
                    class="inline-flex items-center gap-2 rounded-xl px-5 py-2.5 text-xs sm:text-sm font-extrabold transition-all shrink-0 {{ $fwdClass }}">
                    <span>{{ $fwdLabel }}</span>
                    <x-dynamic-component :component="$isFinish ? 'heroicon-s-check-circle' : 'heroicon-s-arrow-right'" class="h-4 w-4" />
                </button>
            </form>
        @endif

    </div>
</div>
@endsection

@section('content')
{{-- Full-screen Focus Reader Mode Controller --}}
<div x-data="{ read: $persist(false).as('portal_reader_full') }" 
     @keydown.escape.window="read = false"
     :class="read && 'fixed inset-0 z-[70] flex flex-col overflow-hidden bg-surface-container-lowest'">

    {{-- Focus Mode Header Bar --}}
    <div x-show="read" x-cloak
        class="flex shrink-0 items-center justify-between gap-3 border-b border-outline-variant bg-surface-container-lowest px-4 py-3 sm:px-6 shadow-xs">
        <div class="min-w-0">
            <p class="text-xs font-bold text-primary">Materi {{ $currentIdx }} dari {{ $totalMateris }}</p>
            <p class="truncate text-sm font-extrabold text-on-surface">{{ $materi->judul }}</p>
        </div>
        <button type="button" @click="read = false"
            class="inline-flex shrink-0 items-center gap-1.5 rounded-xl border border-outline-variant bg-surface-container-low px-3 py-1.5 text-xs font-bold text-on-surface hover:bg-surface-container-high transition-colors">
            <x-heroicon-s-arrows-pointing-in class="h-4 w-4 text-primary" />
            <span>Keluar Mode Baca</span>
            <kbd class="hidden rounded bg-surface-container-high px-1.5 py-0.5 text-[10px] font-mono text-on-surface-variant sm:inline">Esc</kbd>
        </button>
    </div>

    {{-- Main Scroll Area --}}
    <div :class="read && 'flex-1 overflow-y-auto'">
        
        {{-- Back Link (hidden in Focus Mode) --}}
        <div x-show="!read" class="mb-4">
            <a href="{{ $moduleUrl }}"
                class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-bold text-on-surface-variant hover:text-primary transition-colors">
                <x-heroicon-s-arrow-left class="h-4 w-4" />
                <span>Kembali ke Detail Modul</span>
            </a>
        </div>

        {{-- Toast / Celebrate Notification --}}
        @if($c = session('celebrate'))
            <script>
                window.addEventListener('load', () => {
                    window.fireConfetti?.();
                    window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', title: @json($c['title']), message: @json($c['message']) } }));
                });
            </script>
        @endif

        {{-- Reader Container: Main Material Card (Left) & Sticky Curriculum Sidebar (Right) --}}
        <div class="flex flex-col gap-6 lg:flex-row lg:items-start"
            :class="read && 'portal-reader-full !mx-auto !block !w-full !max-w-[96rem] px-4 pb-28 pt-6 sm:px-6 lg:px-10 xl:px-12'">

            {{-- MAIN READING CARD --}}
            <main class="min-w-0 flex-1">
                <article class="overflow-hidden rounded-2xl border border-outline-variant bg-surface-container-lowest shadow-sm">
                    
                    {{-- Material Header Banner --}}
                    <div class="border-b border-outline-variant p-6 sm:p-8 bg-surface-container-low/40">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0 space-y-2">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="rounded-full bg-primary/10 px-3 py-0.5 text-xs font-extrabold text-primary">
                                        Materi {{ $currentIdx }} dari {{ $totalMateris }}
                                    </span>
                                    @if($currentDone)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/10 px-2.5 py-0.5 text-xs font-bold text-emerald-600 dark:text-emerald-400">
                                            <x-heroicon-s-check-circle class="h-3.5 w-3.5" />
                                            <span>Selesai Dibaca</span>
                                        </span>
                                    @endif
                                </div>

                                <h1 class="text-2xl font-black text-on-surface sm:text-3xl lg:text-4xl tracking-tight leading-tight">
                                    {{ $materi->judul }}
                                </h1>
                            </div>

                            {{-- Toggle Fullscreen Focus Reading Mode Button --}}
                            <button type="button" x-show="!read" @click="read = true"
                                class="inline-flex shrink-0 items-center gap-1.5 rounded-xl border border-outline-variant bg-surface-container-lowest px-3 py-2 text-xs font-bold text-on-surface hover:bg-surface-container-low hover:text-primary transition-all shadow-2xs"
                                title="Baca Layar Penuh Bebas Distraksi">
                                <x-heroicon-s-arrows-pointing-out class="h-4 w-4 text-primary" />
                                <span class="hidden sm:inline">Mode Baca Layar Penuh</span>
                            </button>
                        </div>
                    </div>

                    {{-- Content Blocks Rendering Area --}}
                    <div class="p-6 sm:p-8 sm:py-10 space-y-8">
                        @forelse($materi->blocks ?? [] as $blockIndex => $block)
                            <div class="block-item border-b border-outline-variant/40 pb-8 last:border-b-0 last:pb-0">
                                <x-portal.module-block :block="$block" :module="$module" :page="$materi" :block-index="$blockIndex" />
                            </div>
                        @empty
                            <div class="py-12 text-center text-sm text-on-surface-variant">
                                <x-heroicon-o-document-text class="mx-auto h-12 w-12 text-outline mb-2" />
                                <p class="font-bold text-on-surface">Materi Belum Tersedia</p>
                                <p class="text-xs">Isi materi pembelajaran sedang disiapkan oleh pengelola.</p>
                            </div>
                        @endforelse
                    </div>

                </article>
            </main>

            {{-- STICKY SIDEBAR CURRICULUM (Hidden in Focus Mode) --}}
            <aside x-show="!read" class="hidden w-80 shrink-0 lg:block lg:sticky lg:top-24">
                <article class="overflow-hidden rounded-2xl border border-outline-variant bg-surface-container-lowest shadow-sm">
                    
                    {{-- Sidebar Header --}}
                    <div class="flex items-center justify-between border-b border-outline-variant p-4 bg-surface-container-low">
                        <div>
                            <h2 class="font-extrabold text-sm text-on-surface flex items-center gap-1.5">
                                <x-heroicon-s-book-open class="h-4 w-4 text-primary" />
                                <span>Daftar Materi Modul</span>
                            </h2>
                            <p class="text-[11px] text-on-surface-variant mt-0.5">{{ $done }} dari {{ $totalMateris }} Selesai</p>
                        </div>
                        <span class="rounded-lg bg-primary/10 px-2 py-0.5 text-xs font-extrabold text-primary">
                            {{ $pct }}%
                        </span>
                    </div>

                    {{-- Ordered Pages List --}}
                    <ol class="divide-y divide-outline-variant max-h-[calc(100vh-14rem)] overflow-y-auto">
                        @foreach($materis as $p)
                            @php
                                $isPDone = in_array($p->id, $materiSelesai);
                                $isPCurrent = $p->id === $materi->id;
                                $isPLocked = $firstIncomplete !== false && $loop->index > $firstIncomplete;
                            @endphp
                            <li>
                                @if($isPLocked)
                                    <div class="flex items-center gap-3 p-3.5 cursor-not-allowed opacity-60 bg-surface-container-low/50" title="Selesaikan materi sebelumnya terlebih dahulu">
                                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-surface-container-high text-outline text-xs">
                                            <x-heroicon-s-lock-closed class="h-3.5 w-3.5" />
                                        </span>
                                        <span class="truncate text-xs font-medium text-on-surface-variant">{{ $p->judul }}</span>
                                    </div>
                                @else
                                    <a href="{{ $materiUrl($p) }}"
                                        class="flex items-center gap-3 p-3.5 transition-colors {{ $isPCurrent ? 'bg-primary/10 border-l-4 border-l-primary font-bold' : 'hover:bg-surface-container-low' }}">
                                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-bold
                                            {{ $isPDone ? 'bg-emerald-600 text-white' : ($isPCurrent ? 'bg-primary text-on-primary' : 'bg-surface-container-high text-on-surface-variant') }}">
                                            @if($isPDone)
                                                <x-heroicon-s-check class="h-3.5 w-3.5" />
                                            @else
                                                {{ $loop->iteration }}
                                            @endif
                                        </span>
                                        <span class="truncate text-xs {{ $isPCurrent ? 'text-primary font-extrabold' : ($isPDone ? 'text-on-surface-variant font-medium' : 'text-on-surface font-semibold') }}">
                                            {{ $p->judul }}
                                        </span>
                                    </a>
                                @endif
                            </li>
                        @endforeach
                    </ol>

                </article>
            </aside>

        </div>
    </div>

    {{-- Focus Mode Bottom Navigation Bar --}}
    <div x-show="read" x-cloak class="shrink-0 border-t border-outline-variant bg-surface-container-lowest shadow-lg">
        <div class="mx-auto flex h-16 w-full max-w-[96rem] items-center justify-between gap-3 px-4 sm:px-6 lg:px-10 xl:px-12">
            <a href="{{ $prevMateri ? $materiUrl($prevMateri) : $moduleUrl }}"
                class="inline-flex items-center gap-2 rounded-xl border border-outline-variant bg-surface-container-low px-4 py-2.5 text-xs font-bold text-on-surface hover:bg-surface-container-high transition-colors">
                <x-heroicon-s-arrow-left class="h-4 w-4 text-primary" />
                <span>{{ $prevMateri ? 'Sebelumnya' : 'Detail Modul' }}</span>
            </a>

            <div class="hidden items-center gap-1.5 sm:flex">
                @foreach($materis as $p)
                    @php $pDone = in_array($p->id, $materiSelesai); $pCurrent = $p->id === $materi->id; @endphp
                    <span class="rounded-full transition-all
                        @if($pCurrent) h-2 w-6 bg-primary
                        @elseif($pDone) h-2 w-2 bg-emerald-500
                        @else h-2 w-2 bg-surface-container-high @endif"></span>
                @endforeach
            </div>

            @if($currentDone)
                <a href="{{ $isFinish ? $moduleUrl : $materiUrl($nextMateri) }}"
                    class="inline-flex items-center gap-2 rounded-xl px-5 py-2.5 text-xs sm:text-sm font-extrabold transition-colors {{ $fwdClass }}">
                    <span>{{ $fwdLabel }}</span>
                    <x-dynamic-component :component="$isFinish ? 'heroicon-s-check-circle' : 'heroicon-s-arrow-right'" class="h-4 w-4" />
                </a>
            @else
                <form method="POST" action="{{ route('portal.modules.materi.complete', [$module, $materi]) }}">
                    @csrf
                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-xl px-5 py-2.5 text-xs sm:text-sm font-extrabold transition-colors {{ $fwdClass }}">
                        <span>{{ $fwdLabel }}</span>
                        <x-dynamic-component :component="$isFinish ? 'heroicon-s-check-circle' : 'heroicon-s-arrow-right'" class="h-4 w-4" />
                    </button>
                </form>
            @endif
        </div>
    </div>

</div>
@endsection
