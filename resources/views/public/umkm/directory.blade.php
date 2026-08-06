@extends('public.layouts.app')

@section('title', $nagari ? 'Lapau Nagari' : 'Lapau Nagari · Direktori UMKM')
@section('meta_description', $nagari
    ? 'Direktori UMKM '.$nagari->nama_lengkap.': jelajahi usaha warga, kunjungi etalasenya, dan hubungi penjual langsung via WhatsApp.'
    : 'Lapau Nagari BASAMO NCH: direktori lapau usaha dan produk lokal dari nagari mitra.')
@section('main-class', 'w-full')

@section('content')
@php
    $isFallback = $nagari && request()->routeIs('*.fallback');
    $directoryUrl = $nagari
        ? ($isFallback ? route('public.nagari.umkm.fallback', $nagari) : route('public.nagari.umkm', $nagari))
        : route('public.umkm');
@endphp
<x-public.pillar-header
    eyebrow="Pilar 4 · Lapau Nagari"
    title="Temukan lapau usaha dan produk lokal nagari."
    description="Jelajahi profil usaha warga, lihat produk yang tersedia, lalu hubungi penjual secara langsung melalui etalasenya."
/>

<section class="border-b border-outline-variant bg-surface-container-lowest py-6 lg:py-7">
    <div class="mx-auto max-w-container-page px-margin-mobile lg:px-margin-page">
        <form method="GET" action="{{ $directoryUrl }}" @class([
            'grid gap-3 lg:items-end',
            'lg:grid-cols-[minmax(18rem,1fr)_18rem_auto]' => ! $nagari,
            'lg:grid-cols-[minmax(18rem,1fr)_auto]' => $nagari,
        ])>
            <label class="block min-w-0">
                <span class="mb-2 block text-sm font-bold text-on-surface">Cari lapau usaha</span>
                <span class="relative block">
                    <x-heroicon-o-magnifying-glass class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-on-surface-variant" />
                <input
                    type="search"
                    name="q"
                    value="{{ $filters['q'] }}"
                        placeholder="Contoh: rendang, tenun, kerajinan"
                        autocomplete="off"
                        class="min-h-12 w-full rounded-xl border border-control-border bg-white py-3 pl-11 pr-4 text-sm text-on-surface shadow-sm outline-none transition focus:border-primary focus:ring-2 focus:ring-primary/20"
                >
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
                <button type="submit" class="min-h-12 flex-1 rounded-xl bg-primary px-6 py-3 text-sm font-bold text-on-primary shadow-sm transition hover:bg-primary-highlight focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 lg:flex-none">
                    Tampilkan
                </button>
                @if(collect($filters)->filter()->isNotEmpty())
                    <a href="{{ $directoryUrl }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl border border-control-border bg-white px-4 text-sm font-bold text-primary transition hover:border-primary hover:bg-primary/5 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary" aria-label="Hapus semua filter">
                        <x-heroicon-o-x-mark class="h-4 w-4" /> <span class="hidden sm:inline">Hapus filter</span>
                    </a>
                @endif
            </div>
        </form>
    </div>
</section>

<section class="bg-background py-10 lg:py-14">
    <div class="mx-auto max-w-container-page px-margin-mobile lg:px-margin-page">
        <div class="mb-7 flex flex-col justify-between gap-2 sm:flex-row sm:items-end">
            <div>
                <p class="text-xs font-bold uppercase tracking-widest text-secondary">Direktori Lapau Usaha</p>
                <h2 class="mt-1 text-section text-primary">
                    {{ number_format($usaha->total(), 0, ',', '.') }} lapau usaha
                </h2>
            </div>
            <p class="text-sm text-on-surface-variant">
                {{ $nagari ? $nagari->nama_lengkap : 'Seluruh nagari mitra' }}
            </p>
        </div>

        @if($usaha->isEmpty())
            {{-- Keadaan kosong apa adanya. Sebelumnya di sini tampil DELAPAN usaha
                 karangan lengkap dengan nama pemilik, kategori, jumlah produk, dan
                 deskripsinya; pengunjung tidak punya cara tahu itu bukan usaha
                 sungguhan di nagari ini. --}}
            <x-public.empty-state
                icon="heroicon-o-building-storefront"
                :title="collect($filters)->filter()->isNotEmpty() ? 'Lapau usaha tidak ditemukan' : 'Belum ada lapau usaha yang tayang'"
                :description="collect($filters)->filter()->isNotEmpty() ? 'Coba gunakan kata kunci lain atau hapus filter yang aktif.' : 'Lapau usaha akan tampil setelah profilnya dilengkapi dan diaktifkan oleh pengelola nagari.'" />
        @else
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5">
                @foreach($usaha as $profile)
                    <x-umkm.business-card
                        :profile="$profile"
                        :nagari="$nagari ?? $profile->nagari"
                        :show-nagari="$nagari === null"
                        :global="$nagari === null"
                    />
                @endforeach
            </div>

            <div class="mt-10">{{ $usaha->onEachSide(1)->links('public.pagination', ['label' => 'lapau usaha']) }}</div>
        @endif
    </div>
</section>
@endsection
