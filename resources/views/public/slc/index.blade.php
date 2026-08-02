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
    title="Pelatihan warga dalam satu ruang belajar."
    description="Pilih sebuah pelatihan untuk melihat pengelola dan susunan modulnya. Isi materi, evaluasi, serta diskusi dibuka melalui akun warga sesuai sasaran nagari.">
    <x-slot:aside>
        <div class="rounded-2xl border border-on-primary/15 bg-on-primary/8 p-5 backdrop-blur-sm">
            <div class="flex items-start gap-3">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-secondary-container text-on-secondary-container">
                    <x-heroicon-o-shield-check class="h-5 w-5" />
                </span>
                <div>
                    <p class="font-extrabold text-on-primary">Katalog dapat dijelajahi publik</p>
                    <p class="mt-1 text-sm leading-relaxed text-on-primary/65">Akun warga hanya diperlukan ketika mulai mempelajari materi.</p>
                </div>
            </div>
        </div>
    </x-slot:aside>
</x-public.pillar-header>

<section class="border-b border-outline-variant bg-surface-container-lowest py-7">
    <div class="mx-auto max-w-container-page px-margin-mobile lg:px-margin-page">
        <form method="GET" action="{{ $catalogUrl }}" class="grid gap-3 lg:grid-cols-12">
            <label class="relative lg:col-span-5">
                <span class="sr-only">Cari pelatihan atau modul di dalamnya</span>
                <x-heroicon-o-magnifying-glass class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-outline" />
                <input type="search" name="q" value="{{ $filters['q'] }}" placeholder="Cari pelatihan atau modul…"
                       class="input-nch rounded-full" style="padding-left: 3rem">
            </label>

            @unless($nagari)
                <label class="lg:col-span-3">
                    <span class="sr-only">Filter nagari</span>
                    <select name="nagari" class="select-nch w-full rounded-full">
                        <option value="">Semua nagari</option>
                        @foreach($nagariOptions as $option)
                            <option value="{{ $option->id }}" @selected($filters['nagari'] === (string) $option->id)>
                                {{ $option->nama }}{{ $option->kabupaten ? ' · '.$option->kabupaten : '' }}
                            </option>
                        @endforeach
                    </select>
                </label>
            @endunless

            <label class="{{ $nagari ? 'lg:col-span-5' : 'lg:col-span-3' }}">
                <span class="sr-only">Filter pelatihan</span>
                <select name="pelatihan" class="select-nch w-full rounded-full">
                    <option value="">Semua pelatihan</option>
                    @foreach($pelatihanOptions as $option)
                        <option value="{{ $option->id }}" @selected($filters['pelatihan'] === (string) $option->id)>{{ $option->namaTampil() }}</option>
                    @endforeach
                </select>
            </label>

            <div class="{{ $nagari ? 'lg:col-span-2' : 'lg:col-span-1' }} flex gap-2">
                <button class="flex min-h-11 flex-1 items-center justify-center rounded-full bg-primary px-5 text-sm font-bold text-on-primary">Terapkan</button>
                @if(collect($filters)->filter()->isNotEmpty())
                    <a href="{{ $catalogUrl }}" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-control-border text-on-surface-variant" aria-label="Reset filter">
                        <x-heroicon-o-arrow-path class="h-5 w-5" />
                    </a>
                @endif
            </div>
        </form>
    </div>
</section>

<section class="bg-background py-section-gap">
    <div class="mx-auto max-w-container-page px-margin-mobile lg:px-margin-page">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-end">
            <x-public.section-heading
                eyebrow="Daftar Pelatihan"
                :title="$pelatihans->isEmpty() ? 'Belum ada pelatihan yang dapat dijelajahi.' : number_format($pelatihans->total(), 0, ',', '.').' pelatihan tersedia.'"
                description="Modul tidak dipisahkan dari pelatihannya. Buka sebuah kartu untuk melihat susunan pembelajaran secara utuh."
            />
            @if($selectedNagari)
                <span class="inline-flex w-fit shrink-0 items-center gap-2 rounded-full bg-primary/8 px-4 py-2 text-sm font-bold text-primary">
                    <x-heroicon-o-map-pin class="h-4 w-4" /> {{ $selectedNagari->nama_lengkap }}
                </span>
            @endif
        </div>

        @if($pelatihans->isEmpty())
            <x-public.empty-state
                class="mt-8"
                icon="heroicon-o-academic-cap"
                title="Belum ada pelatihan yang dibuka"
                description="Pelatihan tampil setelah pengajar menyiapkan minimal satu materi dan membuka aksesnya untuk warga." />
        @else
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
