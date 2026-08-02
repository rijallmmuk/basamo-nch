@extends('public.layouts.app')

@section('title', 'Portal Data, Pelatihan & UMKM')
@section('meta_description', 'Wajah digital '.$nagari->nama_lengkap.': data nagari, pelatihan warga, dan etalase UMKM dalam empat pilar Nagari Creative Hub.')
@section('main-class', 'w-full')

@section('content')
@php
    /* Subdomain memakai rute ber-domain, pengembangan lokal memakai /n/{nagari}.
       Seluruh tautan halaman ini WAJIB ikut bentuk yang sedang dipakai, kalau tidak
       pengunjung terlempar ke host yang belum tentu ada. */
    $isFallback = request()->routeIs('*.fallback');
    $tautan = fn (string $nama) => route($isFallback ? $nama.'.fallback' : $nama, $nagari);

    $terasUrl = $tautan('public.nagari.teras');
@endphp

{{-- ══ 1. SAMBUTAN NAGARI ══ --}}
<x-public.ecosystem-hero
    :nagari="$nagari"
    :photos="$fotoSampul"
/>

{{-- ══ 2. RINGKASAN NAGARI ══════════════════════════════════════════════
     Hanya angka utama. Rincian SDGs, IDM, demografi, dan cuaca sengaja berada
     di halaman Teras Nagari agar beranda tetap menjadi pintu masuk. --}}
<section id="ringkasan" class="relative overflow-hidden bg-gradient-to-b from-background to-surface-container-lowest py-section-gap">
    <div class="absolute right-0 top-0 h-[500px] w-[500px] -translate-y-1/2 translate-x-1/2 rounded-full bg-primary/5 blur-[120px]" aria-hidden="true"></div>
    <div class="relative z-10 mx-auto max-w-container-page px-margin-mobile lg:px-margin-page">
        <div class="mb-10 flex flex-col justify-between gap-6 md:flex-row md:items-end">
            <x-public.section-heading
                eyebrow="Ringkasan Statistik"
                :title="'Sekilas angka '.$nagari->nama_lengkap.'.'"
                description="Gambaran umum aktivitas dan capaian nagari saat ini."
            />
            <a href="{{ $terasUrl }}"
               class="group inline-flex shrink-0 items-center gap-2 rounded-full bg-primary px-6 py-3.5 text-sm font-extrabold text-on-primary shadow-lg shadow-primary/20 transition-all duration-300 hover:-translate-y-1 hover:shadow-xl hover:shadow-primary/30">
                Lihat Analitik Lengkap di Teras Nagari
                <x-heroicon-o-arrow-right class="h-4 w-4 transition-transform group-hover:translate-x-1" />
            </a>
        </div>

        <x-public.stat-grid :cols="count($overview['metrics'])">
            @foreach($overview['metrics'] as $metric)
                <x-public.stat-card
                    class="bg-white/80 backdrop-blur-md border border-white/50 shadow-sm transition-all duration-300 hover:shadow-md hover:-translate-y-1"
                    :label="$metric['label']"
                    :value="$metric['value']"
                    :icon="$metric['icon']"
                    :description="$metric['description']" />
            @endforeach
        </x-public.stat-grid>
    </div>
</section>

