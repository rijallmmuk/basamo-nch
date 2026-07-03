@extends('portal.layouts.app')

@section('title', $product ? 'Ubah Produk' : 'Tambah Produk')

@php
    $inputClass = 'block w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3.5 py-2.5 text-sm text-on-surface shadow-sm transition-colors focus:border-primary focus:ring-2 focus:ring-primary/20';
    $existingPhotos = $product?->getMedia('photos') ?? collect();
    $maxPhotos = \App\Services\UmkmService::MAX_PHOTOS;
    $remaining = $maxPhotos - $existingPhotos->count();
    $action = $product ? route('portal.umkm.products.update', $product) : route('portal.umkm.products.store');
    $firstPhotoUrl = $existingPhotos->first()?->getUrl('card');
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

    <form method="POST" action="{{ $action }}" enctype="multipart/form-data"
        x-data="{
            namaProduk: @js(old('nama_produk', $product?->nama_produk ?? '')),
            harga: @js(old('harga', $product?->harga ? (int) $product->harga : null)),
            deskripsi: @js(old('deskripsi', $product?->deskripsi ?? '')),
            fotoUrl: @js($firstPhotoUrl),
            fotoAwal: @js($firstPhotoUrl),
            kategoriId: @js((string) old('umkm_category_id', $product?->umkm_category_id ?? '')),
            panduan: @js($panduanMap),
            contoh: @js($contohMap),
            hargaTampil() {
                return this.harga > 0 ? 'Rp ' + Number(this.harga).toLocaleString('id-ID') : 'Rp —';
            },
            panduanAktif() { return this.panduan[this.kategoriId] ?? null; },
            contohAktif() { return this.contoh[this.kategoriId] ?? null; },
        }"
        {{-- Kartu pratinjau ikut foto pertama dari pemilih foto (fallback: foto lama). --}}
        @photos-updated="fotoUrl = $event.detail.firstUrl ?? fotoAwal"
        class="grid items-start gap-5 lg:grid-cols-[minmax(0,1fr)_19rem]">
        @csrf
        @if($product) @method('PUT') @endif

        <x-portal.card>
            <div class="space-y-5">
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
                        x-model="namaProduk" class="{{ $inputClass }}">
                    @error('nama_produk') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="harga" class="mb-1.5 block text-sm font-semibold text-on-surface">Harga (Rp)</label>
                    <input type="number" id="harga" name="harga" min="0" step="500" required placeholder="mis. 25000"
                        x-model="harga" class="{{ $inputClass }}">
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
            </div>
        </x-portal.card>

        {{-- ── Preview hidup: begini tampil produkmu di katalog ── --}}
        <aside class="lg:sticky lg:top-20">
            <p class="mb-2 flex items-center gap-1.5 text-sm font-semibold text-on-surface">
                <x-heroicon-o-eye class="h-4 w-4 text-primary" /> Pratinjau di katalog
            </p>
            <div class="max-w-[18rem] overflow-hidden rounded-2xl border border-outline-variant bg-surface-container-lowest shadow-sm">
                <template x-if="fotoUrl">
                    <img :src="fotoUrl" alt="" class="aspect-square w-full object-cover">
                </template>
                <template x-if="! fotoUrl">
                    <div class="flex aspect-square w-full flex-col items-center justify-center gap-2 bg-surface-container text-on-surface-variant">
                        <x-heroicon-o-photo class="h-10 w-10 text-outline-variant" />
                        <span class="text-xs">Foto produkmu tampil di sini</span>
                    </div>
                </template>
                <div class="p-3.5">
                    <p class="truncate text-sm font-semibold text-on-surface"
                        x-text="namaProduk || 'Nama produk'" :class="namaProduk ? '' : 'text-on-surface-variant'"></p>
                    <p class="mt-0.5 text-sm font-bold text-primary" x-text="hargaTampil()"></p>
                    <p class="mt-1 line-clamp-2 text-xs text-on-surface-variant"
                        x-text="deskripsi || 'Deskripsi singkat produk akan tampil di halaman detail.'"></p>
                    <div class="mt-3 flex items-center justify-between gap-2 border-t border-outline-variant pt-2.5">
                        <span class="truncate text-xs text-on-surface-variant">{{ $namaUsaha }}</span>
                        <span class="inline-flex shrink-0 items-center gap-1 rounded-full bg-sdg-3 px-2.5 py-1 text-[10px] font-bold text-white">
                            <x-heroicon-s-chat-bubble-left-ellipsis class="h-3 w-3" /> WhatsApp
                        </span>
                    </div>
                </div>
            </div>
            <p class="mt-2 max-w-[18rem] text-xs leading-relaxed text-on-surface-variant">
                Begini kurang-lebih produkmu tampil di katalog publik setelah disetujui.
            </p>
        </aside>

        {{-- Tombol di bawah semua konten (Batal kiri, aksi utama kanan):
             urutan mobile jadi form → pratinjau → simpan. --}}
        <div class="flex items-center gap-3 lg:col-span-full">
            <x-portal.button :href="route('portal.umkm.index')" variant="ghost">Batal</x-portal.button>
            <x-portal.button type="submit">{{ $product ? 'Simpan Perubahan' : 'Ajukan Produk' }}</x-portal.button>
        </div>
    </form>
@endsection
