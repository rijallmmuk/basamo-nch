@extends('portal.layouts.app')

@section('title', 'Produk Saya')

@php
    $statusMap = [
        'approved' => ['label' => 'Disetujui', 'class' => 'bg-sdg-3/10 text-sdg-3', 'icon' => 'heroicon-s-check-circle'],
        'pending' => ['label' => 'Menunggu', 'class' => 'bg-secondary-container text-on-secondary-container', 'icon' => 'heroicon-s-clock'],
        'rejected' => ['label' => 'Ditolak', 'class' => 'bg-error-container text-on-error-container', 'icon' => 'heroicon-s-x-circle'],
    ];
@endphp

@section('content')
    <x-portal.breadcrumb :items="[
        ['label' => 'Beranda', 'url' => route('portal.home')],
        ['label' => 'Produk Saya'],
    ]" />

    <div class="mb-5 flex items-end justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-on-surface sm:text-2xl">Produk Saya</h1>
            <p class="mt-1 text-sm text-on-surface-variant">Kelola profil usaha & produk UMKM Anda.</p>
        </div>
        @if($profile)
            <x-portal.button :href="route('portal.umkm.products.create')" size="sm">
                <x-heroicon-s-plus class="h-4 w-4" /> Tambah Produk
            </x-portal.button>
        @endif
    </div>

    @if(! $profile)
        {{-- Belum ada profil usaha --}}
        <x-portal.empty
            icon="heroicon-o-building-storefront"
            title="Lengkapi profil usaha Anda"
            subtitle="Isi profil usaha terlebih dahulu agar bisa menambahkan produk ke katalog.">
            <x-portal.button :href="route('portal.umkm.profile.edit')">
                <x-heroicon-s-pencil-square class="h-4 w-4" /> Isi Profil Usaha
            </x-portal.button>
        </x-portal.empty>
    @else
        {{-- Ringkasan profil --}}
        <x-portal.card class="mb-5">
            <div class="flex items-start justify-between gap-4">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <h2 class="truncate text-lg font-bold text-on-surface">{{ $profile->nama_usaha }}</h2>
                        <span class="inline-flex shrink-0 items-center rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-semibold text-primary">{{ $profile->category?->nama }}</span>
                    </div>
                    @if($profile->deskripsi)
                        <p class="mt-1.5 text-sm text-on-surface-variant">{{ $profile->deskripsi }}</p>
                    @endif
                    <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-on-surface-variant">
                        <span class="inline-flex items-center gap-1.5"><x-heroicon-o-phone class="h-4 w-4 text-outline" /> {{ $profile->whatsapp }}</span>
                        @if($profile->alamat)
                            <span class="inline-flex items-center gap-1.5"><x-heroicon-o-map-pin class="h-4 w-4 text-outline" /> {{ $profile->alamat }}</span>
                        @endif
                    </div>
                </div>
                <a href="{{ route('portal.umkm.profile.edit') }}" class="shrink-0 rounded-xl p-2 text-on-surface-variant transition-colors hover:bg-surface-container-high hover:text-on-surface" aria-label="Ubah profil">
                    <x-heroicon-o-pencil-square class="h-5 w-5" />
                </a>
            </div>
        </x-portal.card>

        {{-- Daftar produk --}}
        <h3 class="mb-3 text-sm font-bold uppercase tracking-wide text-on-surface-variant">Produk ({{ $profile->products->count() }})</h3>

        @if($profile->products->isEmpty())
            <x-portal.empty icon="heroicon-o-cube" title="Belum ada produk" subtitle="Tambahkan produk pertama Anda untuk tampil di katalog.">
                <x-portal.button :href="route('portal.umkm.products.create')">
                    <x-heroicon-s-plus class="h-4 w-4" /> Tambah Produk
                </x-portal.button>
            </x-portal.empty>
        @else
            <div class="grid gap-4 sm:grid-cols-2">
                @foreach($profile->products as $product)
                    @php $s = $statusMap[$product->status->value] ?? $statusMap['pending']; @endphp
                    <x-portal.card :padded="false">
                        <div class="flex gap-4 p-4">
                            <img src="{{ $product->coverUrl() }}" alt="{{ $product->nama_produk }}"
                                class="h-20 w-20 shrink-0 rounded-xl object-cover ring-1 ring-outline-variant">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="truncate font-semibold text-on-surface">{{ $product->nama_produk }}</p>
                                    <span class="inline-flex shrink-0 items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold {{ $s['class'] }}">
                                        <x-dynamic-component :component="$s['icon']" class="h-3.5 w-3.5" /> {{ $s['label'] }}
                                    </span>
                                </div>
                                <p class="mt-0.5 text-sm font-bold text-primary">
                                    {{ $product->harga ? 'Rp '.number_format($product->harga, 0, ',', '.') : 'Harga tidak dicantumkan' }}
                                </p>
                                @if($product->status->value === 'rejected' && $product->alasan_penolakan)
                                    <p class="mt-1.5 rounded-lg bg-error-container px-2 py-1 text-xs text-on-error-container">{{ $product->alasan_penolakan }}</p>
                                @endif
                                <div class="mt-2 flex items-center gap-3 text-sm">
                                    <a href="{{ route('portal.umkm.products.edit', $product) }}" class="font-semibold text-primary hover:text-surface-tint">Ubah</a>
                                    <form id="hapus-produk-{{ $product->id }}" method="POST" action="{{ route('portal.umkm.products.destroy', $product) }}" class="hidden">
                                        @csrf @method('DELETE')
                                    </form>
                                    <x-portal.confirm-dialog
                                        tone="danger"
                                        icon="heroicon-o-trash"
                                        title="Hapus produk?"
                                        :message="'Produk «'.$product->nama_produk.'» akan dihapus permanen dari katalog. Tindakan ini tidak dapat dibatalkan.'"
                                        confirm-label="Ya, Hapus"
                                        form="hapus-produk-{{ $product->id }}"
                                        trigger-class="font-semibold text-error transition-colors hover:text-on-error-container">
                                        Hapus
                                    </x-portal.confirm-dialog>
                                </div>
                            </div>
                        </div>
                    </x-portal.card>
                @endforeach
            </div>
        @endif
    @endif
@endsection
