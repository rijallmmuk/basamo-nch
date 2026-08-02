@extends('public.layouts.app')

@section('title', $nagari ? 'Lapau Nagari' : 'Lapau Nagari · Direktori UMKM')
@section('meta_description', $nagari
    ? 'Direktori UMKM '.$nagari->nama_lengkap.': jelajahi usaha warga, kunjungi etalasenya, dan hubungi penjual langsung via WhatsApp.'
    : 'Lapau Nagari BASAMO NCH: direktori UMKM lintas nagari, rumah digital pelaku usaha dan produk lokal.')
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
    title="Produk lokal punya cerita, pelaku usaha punya rumah."
    description="Kenali pelaku usaha, buka etalasenya, lalu jelajahi produk milik usaha tersebut tanpa mencampurkannya dengan lapau lain. Hubungi penjual secara langsung melalui etalasenya."
/>

<section class="bg-surface-container-lowest py-10 lg:py-12">
    <div class="mx-auto max-w-container-page px-margin-mobile lg:px-margin-page">
        <span class="mb-2 inline-flex items-center gap-2 text-xs font-bold uppercase tracking-widest text-secondary">
            <span class="h-px w-6 bg-secondary/50" aria-hidden="true"></span>
            Direktori UMKM
        </span>
        <h2 class="text-section text-primary">{{ $usaha->isEmpty() ? 'Temukan usaha pilihan warga' : number_format($usaha->total(), 0, ',', '.').' rumah usaha' }}</h2>
        <p class="mb-6 mt-2 text-pretty text-sm text-on-surface-variant">
            {{ $nagari ? 'Pelaku usaha di '.$nagari->nama_lengkap.'.' : 'Pelaku usaha dari seluruh nagari mitra.' }}
            Klik sebuah UMKM untuk melihat profil, QR usaha, dan produknya.
        </p>

        <form method="GET" action="{{ $directoryUrl }}" class="mb-8 grid max-w-4xl gap-3 sm:grid-cols-12">
            <div class="relative {{ $nagari ? 'sm:col-span-9' : 'sm:col-span-6' }}">
                <x-heroicon-o-magnifying-glass class="pointer-events-none absolute left-3 top-1/2 h-5 w-5 -translate-y-1/2 text-outline" />
                <input
                    type="search"
                    name="q"
                    value="{{ $filters['q'] }}"
                    placeholder="Cari nama usaha…"
                    class="input-nch rounded-full" style="padding-left: 2.75rem"
                >
            </div>
            @unless($nagari)
                <select name="nagari" class="select-nch rounded-full sm:col-span-4">
                    <option value="">Semua nagari</option>
                    @foreach($nagariOptions as $option)
                        <option value="{{ $option->id }}" @selected($filters['nagari'] === (string) $option->id)>
                            {{ $option->nama }}{{ $option->kabupaten ? ' · '.$option->kabupaten : '' }}
                        </option>
                    @endforeach
                </select>
            @endunless
            <button type="submit" class="rounded-full bg-primary px-5 py-2.5 text-sm font-bold text-on-primary transition-colors hover:bg-primary/90 sm:col-span-3 lg:col-span-2">Terapkan</button>
        </form>

        @if($usaha->isEmpty())
            {{-- Keadaan kosong apa adanya. Sebelumnya di sini tampil DELAPAN usaha
                 karangan lengkap dengan nama pemilik, kategori, jumlah produk, dan
                 deskripsinya; pengunjung tidak punya cara tahu itu bukan usaha
                 sungguhan di nagari ini. --}}
            <x-public.empty-state
                icon="heroicon-o-building-storefront"
                title="Belum ada usaha yang tayang"
                description="Usaha tampil di sini setelah operator nagari memberi akses UMKM kepada warga dan pemiliknya mengisi profil usahanya." />
        @else
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                @foreach($usaha as $profile)
                    <x-umkm.business-card
                        :profile="$profile"
                        :nagari="$nagari ?? $profile->nagari"
                        :show-nagari="$nagari === null"
                        :global="$nagari === null"
                    />
                @endforeach
            </div>

            <div class="mt-10">{{ $usaha->onEachSide(1)->links('public.pagination', ['label' => 'rumah usaha']) }}</div>
        @endif
    </div>
</section>
@endsection
