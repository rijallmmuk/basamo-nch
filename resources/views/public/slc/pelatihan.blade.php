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
            <div class="flex flex-wrap items-center gap-2">
                <span class="text-xs font-bold uppercase tracking-[0.18em] text-secondary-container">Pilar 2 · Medan Nan Balinduang</span>
                @if($pelatihan->dapatDimasuki())
                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-600/95 px-2.5 py-0.5 text-[11px] font-bold">
                        <x-heroicon-s-check-circle class="h-3.5 w-3.5" /> Terbuka
                    </span>
                @else
                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-500/95 px-2.5 py-0.5 text-[11px] font-bold">
                        <x-heroicon-s-lock-closed class="h-3.5 w-3.5" /> Terkunci
                    </span>
                @endif
            </div>

            <h1 class="mt-3 text-hero text-balance text-on-primary">{{ $pelatihan->temaNama() }}</h1>

            @if(filled($pelatihan->deskripsi))
                <p class="mt-5 text-lead text-pretty text-on-primary/72">{{ strip_tags($pelatihan->deskripsi) }}</p>
            @endif

            <div class="mt-8 flex flex-wrap items-center gap-3">
                <a href="{{ route('login') }}"
                   class="inline-flex items-center gap-2 rounded-full bg-secondary-container px-7 py-3.5 text-sm font-extrabold text-on-secondary-container shadow-lg transition-all hover:-translate-y-0.5 hover:shadow-xl">
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
        <div class="grid gap-8 lg:grid-cols-3">

            {{-- ── Daftar modul: JUDUL SAJA ──────────────────────────────────
                 Isi materi, berkas, pre-test, evaluasi, dan diskusi tidak ikut
                 dimuat sama sekali, bukan sekadar disembunyikan di tampilan.
                 Yang tidak dimuat tidak bisa bocor lewat view-source. --}}
            <div class="lg:col-span-2">
                <h2 class="text-xl font-extrabold tracking-tight text-primary">Isi pelatihan</h2>
                <p class="mt-1 text-sm text-on-surface-variant">
                    Judul modulnya terbuka untuk umum. Materi, berkas, dan evaluasinya dibuka setelah masuk sebagai warga nagari.
                </p>

                @if($pelatihan->modules->isEmpty())
                    <x-public.empty-state
                        class="mt-6"
                        icon="heroicon-o-book-open"
                        title="Modul belum tersedia"
                        description="Modul tampil di sini setelah pengajar menyiapkan materinya." />
                @else
                    <ol class="mt-6 space-y-3">
                        @foreach($pelatihan->modules as $urutan => $modul)
                            <li class="flex items-start gap-4 rounded-2xl border border-outline-variant bg-surface-container-lowest p-5 shadow-sm">
                                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-primary/8 text-sm font-black text-primary">
                                    {{ $urutan + 1 }}
                                </span>
                                <div class="min-w-0 flex-1">
                                    <p class="font-extrabold leading-snug text-on-surface">{{ $modul->judul }}</p>
                                    @if(filled($modul->deskripsi))
                                        <p class="mt-1 line-clamp-2 text-sm leading-relaxed text-on-surface-variant">
                                            {{ strip_tags($modul->deskripsi) }}
                                        </p>
                                    @endif
                                </div>
                                <span class="mt-1 shrink-0 text-on-surface-variant" title="Terbuka setelah masuk">
                                    <x-heroicon-o-lock-closed class="h-5 w-5" />
                                </span>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>

            {{-- ── Sisi kanan ── --}}
            <aside class="space-y-5">
                <div class="rounded-2xl border border-outline-variant bg-surface-container-lowest p-6 shadow-sm">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Pengajar atau Pengelola</h2>
                    <div class="mt-4">
                        <x-slc.pengelola-list :participants="$participants" compact />
                    </div>
                </div>

                <div class="rounded-2xl border border-outline-variant bg-surface-container-lowest p-6 shadow-sm">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-on-surface-variant">Cara mengikuti</h2>
                    <ol class="mt-4 space-y-3 text-sm text-on-surface-variant">
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
                            Pelajari materi tiap modul secara berurutan, lalu kerjakan evaluasinya.
                        </li>
                    </ol>

                </div>
            </aside>
        </div>
    </div>
</section>
@endsection
