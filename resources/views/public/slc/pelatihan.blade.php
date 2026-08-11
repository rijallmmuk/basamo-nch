@extends('public.layouts.app')

@section('title', $pelatihan->temaNama())
@section('meta_description', \Illuminate\Support\Str::limit(strip_tags((string) $pelatihan->deskripsi) ?: 'Pelatihan '.$pelatihan->temaNama().' pada Medan Nan Balinduang, ruang belajar warga nagari.', 155))
@section('canonical', route('public.pelatihan', $pelatihan))
@section('meta_image', $pelatihan->coverUrl() ?: asset('images/brand/basamo-nch-mark.png'))
@section('meta_image_alt', 'Sampul pelatihan '.$pelatihan->temaNama())
@section('main-class', 'w-full')

@push('structured-data')
    <x-public.structured-data :data="array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Course',
        'name' => $pelatihan->temaNama(),
        'description' => strip_tags((string) $pelatihan->deskripsi) ?: 'Pelatihan warga melalui Smart Learning Center Basamo NCH.',
        'url' => route('public.pelatihan', $pelatihan),
        'image' => $pelatihan->coverUrl(),
        'provider' => [
            '@type' => 'Organization',
            'name' => 'BASAMO Nagari Creative Hub',
            'url' => rtrim((string) config('app.url'), '/'),
        ],
        'hasCourseInstance' => [
            '@type' => 'CourseInstance',
            'courseMode' => 'online',
            'instructor' => $pelatihan->participants()->map(fn ($participant) => [
                '@type' => 'Person',
                'name' => $participant->name,
            ])->values()->all(),
        ],
    ])" />
@endpush

@section('content')
@php
    $participants = $pelatihan->participants();
    $isFallback = $nagari && request()->routeIs('*.fallback');
    $catalogUrl = $nagari
        ? ($isFallback
            ? route('public.nagari.slc.fallback', $nagari)
            : route('public.nagari.slc', $nagari))
        : route('public.slc');
@endphp

{{-- ══ KEPALA HALAMAN ══════════════════════════════════════════════════
     Sampul dipakai sebagai latar, sama seperti kartu di katalog, supaya
     pengunjung mengenali bahwa ia membuka kartu yang barusan ditekannya. --}}
<section class="relative overflow-hidden bg-primary text-on-primary">
    @if($pelatihan->punyaCover())
        <img src="{{ $pelatihan->coverUrl() }}" alt="" aria-hidden="true"
             class="absolute inset-0 h-full w-full object-cover opacity-25">
    @else
        <div class="songket-pattern absolute inset-0 opacity-10" aria-hidden="true"></div>
    @endif
    <div class="absolute inset-0 bg-gradient-to-t from-primary via-primary/85 to-primary/70" aria-hidden="true"></div>

    <div class="relative z-10 mx-auto max-w-container-page px-margin-mobile py-14 lg:px-margin-page lg:py-20">
        <div class="max-w-3xl">
            <a href="{{ $catalogUrl }}" class="mb-8 inline-flex items-center gap-2 text-sm font-bold text-on-primary/80 transition hover:text-on-primary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-on-primary">
                <x-heroicon-o-arrow-left class="h-4 w-4" /> Kembali ke Medan Nan Balinduang
            </a>

            <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                <span class="text-xs font-bold uppercase tracking-[0.18em] text-secondary-container">Medan Nan Balinduang</span>
                @if($pelatihan->dapatDimasuki())
                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-on-primary/80">
                        <x-heroicon-o-check-circle class="h-4 w-4" /> Dapat dipelajari
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 text-xs font-semibold text-on-primary/80">
                        <x-heroicon-o-lock-closed class="h-4 w-4" /> Belum dibuka untuk belajar
                    </span>
                @endif
            </div>

            <h1 class="mt-3 text-hero text-balance text-on-primary">{{ $pelatihan->temaNama() }}</h1>

            @if(filled($pelatihan->deskripsi))
                <p class="mt-5 text-lead text-pretty text-on-primary/72">{{ strip_tags($pelatihan->deskripsi) }}</p>
            @endif

            <div class="mt-8 flex flex-wrap items-center gap-3">
                <a href="{{ \App\Support\PublicNavigation::masukUrl(pelatihanId: $pelatihan->getKey()) }}"
                   class="inline-flex min-h-12 items-center gap-2 rounded-xl bg-secondary-container px-7 py-3.5 text-sm font-extrabold text-on-secondary-container shadow-sm transition hover:bg-secondary-fixed focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-on-primary focus-visible:ring-offset-2 focus-visible:ring-offset-primary">
                    Masuk untuk Belajar <x-heroicon-o-arrow-right class="h-4 w-4" />
                </a>
                <span class="text-sm text-on-primary/70">
                    {{ $pelatihan->modules_count }} modul
                </span>
            </div>
        </div>
    </div>
