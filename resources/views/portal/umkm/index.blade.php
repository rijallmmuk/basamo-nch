@extends('portal.layouts.app')

@section('title', 'Produk Saya')

@php
    $statusMap = [
        'approved' => ['label' => 'Disetujui', 'class' => 'bg-emerald-100 text-emerald-700', 'icon' => 'heroicon-s-check-circle'],
        'pending' => ['label' => 'Menunggu', 'class' => 'bg-amber-100 text-amber-700', 'icon' => 'heroicon-s-clock'],
        'rejected' => ['label' => 'Ditolak', 'class' => 'bg-red-100 text-red-700', 'icon' => 'heroicon-s-x-circle'],
    ];
@endphp

@section('content')
    <x-portal.breadcrumb :items="[
        ['label' => 'Beranda', 'url' => route('portal.home')],
        ['label' => 'Produk Saya'],
    ]" />

    <div class="mb-5 flex items-end justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold text-gray-900 sm:text-2xl">Produk Saya</h1>
            <p class="mt-1 text-sm text-gray-500">Kelola profil usaha & produk UMKM Anda.</p>
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
                        <h2 class="truncate text-lg font-bold text-gray-900">{{ $profile->nama_usaha }}</h2>
                        <span class="inline-flex shrink-0 items-center rounded-full bg-indigo-50 px-2.5 py-0.5 text-xs font-semibold text-indigo-700">{{ $profile->category?->nama }}</span>
                    </div>
                    @if($profile->deskripsi)
                        <p class="mt-1.5 text-sm text-gray-500">{{ $profile->deskripsi }}</p>
                    @endif
                    <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-500">
                        <span class="inline-flex items-center gap-1.5"><x-heroicon-o-phone class="h-4 w-4 text-gray-400" /> {{ $profile->whatsapp }}</span>
                        @if($profile->alamat)
                            <span class="inline-flex items-center gap-1.5"><x-heroicon-o-map-pin class="h-4 w-4 text-gray-400" /> {{ $profile->alamat }}</span>
                        @endif
                    </div>
                </div>
                <a href="{{ route('portal.umkm.profile.edit') }}" class="shrink-0 rounded-xl p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700" aria-label="Ubah profil">
                    <x-heroicon-o-pencil-square class="h-5 w-5" />
                </a>
            </div>
        </x-portal.card>

        {{-- Daftar produk --}}
        <h3 class="mb-3 text-sm font-bold uppercase tracking-wide text-gray-400">Produk ({{ $profile->products->count() }})</h3>

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
                                class="h-20 w-20 shrink-0 rounded-xl object-cover ring-1 ring-gray-100">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start justify-between gap-2">
                                    <p class="truncate font-semibold text-gray-900">{{ $product->nama_produk }}</p>
                                    <span class="inline-flex shrink-0 items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold {{ $s['class'] }}">
                                        <x-dynamic-component :component="$s['icon']" class="h-3.5 w-3.5" /> {{ $s['label'] }}
                                    </span>
                                </div>
                                <p class="mt-0.5 text-sm font-bold text-indigo-600">
                                    {{ $product->harga ? 'Rp '.number_format($product->harga, 0, ',', '.') : 'Harga tidak dicantumkan' }}
                                </p>
                                @if($product->status->value === 'rejected' && $product->rejection_reason)
                                    <p class="mt-1.5 rounded-lg bg-red-50 px-2 py-1 text-xs text-red-600">{{ $product->rejection_reason }}</p>
                                @endif
                                <div class="mt-2 flex items-center gap-3 text-sm">
                                    <a href="{{ route('portal.umkm.products.edit', $product) }}" class="font-semibold text-indigo-600 hover:text-indigo-700">Ubah</a>
                                    <form method="POST" action="{{ route('portal.umkm.products.destroy', $product) }}"
                                        onsubmit="return confirm('Hapus produk ini?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="font-semibold text-red-500 hover:text-red-600">Hapus</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </x-portal.card>
                @endforeach
            </div>
        @endif
    @endif
@endsection
