@extends('public.layouts.app')

@php
    $judulSitus = $nagari ? 'Kabar Nagari ' . $nagari->nama : 'Kabar Nagari dan Informasi Terkini';
    $deskripsiHalaman = $nagari
        ? 'Kumpulan berita resmi, pengumuman pemerintahan, dan agenda kegiatan dari Nagari ' . $nagari->nama . '.'
        : 'Informasi resmi, siaran pers, pengumuman dan agenda seputar nagari dan ekosistem BASAMO NCH.';
    
    $detailUrl = function (\App\Models\Berita $item) use ($nagari) {
        if ($nagari) {
            return \App\Support\PublicNavigation::rute('public.nagari.kabar.detail', $nagari, ['berita' => $item->slug]);
        }
        return route('public.kabar.detail', ['berita' => $item->slug]);
    };

    $filterUrl = function (?string $kategoriKey = null, ?string $nagariKey = null) use ($nagari, $search) {
        $params = [];
        if ($kategoriKey) {
            $params['kategori'] = $kategoriKey;
        }
        if ($nagariKey) {
            $params['nagari'] = $nagariKey;
        }
        if ($search) {
            $params['cari'] = $search;
        }

        if ($nagari) {
            return \App\Support\PublicNavigation::rute('public.nagari.kabar', $nagari, $params);
        }
        return route('public.kabar', $params);
    };
@endphp

@section('title', $judulSitus)
@section('meta_description', $deskripsiHalaman)
@section('main-class', 'w-full')

@section('content')
{{-- ══ HERO / KEPALA HALAMAN ═══════════════════════════════════════════════ --}}
<x-public.pillar-header
    eyebrow="{{ $nagari ? 'Kabar dan Publikasi Nagari' : 'Portal Informasi dan Berita' }}"
    title="{{ $nagari ? 'Kabar Terkini Nagari ' . $nagari->nama : 'Kabar Terkini, Pengumuman dan Agenda' }}"
    description="{{ $deskripsiHalaman }}"
/>

