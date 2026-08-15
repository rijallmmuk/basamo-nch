@extends('portal.layouts.app')

@section('title', $module->judul . ' · Modul SLC')

@php
    $status = $isCompleted ? 'completed' : ($progress ? 'in_progress' : 'available');
    // Hitung materi TERKINI yang sudah dibaca (tahan ID hantu sisa materi terhapus).
    $done = $materis->filter(fn ($p) => in_array($p->id, $materiSelesai))->count();
    $total = $materis->count();
    $pct = $total > 0 ? (int) round($done / $total * 100) : 0;
    $nextMateri = $materis->first(fn ($p) => ! in_array($p->id, $materiSelesai)) ?? $materis->first();
    $firstPage = $materis->first();
    // Modul sudah "Selesai" tapi admin menambah materi baru (belum dibaca)
    $hasNewMaterial = $isCompleted && $done < $total;
    // $evaluasi & $evaluasiPassedScore dari controller
    $hasEvaluasi = $evaluasi !== null;
    $evaluasiPassed = $evaluasiPassedScore !== null;
    // Indeks materi pertama yang belum selesai = batas akses berurutan.
    $firstIncompleteIdx = $materis->search(fn ($p) => ! in_array($p->id, $materiSelesai));
    $program = $module->pelatihan;
    $isPreview = ! empty($isPreview);
    $moduleDetailUrl = $isPreview
        ? \App\Filament\Resources\Modules\ModuleResource::getUrl('view', ['record' => $module])
        : route('portal.modules.show', $module);
    $pelatihanUrl = $isPreview && $program
        ? \App\Filament\Resources\Pelatihans\PelatihanResource::getUrl('view', ['record' => $program])
        : ($program ? route('portal.pelatihan.show', $program) : route('portal.pelatihan.index'));
    $materiUrl = fn ($item) => $isPreview
        ? route('admin.preview.modules.materi.show', ['module' => $module, 'materi' => $item])
        : route('portal.modules.materi.show', [$module, $item]);
    $pretestUrl = $isPreview
        ? route('admin.preview.modules.pretest.show', $module)
        : route('portal.modules.pretest', $module);
    $evaluasiUrl = $isPreview
        ? route('admin.preview.modules.evaluasi.show', $module)
        : route('portal.modules.evaluasi', $module);
@endphp

