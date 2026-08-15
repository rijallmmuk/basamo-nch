@extends('portal.layouts.app')

@section('title', $pelatihan->temaNama() . ' · Pelatihan')

@section('content')
<div class="space-y-8">
    
    {{-- Back Link --}}
    <div class="mb-4">
        <a href="{{ route('portal.pelatihan.index') }}"
            class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-bold text-on-surface-variant hover:text-primary transition-colors">
            <x-heroicon-s-arrow-left class="h-4 w-4" />
            <span>Kembali ke Katalog Pelatihan</span>
        </a>
    </div>

    {{-- ── 1. PROGRAM HEADER HERO CARD ────────────────────────────────── --}}
    <section class="overflow-hidden rounded-2xl border border-outline-variant bg-surface-container-lowest shadow-sm">
        <div class="grid grid-cols-1 lg:grid-cols-3">
            
            {{-- Pelatihan Cover Image (1 Column) --}}
            <div class="relative aspect-[16/9] lg:aspect-auto lg:h-full w-full overflow-hidden bg-surface-container-high">
                @if ($pelatihan->punyaCover())
<img src="{{ $pelatihan->coverUrl() }}" alt="Cover {{ $pelatihan->temaNama() }}" class="h-full w-full object-cover" loading="lazy">
                @else
                    {{-- Ringkas: nama pelatihan sudah tertera besar tepat di sebelahnya. --}}
                    <x-slc.tema-cover :nama="$pelatihan->temaNama()" ringkas class="h-full w-full object-cover" />
                @endif
                
            </div>

            {{-- Pelatihan Info & Metadata (2 Columns) --}}
            <div class="lg:col-span-2 p-6 sm:p-8 flex flex-col justify-between space-y-6">
                <div>
                    {{-- Badge status saja. Sasaran nagari tidak ditampilkan: warga membuka
                         halaman ini dari portal nagarinya sendiri. --}}
                    <div class="flex flex-wrap items-center gap-2 mb-3">
                        @if(! $pelatihan->dapatDimasuki())
                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-500/10 px-3 py-1 text-xs font-bold text-amber-700 dark:text-amber-300">
                                <x-heroicon-s-lock-closed class="h-3.5 w-3.5" />
                                <span>Terkunci oleh Admin</span>
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-500/10 px-3 py-1 text-xs font-bold text-emerald-600 dark:text-emerald-400">
                                <x-heroicon-s-check-circle class="h-3.5 w-3.5" />
                                <span>Pelatihan Terbuka</span>
                            </span>
                        @endif
                    </div>

                    {{-- Title --}}
                    <h1 class="text-2xl font-black text-on-surface sm:text-3xl lg:text-4xl tracking-tight break-words">{{ $pelatihan->temaNama() }}</h1>

                    {{-- Deskripsi yang ditulis pengajar pada form pelatihan. --}}
                    @if(filled($pelatihan->deskripsi))
                        <div x-data="{ expanded: false, overflow: false }" 
                             x-init="$nextTick(() => { overflow = $refs.content.scrollHeight > $refs.content.clientHeight })" 
                             class="mt-4">
                            <div x-ref="content" 
                                 :class="expanded ? '' : 'line-clamp-4'" 
                                 class="prose prose-sm max-w-none text-on-surface-variant prose-headings:text-on-surface prose-a:text-primary transition-all duration-300">
                                {!! str($pelatihan->deskripsi)->sanitizeHtml() !!}
                            </div>
                            <button x-show="overflow" x-cloak 
                                    @click="expanded = !expanded" 
                                    class="mt-2 text-sm font-bold text-primary hover:underline focus:outline-none inline-flex items-center gap-1">
                                <span x-text="expanded ? 'Tampilkan lebih sedikit' : 'Baca selengkapnya'"></span>
                                <x-heroicon-s-chevron-down class="h-4 w-4 transition-transform duration-300" x-bind:class="expanded ? 'rotate-180' : ''" />
                            </button>
                        </div>
                    @endif
                </div>

                @if($pelatihan->sertifikat_mode->memberiSertifikat())
                    <div @class([
                        'mt-6 rounded-2xl border p-5',
                        'border-success/40 bg-success/5' => $sertifikatBerhak,
                        'border-outline-variant bg-surface-container-low' => ! $sertifikatBerhak,
                    ])>
                        <div class="flex items-start gap-3">
                            <x-heroicon-o-document-check @class([
                                'h-6 w-6 shrink-0',
                                'text-success' => $sertifikatBerhak,
                                'text-on-surface-variant' => ! $sertifikatBerhak,
                            ]) />
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-extrabold text-on-surface">Sertifikat Pelatihan</p>

                                @if($sertifikatBerhak)
                                    <p class="mt-1 text-xs text-on-surface-variant">Selamat, sertifikat Anda sudah bisa diambil.</p>
                                    <a href="{{ route('portal.pelatihan.sertifikat', $pelatihan) }}"
                                       class="mt-3 inline-flex min-h-11 items-center gap-2 rounded-xl bg-primary px-5 text-sm font-bold text-on-primary transition hover:bg-primary-highlight focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2">
                                        Unduh Sertifikat <x-heroicon-o-arrow-down-tray class="h-4 w-4" />
                                    </a>
                                @else
                                    <p class="mt-1 text-xs text-on-surface-variant">{{ $sertifikatAlasan }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Pengajar atau Pengelola Section --}}
                @php
                    $participants = $pelatihan->participants();
                @endphp
                <div class="border-t border-outline-variant pt-4">
                    <p class="text-xs font-bold uppercase tracking-wider text-on-surface-variant mb-3">Pengajar atau Pengelola</p>
                    <x-slc.pengelola-list :participants="$participants" />
                </div>

            </div>
        </div>
    </section>

    {{-- ── 2. DAFTAR MODUL PEMBELAJARAN DALAM PROGRAM (4-Column Grid) ────── --}}
    <section class="space-y-4">
        <div class="flex items-center justify-between border-b border-outline-variant pb-3">
            <div>
                <h2 class="text-lg font-black text-on-surface flex items-center gap-2">
                    <x-heroicon-s-book-open class="h-5 w-5 text-primary" />
                    <span>Daftar Modul Pembelajaran Pelatihan</span>
                </h2>
                <p class="text-xs text-on-surface-variant">Modul-modul yang perlu diselesaikan dalam pelatihan ini.</p>
            </div>
            <span class="rounded-lg bg-surface-container-high px-3 py-1 text-xs font-extrabold text-on-surface">
                {{ $modules->count() }} Modul
            </span>
        </div>

        @if($modules->isEmpty())
            <x-portal.empty 
                icon="heroicon-o-book-open" 
                title="Belum Ada Modul Dalam Pelatihan Ini"
                subtitle="Modul pembelajaran sedang disiapkan oleh pengelola pelatihan." />
        @else
            {{-- 4-Column Grid Layout for Modules --}}
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach($modules as $module)
                    @php
                        $user = auth()->user();
                        $progress = $module->progress->first();
                        $materisDone = count($progress?->halaman_selesai ?? []);
                        $pct = $module->materis_count > 0 ? (int) round($materisDone / $module->materis_count * 100) : 0;
                        $isCompleted = $progress?->status === \App\Enums\ModuleProgressStatus::Completed;
                        $isLocked = ! $pelatihan->dapatDimasuki() || ($module->prasyarat_module_id && ! in_array($module->prasyarat_module_id, $modules->where('progress.0.status', \App\Enums\ModuleProgressStatus::Completed)->pluck('id')->all()));
                    @endphp

                    {{-- Entire Module Card is Clickable (if unlocked) --}}
                    @if($isLocked)
                        <div class="flex flex-col justify-between overflow-hidden rounded-2xl border border-outline-variant bg-surface-container-lowest opacity-80 shadow-sm h-full">
                    @else
                        <a href="{{ route('portal.modules.show', $module) }}" 
                           class="group flex flex-col justify-between overflow-hidden rounded-2xl border border-outline-variant bg-surface-container-lowest shadow-sm hover:border-primary/40 hover:shadow-md transition-all h-full cursor-pointer">
                    @endif
                        <div>
                            {{-- Thumbnail (16:9 Aspect Ratio) --}}
                            <div class="relative aspect-[16/9] w-full overflow-hidden bg-surface-container-high shrink-0">
                                @if($module->punyaCover())
                                    <img src="{{ $module->coverUrl() }}"
                                         alt="{{ $module->judul }}"
                                         loading="lazy"
                                         class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
                                @else
                                    <x-slc.module-cover :judul="$module->judul"
                                        class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" />
                                @endif
                                
                                @if($isCompleted)
                                    <span class="absolute top-2.5 right-2.5 inline-flex items-center gap-1 rounded-full bg-emerald-600 px-2.5 py-0.5 text-[10px] font-bold text-white shadow-xs">
                                        <x-heroicon-s-check-circle class="h-3.5 w-3.5" />
                                        <span>Selesai</span>
                                    </span>
                                @elseif($isLocked)
                                    <span class="absolute top-2.5 right-2.5 inline-flex items-center gap-1 rounded-full bg-slate-900/80 backdrop-blur-md px-2.5 py-0.5 text-[10px] font-bold text-amber-300 shadow-xs">
                                        <x-heroicon-s-lock-closed class="h-3.5 w-3.5" />
                                        <span>Terkunci</span>
                                    </span>
                                @endif
                            </div>

                            {{-- Modul Content (Proportional line-clamps) --}}
                            <div class="p-4 space-y-2">
                                <h3 class="font-extrabold text-sm text-on-surface line-clamp-2 min-h-[2.5rem] leading-snug group-hover:text-primary transition-colors">
                                    {{ $module->judul }}
                                </h3>

                                <p class="text-xs text-on-surface-variant line-clamp-2 leading-relaxed min-h-[2.25rem]">
                                    {{ $module->deskripsi ? strip_tags($module->deskripsi) : 'Materi modul pembelajaran digital.' }}
                                </p>

                                {{-- Prasyarat Warning / Progress Bar --}}
                                @if($isLocked && $module->prerequisite)
                                    <div class="rounded-lg bg-amber-500/10 p-2 text-[10px] font-semibold text-amber-700 dark:text-amber-300 flex items-start gap-1.5 min-h-[2.5rem]">
                                        <x-heroicon-s-lock-closed class="h-3.5 w-3.5 shrink-0 mt-0.5" />
                                        <span class="line-clamp-2">Syarat: Selesaikan <strong>"{{ $module->prerequisite->judul }}"</strong>.</span>
                                    </div>
                                @elseif($isLocked && ! $pelatihan->dapatDimasuki())
                                    <div class="rounded-lg bg-amber-500/10 p-2 text-[10px] font-semibold text-amber-700 dark:text-amber-300 flex items-start gap-1.5 min-h-[2.5rem]">
                                        <x-heroicon-s-lock-closed class="h-3.5 w-3.5 shrink-0 mt-0.5" />
                                        <span>Pelatihan belum dibuka oleh Admin / Pengelola.</span>
                                    </div>
                                @else
                                    {{-- Progres Bar --}}
                                    <div class="space-y-1 min-h-[2.5rem] flex flex-col justify-center">
                                        <div class="flex items-center justify-between text-[10px] font-bold text-on-surface-variant">
                                            <span>Progres Materi</span>
                                            <span>{{ $materisDone }}/{{ $module->materis_count }} ({{ $pct }}%)</span>
                                        </div>
                                        <div class="h-1.5 w-full overflow-hidden rounded-full bg-surface-container-high">
                                            <div class="h-full rounded-full bg-primary transition-all duration-300" style="width: {{ $pct }}%"></div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Footer Action --}}
                        <div class="p-4 pt-0">
                            @if($isLocked)
                                <button disabled
                                    class="w-full inline-flex items-center justify-center gap-1.5 rounded-xl bg-surface-container-high px-3 py-2 text-xs font-bold text-on-surface-variant opacity-75 cursor-not-allowed">
                                    <x-heroicon-s-lock-closed class="h-3.5 w-3.5" />
                                    <span>Modul Terkunci</span>
                                </button>
                            @else
                                <span class="w-full inline-flex items-center justify-center gap-1.5 rounded-xl bg-primary px-3 py-2 text-xs font-extrabold text-on-primary shadow-xs group-hover:bg-surface-tint transition-all">
                                    <span>{{ $isCompleted ? 'Tinjau Ulang' : ($pct > 0 ? 'Lanjutkan Belajar' : 'Mulai Belajar') }}</span>
                                    <x-heroicon-s-arrow-right class="h-3.5 w-3.5" />
                                </span>
                            @endif
                        </div>

                    @if($isLocked)
                        </div>
                    @else
                        </a>
                    @endif
                @endforeach
            </div>
        @endif
    </section>

</div>
@endsection
