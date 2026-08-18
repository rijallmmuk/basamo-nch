@extends('public.layouts.app')

@section('title', 'Teras Nagari')
@section('meta_description', 'Jelajahi data pembangunan, pembelajaran, ekonomi, cuaca, dan IoT nagari mitra BASAMO NCH melalui peta interaktif dan dasbor analitik lintas nagari.')
@section('main-class', 'w-full')

@section('content')
@php
    $jumlahPenduduk = (int) $performa->sum(fn ($nagari) => (int) $nagari->total_penduduk);
    $jumlahUmkm = (int) $performa->sum(fn ($nagari) => (int) $nagari->total_umkm);
    $jumlahProduk = (int) $performa->sum(fn ($nagari) => (int) $nagari->total_produk);
    $jumlahIot = (int) $performa->sum(fn ($nagari) => (int) $nagari->total_iot);
    $jumlahModulSelesai = (int) $performa->sum(fn ($nagari) => (int) $nagari->modul_selesai);
    $jumlahWargaBelajar = (int) $performa->sum(fn ($nagari) => (int) $nagari->warga_belajar);
@endphp

{{-- ══ PETA INTERAKTIF TERAS NAGARI ════════════════════════════════════ --}}
<section
    data-teras-map
    data-boundary-url="{{ route('public.teras.map.boundaries') }}"
    data-kabupaten-url="{{ route('public.teras.map.kabupaten') }}"
    data-marker-url="{{ route('public.teras.map.data') }}"
    class="relative isolate min-h-[calc(100svh-4.75rem)] overflow-hidden bg-surface-container">
    <div id="teras-map-canvas" class="absolute inset-0 z-0" aria-label="Peta nagari mitra BASAMO NCH"></div>

    <div class="pointer-events-none absolute inset-x-0 top-0 z-[400] h-28 bg-gradient-to-b from-primary/25 to-transparent" aria-hidden="true"></div>

    <div id="teras-map-status" class="pointer-events-none absolute inset-0 z-[450] flex items-center justify-center bg-surface-container/80 px-6 text-center text-sm font-semibold text-on-surface-variant backdrop-blur-sm" role="status" aria-live="polite">
        Memuat peta, batas kabupaten, dan nagari mitra…
    </div>

    {{-- ══ PANEL DAFTAR & FILTER NAGARI (MOBILE-FIRST RESPONSIVE DRAWER) ══ --}}
    <aside data-teras-drawer data-collapsed="false"
           class="absolute inset-x-2 bottom-2 z-[500] flex max-h-[78svh] flex-col overflow-hidden rounded-3xl border border-white/80 bg-surface-container-lowest/95 shadow-2xl backdrop-blur-xl transition-all duration-300 sm:inset-x-auto sm:bottom-5 sm:left-5 sm:top-5 sm:w-[24rem] sm:max-h-none lg:left-8 lg:top-8 lg:w-[26rem]"
           aria-label="Daftar nagari mitra">

        {{-- Mobile Drag / Toggle Bar --}}
        <div class="flex shrink-0 items-center justify-center pt-2 pb-1 sm:hidden">
            <button type="button" data-teras-drawer-toggle class="flex h-4 w-full items-center justify-center" aria-label="Buka atau tutup daftar nagari">
                <span class="h-1.5 w-12 rounded-full bg-outline-variant/80 transition hover:bg-primary"></span>
            </button>
        </div>

        {{-- Header Panel --}}
        <div class="shrink-0 border-b border-outline-variant px-4 py-3 sm:p-5">
            <div class="flex items-center justify-between gap-2">
                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-secondary sm:text-[11px]">Pilar 1 · Teras Nagari</p>
                <div class="flex items-center gap-2">
                    <a href="#analisis-lintas-nagari" class="inline-flex items-center gap-1 text-[11px] font-bold text-primary hover:underline">
                        Lihat Dasbor <x-heroicon-m-arrow-down class="h-3.5 w-3.5" />
                    </a>
                    <button type="button" data-teras-drawer-toggle class="flex h-7 w-7 items-center justify-center rounded-lg bg-surface-container-low text-primary transition hover:bg-primary hover:text-white sm:hidden" aria-label="Buka/Tutup Ringkasan">
                        <x-heroicon-o-chevron-down data-drawer-icon-open class="h-4 w-4" />
                        <x-heroicon-o-chevron-up data-drawer-icon-close class="h-4 w-4 hidden" />
                    </button>
                </div>
            </div>

            <h1 class="mt-1 text-xl font-black tracking-tight text-primary sm:text-2xl lg:text-3xl">Jelajahi nagari melalui peta.</h1>
            <p class="mt-1 text-xs leading-relaxed text-on-surface-variant hidden sm:block">Pilih batas wilayah kabupaten atau nagari untuk membuka data publiknya. Gunakan filter untuk menyeleksi nagari.</p>

            <div data-drawer-body class="transition-all duration-300">
                {{-- Metrik Ringkas --}}
                <dl class="mt-3 grid grid-cols-4 gap-1.5 text-center">
                    @foreach([
                        ['Nagari', $performa->count()],
                        ['Penduduk', $jumlahPenduduk],
                        ['UMKM', $jumlahUmkm],
                        ['IoT', $jumlahIot],
                    ] as [$label, $value])
                        <div class="rounded-xl bg-surface-container-low px-1 py-1.5 sm:px-1.5 sm:py-2">
                            <dd class="text-xs font-black tabular-nums text-primary sm:text-sm">{{ number_format($value, 0, ',', '.') }}</dd>
                            <dt class="mt-0.5 truncate text-[9px] font-bold uppercase tracking-wide text-on-surface-variant">{{ $label }}</dt>
                        </div>
                    @endforeach
                </dl>

                {{-- Filter Cepat (Chips) - Horizontal Scroll di Mobile (No Scrollbar) --}}
                <div class="mt-3 flex gap-1.5 overflow-x-auto pb-1 text-xs font-bold [scrollbar-width:none] [&::-webkit-scrollbar]:hidden sm:flex-wrap sm:overflow-visible sm:pb-0" data-teras-filter-chips>
                    <button type="button" data-filter="all" class="teras-chip active shrink-0 rounded-lg border border-primary bg-primary px-2.5 py-1 text-[11px] font-extrabold text-on-primary transition">
                        Semua
                    </button>
                    <button type="button" data-filter="iot" class="teras-chip shrink-0 rounded-lg border border-outline-variant bg-background px-2.5 py-1 text-[11px] font-semibold text-on-surface transition hover:border-primary/40">
                        Ada IoT ({{ $performa->where('total_iot', '>', 0)->count() }})
                    </button>
                    <button type="button" data-filter="mandiri-maju" class="teras-chip shrink-0 rounded-lg border border-outline-variant bg-background px-2.5 py-1 text-[11px] font-semibold text-on-surface transition hover:border-primary/40">
                        IDM Mandiri/Maju
                    </button>
                    <button type="button" data-filter="sdgs" class="teras-chip shrink-0 rounded-lg border border-outline-variant bg-background px-2.5 py-1 text-[11px] font-semibold text-on-surface transition hover:border-primary/40">
                        SDGs Terisi
                    </button>
                </div>

                {{-- Pencarian & Filter Wilayah --}}
                <div class="mt-2.5 grid grid-cols-1 gap-2 sm:grid-cols-2">
                    <div>
                        <label class="sr-only" for="teras-filter-kabupaten">Filter Kabupaten/Kota</label>
                        <select id="teras-filter-kabupaten" data-teras-filter-kabupaten
                                class="min-h-9 w-full rounded-xl border border-control-border bg-background px-2.5 py-1.5 text-xs font-semibold text-on-surface outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20">
                            <option value="">Semua Wilayah</option>
                            @foreach($kabupatens as $kab)
                                <option value="{{ $kab }}">{{ $kab }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="relative">
                        <label class="sr-only" for="teras-search-input">Cari nagari</label>
                        <x-heroicon-o-magnifying-glass class="pointer-events-none absolute left-3 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-on-surface-variant" />
                        <input id="teras-search-input" type="search" data-teras-search placeholder="Cari nama/kecamatan…" autocomplete="off"
                               class="min-h-9 w-full rounded-xl border border-control-border bg-background py-1.5 pl-8 pr-3 text-xs text-on-surface outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20">
                    </div>
                </div>
            </div>
        </div>

        {{-- Daftar Item Nagari --}}
        <div data-drawer-body class="min-h-0 flex-1 overflow-y-auto p-2" data-teras-list>
            @forelse($performa as $nagari)
                @php
                    $idmLabel = $nagari->status_idm ? (\App\Enums\StatusIdm::tryFrom((string) $nagari->status_idm)?->label() ?? (string) $nagari->status_idm) : null;
                    $idmColor = $nagari->status_idm ? (\App\Enums\StatusIdm::tryFrom((string) $nagari->status_idm)?->color() ?? 'gray') : 'gray';
                    $hasIot = (int) $nagari->total_iot > 0;
                    $hasSdgs = (int) $nagari->sdg_terisi > 0;
                @endphp
                <a href="{{ \App\Support\PublicNavigation::rute('public.nagari.teras', $nagari) }}"
                   data-teras-nagari
                   data-slug="{{ $nagari->slug }}"
                   data-detail-url="{{ route('public.teras.nagari.data', $nagari) }}"
                   data-search="{{ str($nagari->nama.' '.$nagari->kecamatan.' '.$nagari->kabupaten)->lower() }}"
                   data-kabupaten="{{ $nagari->kabupaten }}"
                   data-idm="{{ $nagari->status_idm }}"
                   data-sdgs="{{ (float) $nagari->skor_sdgs }}"
                   data-sdg-terisi="{{ (int) $nagari->sdg_terisi }}"
                   data-iot="{{ (int) $nagari->total_iot }}"
                   class="group flex items-center gap-3 rounded-2xl p-2.5 transition hover:bg-primary/7 focus-visible:bg-primary/7 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/40">
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/8 text-primary transition group-hover:bg-primary group-hover:text-on-primary">
                        <x-heroicon-o-map-pin class="h-5 w-5" />
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="flex items-center gap-1.5">
                            <span class="truncate text-sm font-extrabold text-primary">{{ $nagari->nama_lengkap }}</span>
                            @if($hasIot)
                                <span class="inline-flex items-center rounded-md bg-emerald-100 px-1.5 py-0.5 text-[9px] font-black text-emerald-800" title="Titik IoT Aktif">IoT</span>
                            @endif
                        </span>
                        <span class="mt-0.5 block truncate text-[11px] text-on-surface-variant">{{ collect([$nagari->kecamatan, $nagari->kabupaten])->filter()->implode(' · ') }}</span>
                        <span class="mt-1 flex flex-wrap items-center gap-1">
                            @if($idmLabel)
                                <span class="rounded bg-surface-container-high px-1.5 py-0.5 text-[9px] font-bold text-on-surface-variant">{{ $idmLabel }}</span>
                            @endif
                            @if($hasSdgs)
                                <span class="rounded bg-primary/10 px-1.5 py-0.5 text-[9px] font-black text-primary">SDGs {{ number_format((float) $nagari->skor_sdgs, 1, ',', '.') }}%</span>
                            @endif
                        </span>
                    </span>
                    <span class="shrink-0 text-right text-[10px] leading-snug text-on-surface-variant">
                        <span class="font-bold text-on-surface">{{ number_format((int) $nagari->total_penduduk, 0, ',', '.') }}</span> jiwa<br>
                        <span class="font-bold text-on-surface">{{ number_format((int) $nagari->total_umkm, 0, ',', '.') }}</span> UMKM
                    </span>
                </a>
            @empty
                <div class="p-6 text-center text-sm text-on-surface-variant">
                    <x-heroicon-o-map-pin class="mx-auto h-8 w-8 text-outline" />
                    <p class="mt-3 font-bold text-on-surface">Belum ada nagari mitra aktif</p>
                </div>
            @endforelse
            <p data-teras-search-empty class="hidden p-6 text-center text-sm text-on-surface-variant">Nagari tidak ditemukan untuk filter ini.</p>
        </div>
    </aside>

    {{-- Toolbar Kontrol Layer & Peta di Kanan Atas --}}
    <div class="absolute right-3 top-3 z-[500] flex flex-col items-end gap-2 sm:right-5 sm:top-5">
        <div class="flex items-center gap-1.5 rounded-2xl border border-white/70 bg-surface-container-lowest/95 p-1.5 shadow-xl backdrop-blur-xl">
            <button type="button" data-teras-toggle-kabupaten class="flex items-center gap-1.5 rounded-xl bg-primary/10 px-3 py-1.5 text-xs font-black text-primary transition hover:bg-primary hover:text-on-primary active:scale-95" title="Tampilkan/Sembunyikan Batas Kabupaten/Kota">
                <span class="h-2.5 w-2.5 rounded-full bg-primary ring-2 ring-primary/30"></span>
                <span>Batas Kabupaten</span>
            </button>
            <button type="button" data-teras-toggle-nagari class="flex items-center gap-1.5 rounded-xl bg-emerald-50 px-3 py-1.5 text-xs font-black text-emerald-800 transition hover:bg-emerald-600 hover:text-white active:scale-95" title="Tampilkan/Sembunyikan Poligon Nagari">
                <span class="h-2.5 w-2.5 rounded-full bg-emerald-500 ring-2 ring-emerald-300"></span>
                <span>Nagari</span>
            </button>
            <button type="button" data-teras-toggle-iot class="flex items-center gap-1.5 rounded-xl bg-surface-container-low px-3 py-1.5 text-xs font-black text-on-surface-variant transition hover:bg-teal-600 hover:text-white active:scale-95" title="Fokus Titik IoT/EWS">
                <span class="h-2.5 w-2.5 rounded-full bg-teal-500 ring-2 ring-teal-300"></span>
                <span>IoT</span>
            </button>
            <button type="button" data-teras-reset-map class="flex h-8 w-8 items-center justify-center rounded-xl bg-surface-container-low text-primary transition hover:bg-primary hover:text-on-primary active:scale-95" title="Pusatkan Seluruh Sumatera Barat">
                <x-heroicon-o-globe-asia-australia class="h-4 w-4" />
            </button>
        </div>
    </div>

    {{-- Legenda Peta di Kanan Bawah --}}
    <div class="absolute bottom-3 right-3 z-[500] sm:bottom-5 sm:right-5">
        <details class="group rounded-2xl border border-white/70 bg-surface-container-lowest/95 shadow-xl backdrop-blur-xl">
            <summary class="flex cursor-pointer list-none items-center gap-2 px-3 py-2 text-xs font-extrabold text-primary select-none">
                <x-heroicon-o-information-circle class="h-4 w-4 text-primary" />
                <span>Legenda Peta</span>
                <span class="text-[10px] text-on-surface-variant transition group-open:rotate-180">⌄</span>
            </summary>
            <div class="space-y-2 border-t border-outline-variant/60 p-3 text-[11px]">
                <div class="flex items-center gap-2">
                    <span class="h-2.5 w-5 rounded border border-slate-700 bg-slate-200/50"></span>
                    <span class="font-bold text-on-surface">Batas Kabupaten/Kota</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="h-2.5 w-5 rounded border border-emerald-700 bg-emerald-300"></span>
                    <span class="font-bold text-on-surface">Nagari Mandiri</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="h-2.5 w-5 rounded border border-sky-700 bg-sky-300"></span>
                    <span class="font-bold text-on-surface">Nagari Maju</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="h-2.5 w-5 rounded border border-amber-700 bg-amber-300"></span>
                    <span class="font-bold text-on-surface">Nagari Berkembang</span>
                </div>
                <div class="flex items-center gap-2">
                    <span class="relative flex h-3 w-3 items-center justify-center">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-600"></span>
                    </span>
                    <span class="font-bold text-on-surface">Titik Sensor IoT & EWS</span>
                </div>
            </div>
        </details>
    </div>

    <noscript>
        <div class="absolute inset-0 z-[600] flex items-center justify-center bg-background/95 p-6">
            <div class="max-w-md rounded-3xl border border-outline-variant bg-surface-container-lowest p-8 text-center shadow-xl">
                <x-heroicon-o-map class="mx-auto h-10 w-10 text-primary" />
                <h2 class="mt-4 text-xl font-black text-primary">Peta membutuhkan JavaScript</h2>
                <p class="mt-2 text-sm text-on-surface-variant">Daftar nagari tetap tersedia. Aktifkan JavaScript untuk menjelajahi titik pada peta.</p>
            </div>
        </div>
    </noscript>
</section>

{{-- ══ DASBOR ANALITIK & PERBANDINGAN LINTAS NAGARI ══════════════════════ --}}
<section id="analisis-lintas-nagari" class="border-t border-outline-variant bg-surface-container-lowest py-12 sm:py-20">
    <div class="mx-auto max-w-container-page px-margin-mobile lg:px-margin-page">
        <div class="flex flex-col justify-between gap-6 md:flex-row md:items-end">
            <x-public.section-heading
                eyebrow="Dasbor Ekosistem · Agregat Lintas Nagari"
                title="Transparansi dan capaian pembangunan."
                description="Perbandingan indikator kependudukan, ekonomi lokal, SDGs Desa, dan indeks kemandirian nagari mitra di Sumatera Barat."
            />
            <div class="shrink-0 rounded-2xl border border-outline-variant bg-background p-4 text-center shadow-sm">
                <p class="text-[11px] font-bold uppercase tracking-wider text-on-surface-variant">Cakupan Wilayah</p>
                <p class="text-2xl font-black tabular-nums text-primary">{{ $performa->count() }} Nagari</p>
                <p class="text-xs text-on-surface-variant">{{ $kabupatens->count() }} Kabupaten/Kota</p>
            </div>
        </div>

        {{-- Grid Statistik Ekosistem --}}
        <div class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6">
            <div class="rounded-2xl border border-outline-variant bg-background p-5 shadow-sm transition hover:border-primary/30">
                <div class="flex items-center justify-between">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                        <x-heroicon-o-map-pin class="h-5 w-5" />
                    </span>
                    <span class="text-xs font-bold text-on-surface-variant">{{ $kabupatens->count() }} Wilayah</span>
                </div>
                <p class="mt-3 text-2xl font-black tabular-nums text-primary">{{ number_format($performa->count(), 0, ',', '.') }}</p>
                <p class="mt-0.5 text-xs font-bold text-on-surface">Nagari Mitra Aktif</p>
                <p class="mt-1 text-[11px] text-on-surface-variant">Terhubung ekosistem BASAMO</p>
            </div>

            <div class="rounded-2xl border border-outline-variant bg-background p-5 shadow-sm transition hover:border-primary/30">
                <div class="flex items-center justify-between">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-sky-500/10 text-sky-600">
                        <x-heroicon-o-user-group class="h-5 w-5" />
                    </span>
                    <span class="text-xs font-bold text-on-surface-variant">SID Terdata</span>
                </div>
                <p class="mt-3 text-2xl font-black tabular-nums text-primary">{{ number_format($jumlahPenduduk, 0, ',', '.') }}</p>
                <p class="mt-0.5 text-xs font-bold text-on-surface">Total Penduduk</p>
                <p class="mt-1 text-[11px] text-on-surface-variant">Warga dalam sistem informasi</p>
            </div>

            <div class="rounded-2xl border border-outline-variant bg-background p-5 shadow-sm transition hover:border-primary/30">
                <div class="flex items-center justify-between">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-500/10 text-amber-600">
                        <x-heroicon-o-building-storefront class="h-5 w-5" />
                    </span>
                    <span class="text-xs font-bold text-on-surface-variant">{{ number_format($jumlahProduk, 0, ',', '.') }} Produk</span>
                </div>
                <p class="mt-3 text-2xl font-black tabular-nums text-primary">{{ number_format($jumlahUmkm, 0, ',', '.') }}</p>
                <p class="mt-0.5 text-xs font-bold text-on-surface">UMKM & Lapau</p>
                <p class="mt-1 text-[11px] text-on-surface-variant">Rumah usaha lokal aktif</p>
            </div>

            <div class="rounded-2xl border border-outline-variant bg-background p-5 shadow-sm transition hover:border-primary/30">
                <div class="flex items-center justify-between">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600">
                        <x-heroicon-o-chart-pie class="h-5 w-5" />
                    </span>
                    <span class="text-xs font-bold text-on-surface-variant">{{ $nagariBerSdgsCount }}/{{ $performa->count() }} Nagari</span>
                </div>
                <p class="mt-3 text-2xl font-black tabular-nums text-primary">
                    {{ $rataRataSdgs !== null ? number_format($rataRataSdgs, 1, ',', '.').'%' : '—' }}
                </p>
                <p class="mt-0.5 text-xs font-bold text-on-surface">Rata-rata SDGs</p>
                <p class="mt-1 text-[11px] text-on-surface-variant">Capaian 18 tujuan desa</p>
            </div>

            <div class="rounded-2xl border border-outline-variant bg-background p-5 shadow-sm transition hover:border-primary/30">
                <div class="flex items-center justify-between">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-500/10 text-indigo-600">
                        <x-heroicon-o-academic-cap class="h-5 w-5" />
                    </span>
                    <span class="text-xs font-bold text-on-surface-variant">{{ number_format($jumlahModulSelesai, 0, ',', '.') }} Selesai</span>
                </div>
                <p class="mt-3 text-2xl font-black tabular-nums text-primary">{{ number_format($jumlahWargaBelajar, 0, ',', '.') }}</p>
                <p class="mt-0.5 text-xs font-bold text-on-surface">Warga Belajar</p>
                <p class="mt-1 text-[11px] text-on-surface-variant">Mengikuti pelatihan digital</p>
            </div>

            <div class="rounded-2xl border border-outline-variant bg-background p-5 shadow-sm transition hover:border-primary/30">
                <div class="flex items-center justify-between">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-500/10 text-teal-600">
                        <x-heroicon-o-cpu-chip class="h-5 w-5" />
                    </span>
                    <span class="text-xs font-bold text-emerald-700">Real-time</span>
                </div>
                <p class="mt-3 text-2xl font-black tabular-nums text-primary">{{ number_format($jumlahIot, 0, ',', '.') }}</p>
                <p class="mt-0.5 text-xs font-bold text-on-surface">Titik Pantau IoT</p>
                <p class="mt-1 text-[11px] text-on-surface-variant">Sensor EWS & Kebencanaan</p>
            </div>
        </div>

        {{-- Ringkasan Sebaran Status IDM Ekosistem --}}
        <div class="mt-6 rounded-2xl border border-outline-variant bg-background p-6 shadow-sm">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <h3 class="text-sm font-extrabold text-primary">Sebaran Status Indeks Desa Membangun (IDM)</h3>
                    <p class="mt-0.5 text-xs text-on-surface-variant">Klasifikasi tingkat kemandirian nagari mitra berdasarkan penilaian resmi Kemendesa.</p>
                </div>
            </div>
            <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                @foreach([
                    'MANDIRI' => ['label' => 'Mandiri', 'color' => 'bg-emerald-500', 'textColor' => 'text-emerald-700', 'bgLight' => 'bg-emerald-50', 'border' => 'border-emerald-200'],
                    'MAJU' => ['label' => 'Maju', 'color' => 'bg-sky-500', 'textColor' => 'text-sky-700', 'bgLight' => 'bg-sky-50', 'border' => 'border-sky-200'],
                    'BERKEMBANG' => ['label' => 'Berkembang', 'color' => 'bg-amber-500', 'textColor' => 'text-amber-700', 'bgLight' => 'bg-amber-50', 'border' => 'border-amber-200'],
                    'TERTINGGAL' => ['label' => 'Tertinggal', 'color' => 'bg-rose-500', 'textColor' => 'text-rose-700', 'bgLight' => 'bg-rose-50', 'border' => 'border-rose-200'],
                ] as $key => $conf)
                    @php
                        $count = (int) ($idmCounts[$conf['label']] ?? ($idmCounts[$key] ?? 0));
                        $percentage = $performa->count() > 0 ? round(($count / $performa->count()) * 100, 1) : 0;
                    @endphp
                    <div class="rounded-xl border {{ $conf['border'] }} {{ $conf['bgLight'] }} p-3.5">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-extrabold {{ $conf['textColor'] }}">{{ $conf['label'] }}</span>
                            <span class="text-lg font-black tabular-nums text-primary">{{ $count }} <span class="text-[11px] font-semibold text-on-surface-variant">nagari</span></span>
                        </div>
                        <div class="mt-2 h-1.5 overflow-hidden rounded-full bg-white/80">
                            <div class="h-full rounded-full {{ $conf['color'] }}" style="width: {{ $percentage }}%"></div>
                        </div>
                        <p class="mt-1 text-right text-[10px] font-bold text-on-surface-variant">{{ $percentage }}% dari total</p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Tabel Peringkat & Perbandingan Performa Antar-Nagari --}}
        <div class="mt-8 overflow-hidden rounded-3xl border border-outline-variant bg-background shadow-sm" data-teras-leaderboard>
            <div class="flex flex-col gap-4 border-b border-outline-variant bg-surface-container-low p-5 sm:flex-row sm:items-center sm:justify-between sm:px-7">
                <div>
                    <h3 class="text-base font-black text-primary sm:text-lg">Tabel Performa Antar-Nagari</h3>
                    <p class="mt-0.5 text-xs text-on-surface-variant">Klik baris atau tombol "Lihat di Peta" untuk membuka analisis lengkap dan lokasi nagari.</p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <label class="sr-only" for="leaderboard-search">Cari dalam tabel</label>
                    <input id="leaderboard-search" type="search" data-table-search placeholder="Cari nagari/kabupaten…"
                           class="min-h-10 rounded-xl border border-control-border bg-background px-3.5 py-2 text-xs text-on-surface outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20">

                    <label class="sr-only" for="leaderboard-kabupaten">Filter kabupaten</label>
                    <select id="leaderboard-kabupaten" data-table-filter-kabupaten
                            class="min-h-10 rounded-xl border border-control-border bg-background px-3 py-2 text-xs font-semibold text-on-surface outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20">
                        <option value="">Semua Wilayah</option>
                        @foreach($kabupatens as $kab)
                            <option value="{{ $kab }}">{{ $kab }}</option>
                        @endforeach
                    </select>

                    <label class="sr-only" for="leaderboard-idm">Filter IDM</label>
                    <select id="leaderboard-idm" data-table-filter-idm
                            class="min-h-10 rounded-xl border border-control-border bg-background px-3 py-2 text-xs font-semibold text-on-surface outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20">
                        <option value="">Semua Status IDM</option>
                        <option value="MANDIRI">Mandiri</option>
                        <option value="MAJU">Maju</option>
                        <option value="BERKEMBANG">Berkembang</option>
                        <option value="TERTINGGAL">Tertinggal</option>
                    </select>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[56rem] text-left text-xs sm:text-sm">
                    <thead class="bg-surface-container-low/70 text-[11px] font-extrabold uppercase tracking-wider text-on-surface-variant">
                        <tr>
                            <th class="px-5 py-3.5 text-center">No.</th>
                            <th class="px-5 py-3.5">Nama Nagari & Wilayah</th>
                            <th class="px-4 py-3.5 text-right">Penduduk</th>
                            <th class="px-4 py-3.5 text-right">UMKM</th>
                            <th class="px-4 py-3.5 text-right">Produk</th>
                            <th class="px-5 py-3.5 text-center">Skor SDGs</th>
                            <th class="px-5 py-3.5 text-center">Status IDM</th>
                            <th class="px-4 py-3.5 text-center">Titik IoT</th>
                            <th class="px-5 py-3.5 text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant" data-table-body>
                        @forelse($performa as $index => $nagari)
                            @php
                                $idmEnum = $nagari->status_idm ? \App\Enums\StatusIdm::tryFrom((string) $nagari->status_idm) : null;
                                $idmLabel = $idmEnum?->label() ?? ($nagari->status_idm ?: 'Belum terdata');
                                $sdgSkor = (float) $nagari->skor_sdgs;
                                $hasSdgs = (int) $nagari->sdg_terisi > 0;
                            @endphp
                            <tr class="transition hover:bg-primary/5"
                                data-table-row
                                data-slug="{{ $nagari->slug }}"
                                data-search="{{ str($nagari->nama.' '.$nagari->kecamatan.' '.$nagari->kabupaten)->lower() }}"
                                data-kabupaten="{{ $nagari->kabupaten }}"
                                data-idm="{{ (string) $nagari->status_idm }}">
                                <td class="px-5 py-4 text-center font-bold text-on-surface-variant">{{ $index + 1 }}</td>
                                <td class="px-5 py-4">
                                    <span class="block font-black text-primary">{{ $nagari->nama_lengkap }}</span>
                                    <span class="mt-0.5 block text-xs text-on-surface-variant">{{ collect([$nagari->kecamatan, $nagari->kabupaten])->filter()->implode(', ') }}</span>
                                </td>
                                <td class="px-4 py-4 text-right font-bold tabular-nums text-on-surface">{{ number_format((int) $nagari->total_penduduk, 0, ',', '.') }}</td>
                                <td class="px-4 py-4 text-right font-bold tabular-nums text-on-surface">{{ number_format((int) $nagari->total_umkm, 0, ',', '.') }}</td>
                                <td class="px-4 py-4 text-right font-bold tabular-nums text-on-surface">{{ number_format((int) $nagari->total_produk, 0, ',', '.') }}</td>
                                <td class="px-5 py-4 text-center">
                                    @if($hasSdgs)
                                        <span class="inline-flex items-center rounded-full bg-primary/10 px-2.5 py-1 text-xs font-black tabular-nums text-primary">
                                            {{ number_format($sdgSkor, 1, ',', '.') }}%
                                        </span>
                                    @else
                                        <span class="text-xs text-on-surface-variant">—</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-center">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-extrabold
                                        @if($nagari->status_idm === 'MANDIRI') bg-emerald-100 text-emerald-800
                                        @elseif($nagari->status_idm === 'MAJU') bg-sky-100 text-sky-800
                                        @elseif($nagari->status_idm === 'BERKEMBANG') bg-amber-100 text-amber-800
                                        @elseif($nagari->status_idm === 'TERTINGGAL') bg-rose-100 text-rose-800
                                        @else bg-surface-container-high text-on-surface-variant
                                        @endif">
                                        {{ $idmLabel }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 text-center">
                                    @if((int) $nagari->total_iot > 0)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-black text-emerald-800">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>
                                            {{ $nagari->total_iot }}
                                        </span>
                                    @else
                                        <span class="text-xs text-on-surface-variant">0</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button type="button" data-table-select-nagari="{{ $nagari->slug }}"
                                                class="inline-flex items-center gap-1 rounded-xl bg-primary/10 px-3 py-1.5 text-xs font-extrabold text-primary transition hover:bg-primary hover:text-on-primary">
                                            <x-heroicon-o-map-pin class="h-3.5 w-3.5" />
                                            Lihat di Peta
                                        </button>
                                        <a href="{{ \App\Support\PublicNavigation::rute('public.nagari.teras', $nagari) }}"
                                           class="inline-flex items-center justify-center rounded-xl border border-outline-variant bg-background p-1.5 text-on-surface-variant transition hover:border-primary/40 hover:text-primary"
                                           title="Kunjungi Teras Nagari Lengkap">
                                            <x-heroicon-o-arrow-up-right class="h-4 w-4" />
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="p-8 text-center text-sm text-on-surface-variant">Belum ada data nagari mitra.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <p data-table-empty class="hidden p-8 text-center text-sm text-on-surface-variant">Tidak ada nagari yang cocok dengan filter tabel.</p>
        </div>
    </div>
</section>

{{-- ══ MODAL FLYOUT DETAIL NAGARI ════════════════════════════════════════ --}}
<div data-teras-modal class="fixed inset-0 z-[70] hidden" aria-hidden="true">
    <button type="button" data-teras-modal-backdrop class="absolute inset-0 bg-primary/55 backdrop-blur-sm" aria-label="Tutup detail nagari"></button>

    <section data-teras-modal-dialog role="dialog" aria-modal="true" aria-labelledby="teras-modal-title"
             class="absolute inset-x-0 bottom-0 flex max-h-[92svh] flex-col overflow-hidden rounded-t-[2rem] border border-outline-variant bg-background shadow-2xl sm:inset-y-5 sm:left-auto sm:right-5 sm:w-[min(54rem,calc(100vw-2.5rem))] sm:max-h-none sm:rounded-[2rem]">
        {{-- Mobile grab handle --}}
        <div class="flex shrink-0 items-center justify-center pt-2.5 pb-1 sm:hidden">
            <div class="h-1.5 w-12 rounded-full bg-outline-variant/80"></div>
        </div>

        <header class="flex shrink-0 items-start gap-3 border-b border-outline-variant bg-surface-container-lowest px-4 py-3.5 sm:gap-4 sm:px-7 sm:py-5">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-primary text-on-primary sm:h-11 sm:w-11">
                <x-heroicon-o-map-pin class="h-5 w-5" />
            </span>
            <div class="min-w-0 flex-1">
                <p class="text-[10px] font-black uppercase tracking-[0.18em] text-secondary">Data Publik Nagari</p>
                <h2 id="teras-modal-title" data-teras-modal-title class="mt-0.5 truncate text-lg font-black text-primary sm:text-2xl">Memuat nagari…</h2>
                <p data-teras-modal-location class="mt-0.5 truncate text-xs text-on-surface-variant"></p>
            </div>
            <button type="button" data-teras-modal-close class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full border border-outline-variant bg-background text-primary transition hover:bg-primary/7 sm:h-10 sm:w-10" aria-label="Tutup detail nagari">
                <x-heroicon-o-x-mark class="h-5 w-5" />
            </button>
        </header>

        <nav data-teras-modal-tabs class="flex shrink-0 gap-1 overflow-x-auto border-b border-outline-variant bg-surface-container-lowest px-4 py-2 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden sm:px-6" aria-label="Kelompok data nagari"></nav>

        <div data-teras-modal-content class="min-h-0 flex-1 overflow-y-auto px-5 py-6 sm:px-7" tabindex="-1" aria-live="polite">
            <div class="flex min-h-52 items-center justify-center text-sm font-semibold text-on-surface-variant">Memuat data nagari…</div>
        </div>

        <footer class="flex shrink-0 flex-wrap items-center justify-between gap-3 border-t border-outline-variant bg-surface-container-lowest px-5 py-3 sm:px-7">
            <p class="text-[11px] text-on-surface-variant">Data rinci dimuat hanya untuk nagari dan tab yang dipilih.</p>
            <a data-teras-modal-full href="{{ route('public.teras') }}" class="inline-flex items-center gap-2 rounded-full bg-primary px-4 py-2 text-xs font-extrabold text-on-primary shadow-sm transition hover:opacity-90">
                Buka Teras lengkap <x-heroicon-o-arrow-up-right class="h-4 w-4" />
            </a>
        </footer>
    </section>
</div>
@endsection

@push('scripts')
    @vite('resources/js/public-teras-map.js')
@endpush