@section('content')
    {{-- Back Link --}}
    <div class="mb-4">
        <a href="{{ $isPreview ? $moduleDetailUrl : $pelatihanUrl }}"
            class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-bold text-on-surface-variant hover:text-primary transition-colors">
            <x-heroicon-s-arrow-left class="h-4 w-4" />
            <span>{{ $isPreview ? 'Kembali ke Detail Modul' : ($program ? 'Kembali ke Detail Pelatihan' : 'Kembali ke Katalog Modul') }}</span>
        </a>
    </div>

    {{-- Perayaan saat modul baru saja tuntas (di-flash dari MateriController::complete) --}}
    @if($c = session('celebrate'))
        <script>
            window.addEventListener('load', () => {
                window.fireConfetti?.();
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', title: @json($c['title']), message: @json($c['message']) } }));
            });
        </script>
    @endif

    {{-- ── MAIN LAYOUT: 2 Columns (Hero & Sections on Left, Sticky Curriculum on Right) ── --}}
    <div class="grid items-start gap-6 lg:grid-cols-3">

        {{-- ── LEFT COLUMN (2 Columns Wide on Large Screen) ──────────────── --}}
        <div class="space-y-6 lg:col-span-2">
            
            {{-- ── 1. COMPACT ELEGANT MODULE HERO CARD ─────────────── --}}
            <article class="overflow-hidden rounded-2xl border border-outline-variant bg-surface-container-lowest p-5 sm:p-7 shadow-sm space-y-6">
                
                {{-- Compact Side-by-side / Flex Header --}}
                <div class="flex flex-col sm:flex-row sm:items-start gap-5 sm:gap-6">
                    
                    {{-- Cover Image Thumbnail (Compact 16:9 Aspect Ratio) --}}
                    <div class="relative w-full sm:w-56 aspect-[16/9] shrink-0 overflow-hidden rounded-xl bg-surface-container-high border border-outline-variant shadow-xs">
                        @if($module->punyaCover())
                            <img src="{{ $module->coverUrl() }}"
                                 alt="{{ $module->judul }}"
                                 loading="lazy"
                                 class="h-full w-full object-cover">
                        @else
                            {{-- Ringkas: judul modul sudah tertera besar tepat di sebelahnya. --}}
                            <x-slc.module-cover :judul="$module->judul" ringkas class="h-full w-full object-cover" />
                        @endif
                    </div>

                    {{-- Title, Badges & Description Area --}}
                    <div class="flex-1 space-y-3">
                        {{-- Badges Header --}}
                        <div class="flex flex-wrap items-center gap-2">
                            <x-portal.status-badge :status="$status" />

                            @if($program)
                                <a href="{{ $pelatihanUrl }}"
                                   class="inline-flex items-center gap-1.5 rounded-full bg-primary/10 px-3 py-0.5 text-xs font-extrabold text-primary hover:bg-primary hover:text-on-primary transition-colors">
                                    <x-heroicon-s-academic-cap class="h-3.5 w-3.5" />
                                    {{-- Tema saja, bukan namaTampil(): nama rakitan itu menempelkan
                                         sasaran nagari, yang di layar warga hanya mengulang nagarinya
                                         sendiri. Sasaran tetap dipakai di panel. --}}
                                    <span>Pelatihan: {{ $program->temaNama() }}</span>
                                </a>
                            @endif

                            <span class="inline-flex items-center gap-1.5 rounded-full bg-surface-container-high px-3 py-0.5 text-xs font-semibold text-on-surface-variant">
                                <x-heroicon-o-document-text class="h-3.5 w-3.5" />
                                <span>{{ $total }} Materi</span>
                            </span>
                        </div>

                        {{-- Title --}}
                        <h1 class="text-xl font-black text-on-surface sm:text-2xl lg:text-3xl tracking-tight leading-tight">
                            {{ $module->judul }}
                        </h1>

                        {{-- Description --}}
                        @if($module->deskripsi)
                            <div x-data="{ expanded: false, overflow: false }" 
                                 x-init="$nextTick(() => { overflow = $refs.content.scrollHeight > $refs.content.clientHeight })" 
                                 class="mt-3">
                                <div x-ref="content" 
                                     :class="expanded ? '' : 'line-clamp-4'" 
                                     class="prose prose-sm max-w-none text-on-surface-variant leading-relaxed transition-all duration-300">
                                    {!! str($module->deskripsi)->sanitizeHtml() !!}
                                </div>
                                <button x-show="overflow" x-cloak
                                        @click="expanded = !expanded" 
                                        class="mt-2 text-xs font-bold text-primary hover:underline focus:outline-none inline-flex items-center gap-1">
                                    <span x-text="expanded ? 'Tampilkan lebih sedikit' : 'Baca selengkapnya'"></span>
                                    <x-heroicon-s-chevron-down class="h-3.5 w-3.5 transition-transform duration-300" x-bind:class="expanded ? 'rotate-180' : ''" />
                                </button>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Progress Bar (Show when in progress or has new material) --}}
                @if(($progress && ! $isCompleted) || $hasNewMaterial)
                    <div class="space-y-1.5 rounded-xl bg-surface-container-low p-4 border border-outline-variant">
                        <div class="flex items-center justify-between text-xs font-bold text-on-surface">
                            <span>Progres Pembelajaran Anda</span>
                            <span class="text-primary">{{ $done }}/{{ $total }} Materi Selesai ({{ $pct }}%)</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-surface-container-high">
                            <div class="h-full rounded-full bg-primary transition-all duration-500" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @endif

                {{-- ── PRIMARY CTA BOX ─────────────────────────────────────── --}}
                @if($total > 0)
                    <div class="pt-3 border-t border-outline-variant">
                        @if($isCompleted)
                            @if($hasNewMaterial)
                                <div class="rounded-2xl border border-primary/30 bg-primary/5 p-4 space-y-3">
                                    <div class="flex items-center gap-2 text-sm font-extrabold text-primary">
                                        <x-heroicon-s-sparkles class="h-5 w-5 shrink-0" />
                                        <span>Materi Baru Ditambahkan</span>
                                    </div>
                                    <p class="text-xs text-on-surface-variant leading-relaxed">
                                        Pengelola menambahkan materi baru. Mari tuntaskan materi lanjutan ini:
                                    </p>
                                    <a href="{{ $materiUrl($nextMateri) }}"
                                        class="group inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-5 py-3 text-sm font-extrabold text-on-primary shadow-xs transition-all hover:bg-surface-tint">
                                        <x-heroicon-s-book-open class="h-5 w-5" />
                                        <span>Baca Materi Baru: {{ $nextMateri->judul }}</span>
                                        <x-heroicon-s-arrow-right class="h-4 w-4 transition-transform group-hover:translate-x-1" />
                                    </a>
                                </div>
                            @else
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                                    <div class="inline-flex items-center gap-2 rounded-xl bg-emerald-500/10 px-4 py-2.5 text-sm font-bold text-emerald-600 dark:text-emerald-400 border border-emerald-500/20">
                                        <x-heroicon-s-check-circle class="h-5 w-5 shrink-0 text-emerald-600 dark:text-emerald-400" />
                                        <span>Seluruh Materi Modul Selesai</span>
                                    </div>

                                    <a href="{{ $materiUrl($firstPage) }}"
                                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-outline-variant bg-surface-container-low px-4 py-2.5 text-sm font-bold text-on-surface hover:bg-surface-container-high transition-colors">
                                        <x-heroicon-o-arrow-path class="h-5 w-5 text-primary" />
                                        <span>Tinjau Ulang Materi</span>
                                    </a>
                                </div>
                            @endif

                            {{-- Evaluasi CTA if materials done but quiz pending --}}
                            @if($hasEvaluasi && ! $evaluasiPassed)
                                <div class="mt-4 pt-4 border-t border-outline-variant">
                                    <a href="{{ $evaluasiUrl }}"
                                        class="group inline-flex w-full items-center justify-center gap-2.5 rounded-xl bg-primary px-6 py-3 text-sm font-extrabold text-on-primary shadow-sm transition-all hover:bg-surface-tint sm:w-auto">
                                        <x-heroicon-s-clipboard-document-check class="h-5 w-5" />
                                        <span>Kerjakan Evaluasi Kegiatan</span>
                                        <x-heroicon-s-arrow-right class="h-4 w-4 transition-transform group-hover:translate-x-1" />
                                    </a>
                                    <p class="mt-2 text-xs text-on-surface-variant flex items-center gap-1">
                                        <x-heroicon-s-information-circle class="h-4 w-4 text-primary shrink-0" />
                                        <span>Satu langkah lagi! Kerjakan evaluasi untuk menguji pemahaman Anda.</span>
                                    </p>
                                </div>
                            @endif
                        @else
                            <div class="space-y-3">
                                <a href="{{ $butuhPretest ? $pretestUrl : $materiUrl($nextMateri) }}"
                                    class="group inline-flex w-full items-center justify-center gap-2.5 rounded-xl bg-primary px-6 py-3.5 text-sm font-extrabold text-on-primary shadow-sm transition-all hover:bg-surface-tint sm:w-auto">
                                    @if($butuhPretest)
                                        <x-heroicon-s-clipboard-document-list class="h-5 w-5" />
                                        <span>Kerjakan Pre-test</span>
                                    @else
                                        <x-heroicon-s-play class="h-5 w-5" />
                                        <span>{{ $progress ? 'Lanjutkan Belajar Modul' : 'Mulai Belajar Modul' }}</span>
                                    @endif
                                    <x-heroicon-s-arrow-right class="h-4 w-4 transition-transform group-hover:translate-x-1" />
                                </a>

                                <p class="text-xs text-on-surface-variant flex items-center gap-1.5">
                                    <x-heroicon-s-arrow-right-circle class="h-4 w-4 text-primary shrink-0" />
                                    <span>
                                        @if($butuhPretest)
                                            Pre-test dikerjakan satu kali sebelum materi dibuka.
                                        @else
                                            Materi lanjutan: <strong>{{ $nextMateri->judul }}</strong>
                                        @endif
                                    </span>
                                </p>
                            </div>
                        @endif
                    </div>
                @endif
            </article>

            {{-- ── 2. EVALUASI KUIS & RUANG DISKUSI MODUL ─────────────────── --}}
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                
                {{-- Evaluasi Kegiatan Card --}}
                <div class="rounded-2xl border border-outline-variant bg-surface-container-lowest p-5 shadow-sm space-y-3 flex flex-col justify-between">
                    <div class="flex items-start gap-3">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <x-heroicon-s-clipboard-document-check class="h-6 w-6" />
                        </div>
                        <div>
                            <h3 class="font-extrabold text-base text-on-surface">Evaluasi Kegiatan</h3>
                            <p class="text-xs text-on-surface-variant mt-0.5">Uji pemahaman Anda tentang materi modul ini.</p>
                        </div>
                    </div>

                    <div class="pt-2">
                        @if($hasEvaluasi)
                            @if($isCompleted && $evaluasiPassed)
                                <div class="rounded-xl bg-emerald-500/10 p-3 border border-emerald-500/20 text-xs font-bold text-emerald-600 dark:text-emerald-400 flex items-center gap-2">
                                    <x-heroicon-s-check-badge class="h-5 w-5 text-emerald-600 shrink-0" />
                                    <span>Evaluasi Lulus, nilai {{ $evaluasiPassedScore }}</span>
                                </div>
                            @elseif($isCompleted)
                                <a href="{{ $evaluasiUrl }}"
                                    class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2.5 text-xs font-extrabold text-on-primary hover:bg-surface-tint transition-all">
                                    <x-heroicon-s-pencil-square class="h-4 w-4" />
                                    <span>Kerjakan Evaluasi</span>
                                </a>
                            @else
                                <div class="rounded-xl bg-surface-container-low p-3 border border-outline-variant text-xs text-on-surface-variant flex items-center gap-2 opacity-80">
                                    <x-heroicon-s-lock-closed class="h-4 w-4 text-outline shrink-0" />
                                    <span>Evaluasi Terkunci (Selesaikan materi)</span>
                                </div>
                            @endif
                        @else
                            <div class="rounded-xl bg-surface-container-low p-3 border border-outline-variant text-xs text-on-surface-variant">
                                <span>Modul berbasis materi bacaan mandiri.</span>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Diskusi Card --}}
                <div class="rounded-2xl border border-outline-variant bg-surface-container-lowest p-5 shadow-sm space-y-3 flex flex-col justify-between">
                    <div class="flex items-start gap-3">
                        <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-sky-500/10 text-sky-600 dark:text-sky-400">
                            <x-heroicon-s-chat-bubble-left-right class="h-6 w-6" />
                        </div>
                        <div>
                            <h3 class="font-extrabold text-base text-on-surface">Ruang Diskusi Modul</h3>
                            <p class="text-xs text-on-surface-variant mt-0.5">Tanya jawab & diskusi materi bersama warga.</p>
                        </div>
                    </div>

                    <div class="pt-2">
                        @if($isPreview)
                            <div class="w-full inline-flex items-center justify-center gap-2 rounded-xl border border-outline-variant bg-surface-container-low px-4 py-2.5 text-xs font-bold text-on-surface-variant">
                                <x-heroicon-s-eye class="h-4 w-4" />
                                <span>Forum tidak dibuka dalam mode pratinjau</span>
                            </div>
                        @else
                            <a href="{{ route('portal.modules.discuss', $module) }}"
                                class="w-full inline-flex items-center justify-center gap-2 rounded-xl border border-outline-variant bg-surface-container-low px-4 py-2.5 text-xs font-extrabold text-on-surface hover:bg-surface-container-high hover:text-primary transition-all">
                                <x-heroicon-s-chat-bubble-left-right class="h-4 w-4 text-primary" />
                                <span>Buka Forum Diskusi →</span>
                            </a>
                        @endif
                    </div>
                </div>

            </div>

        </div>

        {{-- ── RIGHT COLUMN: STICKY CURRICULUM & DAFTAR MATERI ──────────── --}}
        <aside class="lg:col-span-1 lg:sticky lg:top-24 space-y-4">
            <article class="overflow-hidden rounded-2xl border border-outline-variant bg-surface-container-lowest shadow-sm">
                
                {{-- Header --}}
                <div class="flex items-center justify-between border-b border-outline-variant p-4 sm:p-5 bg-surface-container-low">
                    <div>
                        <h2 class="font-extrabold text-base text-on-surface flex items-center gap-2">
                            <x-heroicon-s-book-open class="h-5 w-5 text-primary" />
                            <span>Daftar Materi Modul</span>
                        </h2>
                        <p class="text-xs text-on-surface-variant mt-0.5">{{ $done }}/{{ $total }} Materi Selesai</p>
                    </div>

                    @if($total > 0)
                        <span class="rounded-xl px-2.5 py-1 text-xs font-extrabold {{ $isCompleted ? 'bg-emerald-500/10 text-emerald-600' : 'bg-primary/10 text-primary' }}">
                            {{ $pct }}%
                        </span>
                    @endif
                </div>

                {{-- Material List Items --}}
                @if($materis->isEmpty())
                    <p class="p-8 text-center text-xs text-on-surface-variant">Belum ada materi pembelajaran.</p>
                @else
                    <ol class="divide-y divide-outline-variant max-h-[calc(100vh-14rem)] overflow-y-auto">
                        @foreach($materis as $p)
                            @php
                                $pDone = in_array($p->id, $materiSelesai);
                                $isNext = ! $pDone && $p->id === $nextMateri->id && (! $isCompleted || $hasNewMaterial);
                                $locked = $butuhPretest || ($firstIncompleteIdx !== false && $loop->index > $firstIncompleteIdx);
                                $pTypes = collect($p->blocks ?? [])->pluck('type')->unique()
                                    ->map(fn ($t) => \App\Enums\ModuleBlockType::tryFrom($t))->filter();
                            @endphp

                            <li>
                                @if($locked)
                                    <div class="flex items-center justify-between p-4 cursor-not-allowed opacity-60 bg-surface-container-low/50" title="Selesaikan materi sebelumnya terlebih dahulu">
                                @else
                                    <a href="{{ $materiUrl($p) }}"
                                       class="group flex items-center justify-between p-4 transition-colors hover:bg-surface-container-low {{ $isNext ? 'bg-primary/5 border-l-4 border-l-primary' : '' }}">
                                @endif

                                    <div class="flex items-start gap-3 min-w-0 flex-1">
                                        {{-- Step Badge Circle --}}
                                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-extrabold mt-0.5
                                            {{ $pDone ? 'bg-emerald-600 text-white' : ($isNext ? 'bg-primary text-on-primary' : 'bg-surface-container-high text-on-surface-variant') }}">
                                            @if($pDone)
                                                <x-heroicon-s-check class="h-4 w-4" />
                                            @elseif($locked)
                                                <x-heroicon-s-lock-closed class="h-3.5 w-3.5 text-outline" />
                                            @else
                                                {{ $loop->iteration }}
                                            @endif
                                        </span>

                                        <div class="min-w-0 flex-1 space-y-0.5">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <h3 class="text-xs font-bold truncate {{ $pDone || $locked ? 'text-on-surface-variant' : 'text-on-surface group-hover:text-primary transition-colors' }}">
                                                    {{ $p->judul }}
                                                </h3>

                                                @if($isNext)
                                                    <span class="shrink-0 rounded-full bg-primary px-2 py-0.5 text-[9px] font-extrabold uppercase tracking-wide text-on-primary">
                                                        {{ $hasNewMaterial ? 'Baru' : ($progress ? 'Lanjut' : 'Mulai') }}
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="flex items-center gap-2 text-[10px] text-on-surface-variant">
                                                @if($pDone)
                                                    <span class="text-emerald-600 dark:text-emerald-400 font-bold flex items-center gap-1">
                                                        <x-heroicon-s-check-circle class="h-3 w-3" />
                                                        <span>Selesai Dibaca</span>
                                                    </span>
                                                @elseif($locked)
                                                    <span class="flex items-center gap-1">
                                                        <x-heroicon-s-lock-closed class="h-3 w-3 text-outline" />
                                                        <span>Terkunci</span>
                                                    </span>
                                                @elseif($pTypes->isNotEmpty())
                                                    @foreach($pTypes as $bt)
                                                        <span class="inline-flex items-center gap-1">
                                                            <x-dynamic-component :component="$bt->getIcon()" class="h-3 w-3 text-primary" />
                                                            <span>{{ $bt->getLabel() }}</span>
                                                        </span>
                                                    @endforeach
                                                @else
                                                    <span>Materi Bacaan</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    @unless($locked)
                                        <x-heroicon-s-chevron-right class="h-4 w-4 shrink-0 text-outline-variant group-hover:text-primary group-hover:translate-x-0.5 transition-all" />
                                    @endunless

                                @if($locked)
                                    </div>
                                @else
                                    </a>
                                @endif
                            </li>
                        @endforeach
                    </ol>
                @endif
            </article>
        </aside>

    </div>
@endsection