@if($ews)
    @php
        $pembacaanEws = $ews['pembacaan'];
        $angkaEws = fn ($nilai, int $desimal = 0) => $nilai === null ? '—' : number_format((float) $nilai, $desimal, ',', '.');
    @endphp
    {{-- Ringkasan saja; empat sensor dan tren lengkap berada di Teras Nagari. --}}
    <section id="ews" class="border-t border-outline-variant bg-surface-container-lowest py-section-gap">
        <div class="mx-auto max-w-container-page px-margin-mobile lg:px-margin-page">
            <div class="flex flex-col justify-between gap-6 md:flex-row md:items-end">
                <x-public.section-heading
                    eyebrow="Pemantauan EWS"
                    title="Ringkasan peringatan dini nagari."
                    description="Pembacaan terakhir dari perangkat sensor yang terpasang di nagari. Status sambungan dan waktu data selalu ditampilkan agar kondisinya tidak disalahartikan."
                />
                <a href="{{ $terasUrl }}#ews"
                   class="group inline-flex shrink-0 items-center gap-2 text-sm font-extrabold text-primary hover:underline">
                    Lihat pemantauan lengkap di Teras Nagari
                    <x-heroicon-o-arrow-right class="h-4 w-4 transition-transform group-hover:translate-x-1" />
                </a>
            </div>

            <div class="mt-10 grid gap-4 lg:grid-cols-12">
                <div class="rounded-3xl border border-outline-variant bg-background p-6 shadow-sm lg:col-span-6">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-widest text-on-surface-variant">Status Sungai</p>
                            <p class="mt-2 text-3xl font-black {{ $ews['status']->kelasWarna() }}">{{ $ews['status']->getLabel() }}</p>
                            <p class="mt-2 text-sm text-on-surface-variant">Titik pantau {{ $ews['device']->namaTampil() }}</p>
                        </div>
                        <span class="inline-flex items-center gap-2 rounded-full border border-outline-variant px-3 py-2 text-xs font-semibold">
                            <span class="h-2 w-2 rounded-full {{ $ews['terhubung'] && ! $ews['basi'] ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                            {{ $ews['terhubung'] && ! $ews['basi'] ? 'Data terkini' : 'Periksa waktu data' }}
                        </span>
                    </div>
                    <p class="mt-5 border-t border-outline-variant pt-4 text-xs text-on-surface-variant">
                        Pembacaan terakhir {{ $pembacaanEws?->direkam_pada?->locale('id')?->diffForHumans() ?? 'belum tersedia' }}.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-4 lg:col-span-6">
                    <div class="rounded-3xl border border-outline-variant bg-background p-6 shadow-sm">
                        <x-heroicon-o-arrow-trending-up class="h-6 w-6 text-sky-600" />
                        <p class="mt-5 text-xs font-bold uppercase tracking-wider text-on-surface-variant">Tinggi Air</p>
                        <p class="mt-2 text-3xl font-black tabular-nums text-primary">{{ $angkaEws($pembacaanEws?->tinggi_air) }}</p>
                        <p class="text-xs font-semibold text-on-surface-muted">cm</p>
                    </div>
                    <div class="rounded-3xl border border-outline-variant bg-background p-6 shadow-sm">
                        <x-heroicon-o-cloud class="h-6 w-6 text-emerald-600" />
                        <p class="mt-5 text-xs font-bold uppercase tracking-wider text-on-surface-variant">Curah Hujan</p>
                        <p class="mt-2 text-3xl font-black tabular-nums text-primary">{{ $angkaEws($pembacaanEws?->curah_hujan) }}</p>
                        <p class="text-xs font-semibold text-on-surface-muted">mm/jam</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endif

{{-- ══ 3. AJAKAN MASUK PORTAL WARGA ══ --}}
<section class="relative overflow-hidden border-t border-outline-variant bg-gradient-to-br from-primary to-gray-900 py-20 lg:py-24 shadow-inner">
    <div class="songket-pattern absolute inset-0 opacity-10 mix-blend-overlay" aria-hidden="true"></div>
    <div class="absolute left-1/2 top-1/2 h-[800px] w-[800px] -translate-x-1/2 -translate-y-1/2 rounded-full bg-primary-400/10 blur-[100px]" aria-hidden="true"></div>
    <div class="relative z-10 mx-auto flex max-w-container-page flex-col items-center justify-center text-center gap-8 px-margin-mobile text-on-primary lg:px-margin-page">
        <div class="max-w-2xl">
            <h2 class="text-3xl font-black tracking-tight sm:text-4xl bg-gradient-to-r from-white to-gray-300 bg-clip-text text-transparent">Warga {{ $nagari->nama_lengkap }}?</h2>
            <p class="mt-4 text-lg text-on-primary/80 leading-relaxed">
                Masuk dengan NIK Anda untuk mengikuti pelatihan, mengerjakan evaluasi, dan berdiskusi dengan pengajar di ruang digital Anda.
            </p>
        </div>
        <a href="{{ route('login') }}"
           class="group inline-flex shrink-0 items-center gap-3 rounded-full bg-secondary-container px-8 py-4 text-sm font-extrabold text-on-secondary-container shadow-xl shadow-secondary-container/20 transition-all duration-300 hover:-translate-y-1 hover:scale-105 hover:shadow-2xl hover:shadow-secondary-container/30">
            Masuk Portal Warga
            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-on-secondary-container/10 group-hover:bg-on-secondary-container/20 transition-colors">
                <x-heroicon-o-arrow-right class="h-4 w-4" />
            </span>
        </a>
    </div>
</section>
@endsection
