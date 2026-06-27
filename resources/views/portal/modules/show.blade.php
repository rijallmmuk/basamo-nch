@extends('portal.layouts.app')

@section('title', $module->judul)

@php
    $status = $isCompleted ? 'completed' : ($progress ? 'in_progress' : 'available');
    $done = count($pagesCompleted);
    $total = $pages->count();
    $pct = $total > 0 ? (int) ($done / $total * 100) : 0;
    $nextPage = $pages->first(fn ($p) => ! in_array($p->id, $pagesCompleted)) ?? $pages->first();
    $firstPage = $pages->first();
    $hasQuiz = $module->quiz && $module->quiz->questions()->exists();
    // Materi berurutan: indeks materi pertama yang belum selesai = batas akses.
    $firstIncompleteIdx = $pages->search(fn ($p) => ! in_array($p->id, $pagesCompleted));
@endphp

@section('content')
    <x-portal.breadcrumb :items="[
        ['label' => 'Modul', 'url' => route('portal.modules.index')],
        ['label' => $module->judul],
    ]" />

    {{-- Perayaan saat modul baru saja tuntas (di-flash dari PageController::complete) --}}
    @if($c = session('celebrate'))
        <script>
            window.addEventListener('load', () => {
                window.fireConfetti?.();
                window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'xp', title: @json($c['title']), message: @json($c['message']) } }));
            });
        </script>
    @endif

    {{-- Urutan DOM = urutan mobile: Hero+CTA → Daftar Materi → Kuis → Diskusi.
         Di desktop: kiri (col-span-2) Hero+Kuis+Diskusi, kanan Daftar Materi sticky. --}}
    <div class="grid items-start gap-5 lg:grid-cols-3">

        {{-- ============ HERO + CTA UTAMA (kiri-atas) ============ --}}
        <x-portal.card :padded="false" class="lg:col-span-2">
            <img src="{{ $module->coverUrl() }}" alt="" class="h-36 w-full object-cover sm:h-44">
            <div class="h-1.5 w-full {{ $isCompleted ? 'bg-sdg-3' : ($progress ? 'bg-primary' : 'bg-primary-fixed-dim') }}"></div>

            <div class="p-5 sm:p-7">
                <div class="mb-3 flex flex-wrap items-center gap-2">
                    <x-portal.status-badge :status="$status" />
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-surface-container-high px-3 py-1 text-xs font-medium text-on-surface-variant">
                        <x-heroicon-o-rectangle-stack class="h-3.5 w-3.5" />
                        {{ $total }} materi
                    </span>
                    @if($module->estimasi_menit)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-surface-container-high px-3 py-1 text-xs font-medium text-on-surface-variant">
                            <x-heroicon-o-clock class="h-3.5 w-3.5" />
                            ± {{ $module->estimasi_menit }} menit
                        </span>
                    @endif
                </div>

                <h1 class="text-xl font-bold text-on-surface sm:text-3xl">{{ $module->judul }}</h1>

                @if($module->deskripsi)
                    <div class="prose prose-sm mt-2 max-w-none leading-relaxed text-on-surface-variant">
                        {!! str($module->deskripsi)->sanitizeHtml() !!}
                    </div>
                @endif

                @if($progress && ! $isCompleted)
                    <div class="mt-5">
                        <div class="mb-1.5 flex items-center justify-between text-sm">
                            <span class="text-on-surface-variant">{{ $done }} dari {{ $total }} materi selesai</span>
                            <span class="font-bold text-primary">{{ $pct }}%</span>
                        </div>
                        <div class="h-2.5 overflow-hidden rounded-full bg-surface-container-high">
                            <div class="h-full rounded-full bg-primary transition-all" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                @endif

                {{-- CTA UTAMA — satu aksi jelas, tepat di bawah judul --}}
                @if($total > 0)
                    <div class="mt-6">
                        @if($isCompleted)
                            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                                <span class="inline-flex items-center justify-center gap-2 rounded-xl bg-sdg-3/10 px-4 py-3 text-sm font-bold text-sdg-3">
                                    <x-heroicon-s-check-circle class="h-5 w-5" />
                                    Modul ini sudah kamu selesaikan
                                </span>
                                <x-portal.button :href="route('portal.modules.pages.show', [$module, $firstPage])" variant="secondary" size="lg">
                                    <x-heroicon-o-arrow-path class="h-5 w-5" /> Tinjau Ulang Materi
                                </x-portal.button>
                            </div>
                        @else
                            <a href="{{ route('portal.modules.pages.show', [$module, $nextPage]) }}"
                                class="group flex w-full items-center justify-center gap-2.5 rounded-xl bg-primary px-6 py-4 text-base font-bold text-on-primary shadow-sm transition-colors hover:bg-surface-tint sm:w-fit">
                                <x-heroicon-s-play class="h-5 w-5" />
                                {{ $progress ? 'Lanjutkan Belajar' : 'Mulai Belajar' }}
                                <x-heroicon-o-arrow-right class="h-5 w-5 transition-transform group-hover:translate-x-0.5" />
                            </a>
                            <p class="mt-2.5 flex items-center gap-1.5 text-sm text-on-surface-variant">
                                <x-heroicon-o-arrow-turn-down-right class="h-4 w-4 shrink-0 text-outline" />
                                {{ $progress ? 'Lanjut ke materi' : 'Mulai dari materi' }}:
                                <span class="font-semibold text-on-surface">{{ $nextPage->judul }}</span>
                            </p>
                        @endif
                    </div>
                @endif
            </div>
        </x-portal.card>

        {{-- ============ DAFTAR MATERI (kanan, sticky + scroll internal) ============ --}}
        {{-- row-span-2 = beri ruang gerak sticky sejajar kolom kiri (hero + kuis/diskusi). --}}
        <x-portal.card :padded="false" class="lg:col-start-3 lg:row-span-2 lg:self-start lg:sticky lg:top-20">
            <div class="flex items-center justify-between gap-3 border-b border-outline-variant px-5 py-4">
                <div>
                    <h2 class="font-bold text-on-surface">Daftar Materi</h2>
                    <p class="mt-0.5 text-xs text-on-surface-variant">{{ $total }} materi · {{ $done }} selesai</p>
                </div>
                @if($total > 0)
                    <span class="text-sm font-bold {{ $isCompleted ? 'text-sdg-3' : 'text-primary' }}">{{ $pct }}%</span>
                @endif
            </div>

            @if($pages->isEmpty())
                <p class="px-5 py-12 text-center text-sm text-on-surface-variant">Belum ada materi tersedia.</p>
            @else
                {{-- Materi banyak: scroll di dalam kartu (desktop) agar halaman tak memanjang. --}}
                <ol class="divide-y divide-outline-variant lg:max-h-[calc(100vh-9rem)] lg:overflow-y-auto">
                    @foreach($pages as $p)
                        @php
                            $pDone = in_array($p->id, $pagesCompleted);
                            $isNext = ! $isCompleted && ! $pDone && $p->id === $nextPage->id;
                            $locked = $firstIncompleteIdx !== false && $loop->index > $firstIncompleteIdx;
                            $pTypes = collect($p->blocks ?? [])->pluck('type')->unique()
                                ->map(fn ($t) => \App\Enums\ModuleBlockType::tryFrom($t))->filter();
                        @endphp
                        <li>
                            @php
                                $rowInner = $locked
                                    ? 'div'
                                    : 'a';
                            @endphp
                            <{{ $rowInner }}
                                @unless($locked) href="{{ route('portal.modules.pages.show', [$module, $p]) }}" @endunless
                                class="group flex items-center gap-3 px-5 py-3.5 {{ $locked ? 'cursor-not-allowed opacity-60' : 'transition-colors hover:bg-surface-container-low' }} {{ $isNext ? 'bg-primary/5' : '' }}"
                                @if($locked) title="Selesaikan materi sebelumnya dulu" @endif>
                                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full text-xs font-bold
                                    {{ $pDone ? 'bg-sdg-3/10 text-sdg-3' : ($isNext ? 'bg-primary text-on-primary' : 'bg-surface-container-high text-on-surface-variant') }}">
                                    @if($pDone)
                                        <x-heroicon-s-check class="h-4 w-4" />
                                    @elseif($locked)
                                        <x-heroicon-s-lock-closed class="h-3.5 w-3.5 text-outline" />
                                    @else
                                        {{ $loop->iteration }}
                                    @endif
                                </span>

                                <span class="min-w-0 flex-1">
                                    <span class="flex items-center gap-2">
                                        <span class="truncate text-sm font-semibold {{ $pDone || $locked ? 'text-on-surface-variant' : 'text-on-surface' }}">
                                            {{ $p->judul }}
                                        </span>
                                        @if($isNext)
                                            <span class="shrink-0 rounded-full bg-primary px-2 py-0.5 text-[0.65rem] font-bold uppercase tracking-wide text-on-primary">
                                                {{ $progress ? 'Lanjut' : 'Mulai' }}
                                            </span>
                                        @endif
                                    </span>
                                    <span class="mt-0.5 flex items-center gap-1.5 text-xs text-on-surface-variant">
                                        @if($pDone)
                                            <x-heroicon-s-check-circle class="h-3.5 w-3.5 text-sdg-3" /> Selesai dibaca
                                        @elseif($locked)
                                            <x-heroicon-s-lock-closed class="h-3 w-3" /> Terkunci
                                        @elseif($pTypes->isNotEmpty())
                                            @foreach($pTypes as $bt)
                                                <span class="inline-flex items-center gap-1">
                                                    <x-dynamic-component :component="$bt->getIcon()" class="h-3.5 w-3.5" />
                                                    {{ $bt->getLabel() }}
                                                </span>
                                            @endforeach
                                        @else
                                            Materi bacaan
                                        @endif
                                    </span>
                                </span>

                                @unless($locked)
                                    <x-heroicon-o-chevron-right class="h-5 w-5 shrink-0 text-outline-variant transition-transform group-hover:translate-x-0.5 group-hover:text-primary" />
                                @endunless
                            </{{ $rowInner }}>
                        </li>
                    @endforeach
                </ol>
            @endif
        </x-portal.card>

        {{-- ============ KUIS + DISKUSI (kiri-bawah / sekunder) ============ --}}
        <div class="space-y-4 lg:col-span-2">
            @if($hasQuiz)
                @if($isCompleted)
                    <a href="{{ route('portal.modules.quiz', $module) }}"
                        class="group flex items-center justify-between gap-4 rounded-2xl border border-primary/20 bg-primary/5 p-5 transition-colors hover:bg-primary/10">
                        <div class="flex items-center gap-3">
                            <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary text-on-primary">
                                <x-heroicon-s-clipboard-document-check class="h-6 w-6" />
                            </span>
                            <div>
                                <p class="font-bold text-primary">Kerjakan Kuis</p>
                                <p class="mt-0.5 text-sm text-on-surface-variant">Uji pemahamanmu untuk menyelesaikan modul.</p>
                            </div>
                        </div>
                        <x-heroicon-o-arrow-right class="h-5 w-5 shrink-0 text-primary transition-transform group-hover:translate-x-0.5" />
                    </a>
                @else
                    <div class="flex items-center gap-3 rounded-2xl border border-outline-variant bg-surface-container p-5">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-surface-container-high text-outline">
                            <x-heroicon-s-lock-closed class="h-5 w-5" />
                        </span>
                        <div>
                            <p class="font-bold text-on-surface-variant">Kuis Terkunci</p>
                            <p class="mt-0.5 text-sm text-outline">Selesaikan semua materi untuk membuka kuis.</p>
                        </div>
                    </div>
                @endif
            @endif

            <a href="{{ route('portal.modules.discuss', $module) }}"
                class="group flex items-center justify-between gap-4 rounded-2xl border border-outline-variant bg-surface-container-lowest p-5 shadow-sm transition-colors hover:bg-surface-container-low">
                <div class="flex items-center gap-3">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-secondary-container text-on-secondary-container">
                        <x-heroicon-s-chat-bubble-left-right class="h-6 w-6" />
                    </span>
                    <div>
                        <p class="font-bold text-on-surface">Ruang Diskusi</p>
                        <p class="mt-0.5 text-sm text-on-surface-variant">Tanya jawab seputar modul ini.</p>
                    </div>
                </div>
                <x-heroicon-o-arrow-right class="h-5 w-5 shrink-0 text-outline-variant transition-transform group-hover:translate-x-0.5" />
            </a>
        </div>
    </div>
@endsection