{{-- ══ PENYARING & PENCARIAN ══════════════════════════════════════════════ --}}
<section class="border-b border-outline-variant bg-surface-container-lowest py-5">
    <div class="mx-auto max-w-container-page px-margin-mobile lg:px-margin-page">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
            {{-- Tab Kategori --}}
            <div class="flex flex-wrap items-center gap-1.5" role="tablist" aria-label="Penyaring Kategori">
                <a href="{{ $filterUrl(null, $currentNagari) }}"
                   class="inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-bold transition-all duration-200 {{ empty($currentKategori) ? 'bg-primary text-on-primary shadow-sm' : 'border border-outline-variant bg-white text-on-surface-variant hover:bg-primary/5 hover:text-primary' }}">
                    <span>Semua Kabar</span>
                    <span class="rounded-full px-1.5 py-0.2 text-[10px] {{ empty($currentKategori) ? 'bg-white/20 text-on-primary' : 'bg-surface-container-high text-on-surface-variant' }}">
                        {{ $kategoriCounts['semua'] ?? 0 }}
                    </span>
                </a>

                <a href="{{ $filterUrl('berita', $currentNagari) }}"
                   class="inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-bold transition-all duration-200 {{ $currentKategori === 'berita' ? 'bg-primary text-on-primary shadow-sm' : 'border border-outline-variant bg-white text-on-surface-variant hover:bg-primary/5 hover:text-primary' }}">
                    <span class="h-2 w-2 rounded-full bg-blue-500"></span>
                    <span>Berita</span>
                    <span class="rounded-full px-1.5 py-0.2 text-[10px] {{ $currentKategori === 'berita' ? 'bg-white/20 text-on-primary' : 'bg-surface-container-high text-on-surface-variant' }}">
                        {{ $kategoriCounts['berita'] ?? 0 }}
                    </span>
                </a>

                <a href="{{ $filterUrl('pengumuman', $currentNagari) }}"
                   class="inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-bold transition-all duration-200 {{ $currentKategori === 'pengumuman' ? 'bg-primary text-on-primary shadow-sm' : 'border border-outline-variant bg-white text-on-surface-variant hover:bg-primary/5 hover:text-primary' }}">
                    <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                    <span>Pengumuman</span>
                    <span class="rounded-full px-1.5 py-0.2 text-[10px] {{ $currentKategori === 'pengumuman' ? 'bg-white/20 text-on-primary' : 'bg-surface-container-high text-on-surface-variant' }}">
                        {{ $kategoriCounts['pengumuman'] ?? 0 }}
                    </span>
                </a>

                <a href="{{ $filterUrl('agenda', $currentNagari) }}"
                   class="inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-bold transition-all duration-200 {{ $currentKategori === 'agenda' ? 'bg-primary text-on-primary shadow-sm' : 'border border-outline-variant bg-white text-on-surface-variant hover:bg-primary/5 hover:text-primary' }}">
                    <span class="h-2 w-2 rounded-full bg-purple-500"></span>
                    <span>Agenda</span>
                    <span class="rounded-full px-1.5 py-0.2 text-[10px] {{ $currentKategori === 'agenda' ? 'bg-white/20 text-on-primary' : 'bg-surface-container-high text-on-surface-variant' }}">
                        {{ $kategoriCounts['agenda'] ?? 0 }}
                    </span>
                </a>
            </div>

            {{-- Form Pencarian & Filter Nagari --}}
            <form method="GET" action="{{ $nagari ? \App\Support\PublicNavigation::rute('public.nagari.kabar', $nagari) : route('public.kabar') }}" class="flex flex-wrap items-center gap-2.5">
                @if($currentKategori)
                    <input type="hidden" name="kategori" value="{{ $currentKategori }}">
                @endif

                @unless($nagari)
                    @if($allNagaris->isNotEmpty())
                        <div class="relative min-w-40">
                            <select name="nagari" onchange="this.form.submit()" class="h-9 w-full rounded-xl border border-control-border bg-white px-3 pr-8 text-xs font-medium text-on-surface shadow-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20">
                                <option value="">Semua Nagari</option>
                                @foreach($allNagaris as $n)
                                    <option value="{{ $n->slug }}" @selected($currentNagari === $n->slug)>{{ $n->nama }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                @endunless

                <div class="relative flex-1 sm:w-60">
                    <x-heroicon-o-magnifying-glass class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-on-surface-variant" />
                    <input type="search" name="cari" value="{{ $search }}" placeholder="Cari kabar / topik..."
                           class="h-9 w-full rounded-xl border border-control-border bg-white pl-8 pr-3 text-xs text-on-surface shadow-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20">
                </div>

                @if($search || $currentKategori || $currentNagari)
                    <a href="{{ $nagari ? \App\Support\PublicNavigation::rute('public.nagari.kabar', $nagari) : route('public.kabar') }}"
                       class="inline-flex h-9 items-center gap-1 rounded-xl border border-control-border bg-white px-2.5 text-xs font-bold text-primary transition hover:border-primary hover:bg-primary/5">
                        <x-heroicon-o-x-mark class="h-3.5 w-3.5" />
                        <span>Reset</span>
                    </a>
                @endif
            </form>
        </div>
    </div>
</section>

{{-- ══ KONTEN UTAMA ═══════════════════════════════════════════════════════ --}}
<div class="bg-background py-8 lg:py-12">
    <div class="mx-auto max-w-container-page px-margin-mobile lg:px-margin-page">

        {{-- 1. SOROTAN UTAMA (PINNED HIGHLIGHT) --}}
        @if($pinned)
            <div class="mb-10">
                <div class="mb-3 flex items-center gap-1.5 text-xs font-black uppercase tracking-wider text-primary">
                    <x-heroicon-s-star class="h-4 w-4 text-amber-500" />
                    <span>Sorotan Utama</span>
                </div>

                <article class="group relative overflow-hidden rounded-2xl border border-outline-variant bg-surface-container-lowest shadow-sm transition-all duration-300 hover:border-primary/40 hover:shadow-md">
                    <div class="grid grid-cols-1 md:grid-cols-12">
                        {{-- Foto Sampul --}}
                        <a href="{{ $detailUrl($pinned) }}" class="relative block aspect-[16/10] overflow-hidden bg-surface-container-high md:col-span-5 md:aspect-auto md:min-h-[240px] md:max-h-[280px]">
                            @if($pinned->sampulUrl('card'))
                                <img src="{{ $pinned->sampulUrl('card') }}" alt="{{ $pinned->judul }}"
                                     class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" loading="lazy">
                            @else
                                <div class="flex h-full min-h-[180px] w-full items-center justify-center bg-gradient-to-br from-primary/10 via-primary/5 to-surface-container-low text-primary/30">
                                    <x-heroicon-o-newspaper class="h-14 w-14" />
                                </div>
                            @endif

                            {{-- Badges --}}
                            <div class="absolute left-3 top-3 flex flex-wrap gap-1.5">
                                <span class="rounded-full px-2.5 py-0.5 text-[9px] font-black uppercase tracking-wider shadow-sm {{ $pinned->kategori->badgeColor() }}">
                                    {{ $pinned->kategori->getLabel() }}
                                </span>
                                <span class="rounded-full bg-amber-500 px-2.5 py-0.5 text-[9px] font-black uppercase tracking-wider text-white shadow-sm flex items-center gap-0.5">
                                    <x-heroicon-s-bookmark class="h-3 w-3" /> Pinned
                                </span>
                            </div>
                        </a>

                        {{-- Teks Cuplikan --}}
                        <div class="flex flex-col justify-between p-5 sm:p-6 md:col-span-7">
                            <div>
                                <div class="flex flex-wrap items-center gap-2 text-xs font-semibold text-on-surface-variant">
                                    <span>{{ $pinned->published_at?->translatedFormat('d F Y') ?? 'Baru saja' }}</span>
                                    <span>•</span>
                                    <span>{{ $pinned->readingTime() }} mnt baca</span>
                                    <span>•</span>
                                    <span class="rounded-md bg-surface-container-high px-2 py-0.5 text-[10px] font-bold text-primary">
                                        {{ $pinned->targetAudienceLabel() }}
                                    </span>
                                </div>

                                <h2 class="mt-2 text-base font-black leading-snug text-primary transition-colors group-hover:text-primary sm:text-lg">
                                    <a href="{{ $detailUrl($pinned) }}" class="focus:outline-none">
                                        {{ $pinned->judul }}
                                    </a>
                                </h2>

                                <p class="mt-2 line-clamp-3 text-xs leading-relaxed text-on-surface-variant">
                                    {{ $pinned->ringkasan ?: \Illuminate\Support\Str::limit(strip_tags((string) $pinned->konten), 160) }}
                                </p>
                            </div>

                            <div class="mt-5 flex items-center justify-between border-t border-outline-variant/60 pt-3">
                                <div class="flex items-center gap-1.5 text-xs font-medium text-on-surface-variant">
                                    <x-heroicon-o-user-circle class="h-4 w-4 text-primary" />
                                    <span>{{ $pinned->authorLabel() }}</span>
                                </div>

                                <a href="{{ $detailUrl($pinned) }}"
                                   class="inline-flex items-center gap-1 text-xs font-bold text-primary transition-all group-hover:translate-x-1">
                                    <span>Baca Selengkapnya</span>
                                    <x-heroicon-o-arrow-right class="h-3.5 w-3.5" />
                                </a>
                            </div>
                        </div>
                    </div>
                </article>
            </div>
        @endif

        {{-- 2. GRID DAFTAR BERITA --}}
        @if($beritas->isNotEmpty())
            <div class="mb-6 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <h3 class="text-sm font-extrabold uppercase tracking-wider text-primary sm:text-base">
                        {{ $currentKategori ? 'Kategori ' . ucfirst($currentKategori) : 'Kabar Terbaru' }}
                    </h3>
                    <span class="text-xs text-on-surface-variant font-medium">({{ $beritas->total() }} rilis)</span>
                </div>
            </div>

            {{-- Grid 4-kolom kompak dan proporsional --}}
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach($beritas as $item)
                    <x-berita.card :berita="$item" :nagari="$nagari" />
                @endforeach
            </div>

            {{-- Navigasi Paginasi --}}
            <div class="mt-10">
                {{ $beritas->links('public.pagination', ['label' => 'kabar']) }}
            </div>
        @elseif(! $pinned)
            {{-- State Kosong --}}
            <div class="py-12">
                <x-public.empty-state
                    icon="heroicon-o-newspaper"
                    title="Belum ada kabar atau pengumuman"
                    description="{{ $search ? 'Tidak ditemukan publikasi dengan kata kunci \''.$search.'\'.' : 'Saat ini belum ada publikasi berita atau pengumuman yang diterbitkan.' }}"
                >
                    @if($search || $currentKategori || $currentNagari)
                        <a href="{{ $nagari ? \App\Support\PublicNavigation::rute('public.nagari.kabar', $nagari) : route('public.kabar') }}"
                           class="inline-flex items-center gap-2 rounded-xl bg-primary px-4 py-2 text-xs font-bold text-on-primary">
                            <span>Tampilkan Semua Kabar</span>
                        </a>
                    @endif
                </x-public.empty-state>
            </div>
        @endif

    </div>
</div>
@endsection
