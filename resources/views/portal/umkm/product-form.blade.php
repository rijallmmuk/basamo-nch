@extends('portal.layouts.app')

@section('title', $product ? 'Ubah Produk' : 'Tambah Produk')
@section('main-width', 'max-w-3xl')

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
        <p class="mt-1 text-sm text-on-surface-variant">Produk akan ditinjau Admin {{ auth()->user()->desa?->jenisDesa?->nama ?? 'Desa' }} sebelum tampil di katalog.</p>
    </div>

    <x-portal.card>
        <form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="space-y-5"
            x-data="{
                deskripsi: @js(old('deskripsi', $product?->deskripsi ?? '')),
                kategoriId: @js((string) old('umkm_category_id', $product?->umkm_category_id ?? '')),
                panduan: @js($panduanMap),
                contoh: @js($contohMap),
                panduanAktif() { return this.panduan[this.kategoriId] ?? null; },
                contohAktif() { return this.contoh[this.kategoriId] ?? null; },
            }">
            @csrf
            @if($product) @method('PUT') @endif

            {{-- Kategori milik PRODUK (pola marketplace) — pilih dulu agar panduan
                 & contoh deskripsi kategorinya muncul. --}}
            <div>
                <label for="umkm_category_id" class="mb-1.5 block text-sm font-semibold text-on-surface">Kategori produk</label>
                <select id="umkm_category_id" name="umkm_category_id" required x-model="kategoriId" class="{{ $inputClass }}">
                    <option value="">— Pilih kategori —</option>
                    @foreach($kategori as $id => $nama)
                        <option value="{{ $id }}">{{ $nama }}</option>
                    @endforeach
                </select>
                @error('umkm_category_id') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="nama_produk" class="mb-1.5 block text-sm font-semibold text-on-surface">Nama produk</label>
                <input type="text" id="nama_produk" name="nama_produk" required placeholder="mis. Keripik Balado 250gr"
                    value="{{ old('nama_produk', $product?->nama_produk) }}" class="{{ $inputClass }}">
                @error('nama_produk') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="harga" class="mb-1.5 block text-sm font-semibold text-on-surface">Harga (Rp)</label>
                <input type="number" id="harga" name="harga" min="0" step="500" required placeholder="mis. 25000"
                    value="{{ old('harga', $product?->harga ? (int) $product->harga : null) }}" class="{{ $inputClass }}">
                @error('harga') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="deskripsi" class="mb-1.5 block text-sm font-semibold text-on-surface">Deskripsi produk</label>
                <textarea id="deskripsi" name="deskripsi" rows="5" required x-model="deskripsi"
                    placeholder="Ikuti panduan di bawah — pilih kategori produk dulu agar panduannya muncul." class="{{ $inputClass }}">{{ old('deskripsi', $product?->deskripsi) }}</textarea>
                @error('deskripsi') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror

                {{-- Bantuan umum mengikuti kategori PRODUK terpilih (live): 3 poin
                     panduan + contoh deskripsi sekali klik. Detail lanjutan urusan WhatsApp. --}}
                <div class="mt-2 rounded-xl border border-primary/20 bg-primary/5 p-3.5" x-show="panduanAktif()" x-cloak>
                    <p class="flex items-center gap-1.5 text-xs font-bold text-primary">
                        <x-heroicon-o-light-bulb class="h-4 w-4" /> Cukup sebutkan:
                    </p>
                    <ul class="mt-1.5 space-y-0.5 text-xs leading-relaxed text-on-surface-variant">
                        <template x-for="baris in (panduanAktif() ?? '').split('\n').filter(b => b.trim())" :key="baris">
                            <li class="flex gap-1.5"><span class="text-primary">•</span><span x-text="baris"></span></li>
                        </template>
                    </ul>
                    <p class="mt-1.5 text-xs italic leading-relaxed text-on-surface-variant">
                        Boleh singkat, boleh rinci — deskripsi lengkap membuat lapak makin meyakinkan.
                        Detail lainnya bisa ditanyakan pembeli lewat WhatsApp.
                    </p>
                    <button type="button" x-show="! deskripsi.trim() && contohAktif()" @click="deskripsi = contohAktif()"
                        class="mt-2 text-xs font-bold text-primary underline-offset-2 hover:underline">
                        Bingung mulai? Pakai contoh, tinggal ganti kata-katanya →
                    </button>
                </div>
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
                <label class="mb-1.5 block text-sm font-semibold text-on-surface">
                    {{ $product ? 'Tambah foto' : 'Foto produk' }}
                    <span class="font-normal text-on-surface-variant">({{ $product ? 'sisa '.max(0, $remaining).' slot' : 'minimal 1, maks '.$maxPhotos }})</span>
                </label>
                <x-portal.photo-picker :max="$product ? max(0, $remaining) : $maxPhotos" />
                <p class="mt-1.5 text-xs text-on-surface-variant">JPG, PNG, atau WEBP — ukuran bebas, otomatis dikompres. Foto pertama jadi sampul produk.</p>
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
