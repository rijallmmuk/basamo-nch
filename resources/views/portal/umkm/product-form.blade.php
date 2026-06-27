@extends('portal.layouts.app')

@section('title', $product ? 'Ubah Produk' : 'Tambah Produk')

@php
    $inputClass = 'block w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3.5 py-2.5 text-sm text-on-surface shadow-sm transition-colors focus:border-primary focus:ring-2 focus:ring-primary/20';
    $existingPhotos = $product?->getMedia('photos') ?? collect();
    $maxPhotos = \App\Services\UmkmService::MAX_PHOTOS;
    $remaining = $maxPhotos - $existingPhotos->count();
    $action = $product ? route('portal.umkm.products.update', $product) : route('portal.umkm.products.store');
@endphp

@section('content')
    <x-portal.breadcrumb :items="[
        ['label' => 'Beranda', 'url' => route('portal.home')],
        ['label' => 'Produk Saya', 'url' => route('portal.umkm.index')],
        ['label' => $product ? 'Ubah Produk' : 'Tambah Produk'],
    ]" />

    <div class="mb-5">
        <h1 class="text-xl font-bold text-on-surface sm:text-2xl">{{ $product ? 'Ubah Produk' : 'Tambah Produk' }}</h1>
        <p class="mt-1 text-sm text-on-surface-variant">Produk akan ditinjau Admin Desa sebelum tampil di katalog.</p>
    </div>

    <x-portal.card>
        <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-5">
            @csrf
            @if($product) @method('PUT') @endif

            <div>
                <label for="nama_produk" class="mb-1.5 block text-sm font-semibold text-on-surface">Nama produk</label>
                <input type="text" id="nama_produk" name="nama_produk" required
                    value="{{ old('nama_produk', $product?->nama_produk) }}" class="{{ $inputClass }}">
                @error('nama_produk') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="harga" class="mb-1.5 block text-sm font-semibold text-on-surface">Harga (Rp) <span class="font-normal text-on-surface-variant">(opsional)</span></label>
                <input type="number" id="harga" name="harga" min="0" step="500" placeholder="mis. 25000"
                    value="{{ old('harga', $product?->harga ? (int) $product->harga : null) }}" class="{{ $inputClass }}">
                @error('harga') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="deskripsi" class="mb-1.5 block text-sm font-semibold text-on-surface">Deskripsi <span class="font-normal text-on-surface-variant">(opsional)</span></label>
                <textarea id="deskripsi" name="deskripsi" rows="3" class="{{ $inputClass }}">{{ old('deskripsi', $product?->deskripsi) }}</textarea>
                @error('deskripsi') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
            </div>

            {{-- Foto produk yang sudah ada (edit) --}}
            @if($existingPhotos->isNotEmpty())
                <div>
                    <p class="mb-1.5 block text-sm font-semibold text-on-surface">Foto saat ini</p>
                    <p class="mb-2 text-xs text-on-surface-variant">Centang foto yang ingin dihapus.</p>
                    <div class="flex flex-wrap gap-3">
                        @foreach($existingPhotos as $media)
                            <label class="group relative cursor-pointer">
                                <img src="{{ $media->getUrl('card') }}" alt="" class="h-24 w-24 rounded-xl object-cover ring-1 ring-outline-variant">
                                <span class="absolute inset-0 flex items-start justify-end rounded-xl bg-black/0 p-1.5 transition-colors group-has-[:checked]:bg-error/30">
                                    <input type="checkbox" name="remove_photos[]" value="{{ $media->id }}"
                                        class="h-5 w-5 rounded border-white accent-error shadow">
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- Tambah foto --}}
            <div>
                <label for="photos" class="mb-1.5 block text-sm font-semibold text-on-surface">
                    {{ $product ? 'Tambah foto' : 'Foto produk' }}
                    <span class="font-normal text-on-surface-variant">(maks {{ $maxPhotos }}, sisa {{ max(0, $remaining) }} slot)</span>
                </label>
                <input type="file" id="photos" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple
                    @disabled($product && $remaining <= 0)
                    class="block w-full text-sm text-on-surface-variant file:mr-3 file:rounded-xl file:border-0 file:bg-primary/10 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-primary hover:file:bg-primary/20 disabled:opacity-50">
                <p class="mt-1.5 text-xs text-on-surface-variant">JPG, PNG, atau WEBP. Maks 2MB per foto.</p>
                @error('photos') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                @error('photos.*') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-3 pt-1">
                <x-portal.button type="submit">{{ $product ? 'Simpan Perubahan' : 'Ajukan Produk' }}</x-portal.button>
                <x-portal.button :href="route('portal.umkm.index')" variant="ghost">Batal</x-portal.button>
            </div>
        </form>
    </x-portal.card>
@endsection