</section>

{{-- ══ ISI ══ --}}
<section class="border-t border-outline-variant bg-background py-section-gap">
    <div class="mx-auto max-w-container-page px-margin-mobile lg:px-margin-page">
        {{-- Daftar modul mengikuti kartu modul portal. Isi materi, berkas,
             pre-test, evaluasi, dan diskusi tetap tidak pernah dimuat. --}}
        <div class="flex flex-col justify-between gap-3 border-b border-outline-variant pb-4 sm:flex-row sm:items-end">
            <div>
                <h2 class="flex items-center gap-2 text-xl font-extrabold tracking-tight text-on-surface">
                    <x-heroicon-s-book-open class="h-5 w-5 text-primary" />
                    Daftar Modul Pembelajaran
                </h2>
                <p class="mt-1 text-sm text-on-surface-variant">
                    Lihat cover, judul, dan ringkasan modul. Materi lengkap tersedia setelah masuk sebagai warga nagari.
                </p>
            </div>
            <p class="shrink-0 text-sm font-bold text-on-surface-variant">
                {{ number_format($pelatihan->modules_count, 0, ',', '.') }} modul
            </p>
        </div>

        @if($pelatihan->modules->isEmpty())
            <x-public.empty-state
                class="mt-6"
                icon="heroicon-o-book-open"
                title="Modul belum tersedia"
                description="Modul tampil di sini setelah pengajar menyiapkan materinya." />
        @else
            <div class="mt-6 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach($pelatihan->modules as $urutan => $modul)
                    <x-slc.module-preview-card :module="$modul" :order="$urutan + 1" />
                @endforeach
            </div>
        @endif

        <aside class="mt-10 grid gap-5 lg:grid-cols-2">
            <div class="rounded-2xl border border-outline-variant bg-surface-container-lowest p-6 shadow-sm">
                <h2 class="text-lg font-extrabold text-on-surface">Pengajar atau pengelola</h2>
                <div class="mt-4">
                    <x-slc.pengelola-list :participants="$participants" compact />
                </div>
            </div>

            <div class="rounded-2xl border border-outline-variant bg-surface-container-lowest p-6 shadow-sm">
                <h2 class="text-lg font-extrabold text-on-surface">Cara mengikuti</h2>
                <ol class="mt-4 grid gap-3 text-sm text-on-surface-variant sm:grid-cols-3 lg:grid-cols-1 xl:grid-cols-3">
                    <li class="flex gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary/8 text-xs font-black text-primary">1</span>
                        Masuk memakai NIK Anda sebagai warga nagari.
                    </li>
                    <li class="flex gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary/8 text-xs font-black text-primary">2</span>
                        Kerjakan pre-test bila pengajar menyediakannya.
                    </li>
                    <li class="flex gap-3">
                        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-primary/8 text-xs font-black text-primary">3</span>
                        Pelajari materi berurutan, lalu kerjakan evaluasinya.
                    </li>
                </ol>
            </div>
        </aside>
    </div>
</section>
@endsection
