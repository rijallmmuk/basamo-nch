@extends('public.layouts.app')

@section('title', $nagari ? 'Medan Nan Balinduang' : 'Katalog Pelatihan')
@section('meta_description', 'Jelajahi pelatihan dan modul pembelajaran BASAMO NCH. Informasi katalog terbuka; materi, evaluasi, dan diskusi hanya untuk warga yang sudah masuk.')
@section('main-class', 'w-full')

@if($pelatihans->isNotEmpty())
    @push('structured-data')
        <x-public.structured-data :data="[
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'name' => $nagari ? 'Pelatihan untuk '.$nagari->nama_lengkap : 'Katalog Pelatihan Basamo NCH',
            'itemListElement' => $pelatihans->getCollection()->values()->map(fn ($item, $index) => [
                '@type' => 'ListItem',
                'position' => $pelatihans->firstItem() + $index,
                'url' => route('public.pelatihan', $item),
                'item' => [
                    '@type' => 'Course',
                    'name' => $item->temaNama(),
                    'description' => \Illuminate\Support\Str::limit(strip_tags((string) $item->deskripsi) ?: 'Pelatihan warga melalui Smart Learning Center Basamo NCH.', 60),
                    'provider' => [
                        '@type' => 'Organization',
                        'name' => 'BASAMO Nagari Creative Hub',
                        'sameAs' => rtrim((string) config('app.url'), '/'),
                    ],
                ],
            ])->all(),
        ]" />
    @endpush
@endif

@section('content')
@php
    $isFallback = $nagari && request()->routeIs('*.fallback');
    $catalogUrl = $nagari
        ? ($isFallback ? route('public.nagari.slc.fallback', $nagari) : route('public.nagari.slc', $nagari))
        : route('public.slc');
@endphp

<x-public.pillar-header
    eyebrow="Pilar 2 · Medan Nan Balinduang"
    title="Pelatihan warga yang terarah dan mudah dijelajahi."
    description="Lihat pilihan pelatihan, pengelola, dan susunan modulnya. Akun warga diperlukan saat Anda mulai membuka materi, evaluasi, dan diskusi."
/>

<section class="border-b border-outline-variant bg-surface-container-lowest py-7">
    <div class="mx-auto max-w-container-page px-margin-mobile lg:px-margin-page">
        <form method="GET" action="{{ $catalogUrl }}" data-live-filter data-live-target="#slc-results" @class([
            'grid gap-3 lg:items-end',
            'lg:grid-cols-[minmax(18rem,1fr)_18rem_auto]' => ! $nagari,
            'lg:grid-cols-[minmax(18rem,1fr)_auto]' => $nagari,
        ])>
            <label class="block min-w-0">
                <span class="mb-2 block text-sm font-bold text-on-surface">Cari pelatihan atau modul</span>
                <span class="relative block">
                    <x-heroicon-o-magnifying-glass class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-on-surface-variant" />
                    <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Contoh: pemasaran digital" autocomplete="off"
                           class="min-h-12 w-full rounded-xl border border-control-border bg-white py-3 pl-11 pr-4 text-sm text-on-surface shadow-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20">
                </span>
            </label>

            @unless($nagari)
                <label class="block">
                    <span class="mb-2 block text-sm font-bold text-on-surface">Nagari</span>
                    <select name="nagari" class="min-h-12 w-full rounded-xl border border-control-border bg-white py-3 pl-4 pr-10 text-sm text-on-surface shadow-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20">
                        <option value="">Semua nagari</option>
                        @foreach($nagariOptions as $option)
                            <option value="{{ $option->id }}" @selected($filters['nagari'] === (string) $option->id)>
                                {{ $option->nama }}{{ $option->kabupaten ? ' · '.$option->kabupaten : '' }}
                            </option>
                        @endforeach
                    </select>
                </label>
            @endunless

            <div class="flex gap-2">
                <span data-live-filter-status class="self-center text-xs font-semibold text-on-surface-variant" role="status" aria-live="polite"></span>
                <noscript>
                    <button class="min-h-12 flex-1 rounded-xl bg-primary px-6 py-3 text-sm font-bold text-on-primary shadow-sm lg:flex-none">Tampilkan</button>
                </noscript>
                <a href="{{ $catalogUrl }}" data-live-filter-reset @class([
                    'min-h-12 items-center justify-center gap-2 rounded-xl border border-control-border bg-white px-4 text-sm font-bold text-primary transition hover:border-primary hover:bg-primary/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary',
                    'inline-flex' => collect($filters)->filter()->isNotEmpty(),
                    'hidden' => collect($filters)->filter()->isEmpty(),
                ]) aria-label="Hapus semua filter">
                    <x-heroicon-o-x-mark class="h-4 w-4" /> <span class="hidden sm:inline">Hapus filter</span>
                </a>
            </div>
        </form>
    </div>
</section>

<section id="slc-results" class="bg-background py-section-gap">
    <div class="mx-auto max-w-container-page px-margin-mobile lg:px-margin-page">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <x-public.section-heading
                eyebrow="Daftar Pelatihan"
                :title="$pelatihans->isEmpty() ? 'Belum ada pelatihan yang dapat dijelajahi.' : number_format($pelatihans->total(), 0, ',', '.').' pelatihan tersedia.'"
                description="Modul tidak dipisahkan dari pelatihannya. Buka sebuah kartu untuk melihat susunan pembelajaran secara utuh."
            />
            @if($selectedNagari)
                <p class="inline-flex w-fit shrink-0 items-center gap-2 text-sm font-semibold text-on-surface-variant">
                    <x-heroicon-o-map-pin class="h-4 w-4 text-primary" /> {{ $selectedNagari->nama_lengkap }}
                </p>
            @endif
        </div>

        @if($pelatihans->isEmpty())
            <x-public.empty-state
                class="mt-8"
                icon="heroicon-o-academic-cap"
                title="Belum ada pelatihan yang dibuka"
                description="Pelatihan tampil setelah pengajar menyiapkan minimal satu materi dan membuka aksesnya untuk warga." />
        @else
            {{-- Mengikuti kepadatan kartu katalog portal: maksimal empat kolom
                 agar cover, judul, dan pengelola tetap mudah dipindai. --}}
            <div class="mt-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach($pelatihans as $pelatihan)
                    <x-slc.pelatihan-card
                        :program="$pelatihan"
                        :href="$nagari
                            ? \App\Support\PublicNavigation::rute('public.nagari.pelatihan', $nagari, ['pelatihan' => $pelatihan->getKey()])
                            : route('public.pelatihan', $pelatihan)"
                        cta="Lihat Pelatihan" />
                @endforeach
            </div>
            <div class="mt-10">{{ $pelatihans->onEachSide(1)->links('public.pagination', ['label' => 'pelatihan']) }}</div>
        @endif
    </div>
</section>
@endsection
