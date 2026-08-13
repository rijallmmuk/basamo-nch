@extends('public.layouts.app')

{{-- Dipakai DUA konteks: pilar milik satu nagari ($nagari terisi) dan pilar tingkat
     induk ($nagari null). Sengaja satu berkas, sebab isinya sama persis kecuali
     penyebutan nama nagari, dan dua salinan pasti melenceng saat pilar ini akhirnya
     benar-benar diisi. --}}
@php
    $nagari = $nagari ?? null;
    $milik = $nagari ? $nagari->nama_lengkap : 'nagari mitra BASAMO NCH';
@endphp

@section('title', 'Medan Nan Bapaneh')
@section('meta_description', 'Medan Nan Bapaneh '.$milik.': ruang budaya, inovasi, arsip, dan publikasi digital yang sedang disiapkan.')
@section('robots', 'noindex, follow')
@section('main-class', 'w-full')

@section('content')
<x-public.pillar-header
    eyebrow="Pilar 3 · Medan Nan Bapaneh"
    title="Ruang budaya dan inovasi nagari sedang disiapkan."
    :description="'Pilar ini direncanakan sebagai rumah arsip budaya, media digital, karya kreatif, dan publikasi '.$milik.'.'">
    <x-slot:aside>
        <div class="inline-flex items-center gap-2 rounded-full border border-secondary-container/30 bg-secondary-container/10 px-4 py-2 text-xs font-black uppercase tracking-[0.16em] text-secondary-container">
            <span class="h-2 w-2 rounded-full bg-secondary-container" aria-hidden="true"></span>
            Dalam perencanaan
        </div>
    </x-slot:aside>
</x-public.pillar-header>

<section class="bg-background py-section-gap">
    <div class="mx-auto max-w-container-page px-margin-mobile lg:px-margin-page">
        <div class="grid gap-8 lg:grid-cols-12 lg:items-start">
            <div class="lg:col-span-5">
                <x-public.section-heading
                    eyebrow="Pengembangan Lanjutan"
                    title="Fondasinya disiapkan sebelum konten diterbitkan."
                    description="Belum ada arsip atau statistik yang ditampilkan. Informasi baru akan dibuka setelah struktur pengelolaan dan sumber datanya tersedia."
                />
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:col-span-7">
                @foreach([
                    ['heroicon-o-archive-box', 'Arsip budaya', 'Dokumen, cerita, dan pengetahuan lokal.'],
                    ['heroicon-o-photo', 'Media digital', 'Foto, video, dan rekam jejak kegiatan nagari.'],
                    ['heroicon-o-light-bulb', 'Ruang inovasi', 'Gagasan serta karya kreatif dari warga.'],
                    ['heroicon-o-megaphone', 'Publikasi nagari', 'Kabar dan karya yang layak dibagikan lebih luas.'],
                ] as [$icon, $title, $description])
                    <article class="flex min-h-36 items-start gap-4 rounded-2xl border border-outline-variant bg-surface-container-lowest p-5 shadow-sm">
                        <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-primary/8 text-primary">
                            <x-dynamic-component :component="$icon" class="h-5 w-5" />
                        </span>
                        <div>
                            <h2 class="font-extrabold text-primary">{{ $title }}</h2>
                            <p class="mt-2 text-sm leading-relaxed text-on-surface-variant">{{ $description }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </div>
    </div>
</section>
@endsection
