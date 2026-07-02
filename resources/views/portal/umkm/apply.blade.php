@extends('portal.layouts.app')

@section('title', 'Ajukan Akses UMKM')

@php
    $inputClass = 'block w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3.5 py-2.5 text-sm text-on-surface shadow-sm transition-colors focus:border-primary focus:ring-2 focus:ring-primary/20';
    $menunggu = $profile?->status_pengajuan === \App\Enums\PengajuanUmkmStatus::Menunggu;
    $ditolak = $profile?->status_pengajuan === \App\Enums\PengajuanUmkmStatus::Ditolak;
    $existingPhotos = $product?->getMedia('photos') ?? collect();
    $maxPhotos = \App\Services\UmkmService::MAX_PHOTOS;
    $remaining = $maxPhotos - $existingPhotos->count();
@endphp

@section('content')
    <x-portal.breadcrumb :items="[
        ['label' => 'Beranda', 'url' => route('portal.home')],
        ['label' => 'Ajukan Akses UMKM'],
    ]" />

    <div class="mb-5">
        <h1 class="text-xl font-bold text-on-surface sm:text-2xl">Ajukan Akses UMKM</h1>
        <p class="mt-1 text-sm text-on-surface-variant">Punya usaha? Isi profil usaha + satu produk unggulan. Setelah disetujui Admin, lapakmu tampil di katalog {{ config('app.name') }}.</p>
    </div>

    {{-- ══ Pengajuan sedang ditinjau — tanpa form ══ --}}
    @if($menunggu)
        <x-portal.card>
            <div class="flex flex-col items-center px-4 py-10 text-center">
                <span class="flex h-16 w-16 items-center justify-center rounded-full bg-secondary-container">
                    <x-heroicon-o-clock class="h-8 w-8 text-on-secondary-container" />
                </span>
                <h2 class="mt-4 text-lg font-bold text-on-surface">Pengajuan sedang ditinjau</h2>
                <p class="mt-1.5 max-w-md text-sm text-on-surface-variant">
                    Lapak <span class="font-semibold text-on-surface">"{{ $profile->nama_usaha }}"</span> diajukan
                    {{ $profile->diajukan_at?->diffForHumans() }}. Hasil tinjauan Admin akan muncul di lonceng notifikasi.
                </p>
            </div>
        </x-portal.card>
    @else
        {{-- ══ Ditolak: alasan + form prefilled untuk ajukan ulang ══ --}}
        @if($ditolak)
            <div class="mb-5 flex items-start gap-3 rounded-2xl border border-error/30 bg-error-container p-4">
                <x-heroicon-s-x-circle class="mt-0.5 h-5 w-5 shrink-0 text-error" />
                <div class="text-sm text-on-error-container">
                    <p class="font-bold">Pengajuan sebelumnya ditolak</p>
                    <p class="mt-0.5">{{ $profile->alasan_penolakan_pengajuan }}</p>
                    <p class="mt-1.5 text-on-error-container/80">Perbaiki datanya di bawah, lalu ajukan ulang.</p>
                </div>
            </div>
        @endif

        <form method="POST" action="{{ route('portal.umkm.ajukan.store') }}" enctype="multipart/form-data" class="space-y-5">
            @csrf

            {{-- ── Profil usaha ── --}}
            <x-portal.card>
                <h2 class="mb-4 flex items-center gap-2 font-bold text-on-surface">
                    <x-heroicon-s-building-storefront class="h-5 w-5 text-primary" /> Profil Usaha
                </h2>
                <div class="space-y-4">
                    <div>
                        <label for="nama_usaha" class="mb-1.5 block text-sm font-semibold text-on-surface">Nama usaha</label>
                        <input type="text" id="nama_usaha" name="nama_usaha" required
                            value="{{ old('nama_usaha', $profile?->nama_usaha) }}" class="{{ $inputClass }}" placeholder="mis. Keripik Balado Uni Ros">
                        @error('nama_usaha') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label for="umkm_category_id" class="mb-1.5 block text-sm font-semibold text-on-surface">Kategori</label>
                            <select id="umkm_category_id" name="umkm_category_id" required class="{{ $inputClass }}">
                                <option value="">— Pilih kategori —</option>
                                @foreach($kategori as $id => $nama)
                                    <option value="{{ $id }}" @selected(old('umkm_category_id', $profile?->umkm_category_id) == $id)>{{ $nama }}</option>
                                @endforeach
                            </select>
                            @error('umkm_category_id') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label for="whatsapp" class="mb-1.5 block text-sm font-semibold text-on-surface">No. WhatsApp</label>
                            <input type="tel" id="whatsapp" name="whatsapp" required
                                value="{{ old('whatsapp', $profile?->whatsapp) }}" class="{{ $inputClass }}" placeholder="0812…">
                            <p class="mt-1.5 text-xs text-on-surface-variant">Pembeli menghubungimu lewat nomor ini.</p>
                            @error('whatsapp') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div>
                        <label for="deskripsi" class="mb-1.5 block text-sm font-semibold text-on-surface">Deskripsi usaha</label>
                        <textarea id="deskripsi" name="deskripsi" rows="3" required class="{{ $inputClass }}"
                            placeholder="Ceritakan singkat usahamu…">{{ old('deskripsi', $profile?->deskripsi) }}</textarea>
                        @error('deskripsi') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="alamat" class="mb-1.5 block text-sm font-semibold text-on-surface">Alamat usaha</label>
                        <textarea id="alamat" name="alamat" rows="2" required class="{{ $inputClass }}">{{ old('alamat', $profile?->alamat) }}</textarea>
                        @error('alamat') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </x-portal.card>

            {{-- ── Produk unggulan ── --}}
            <x-portal.card>
                <h2 class="mb-1 flex items-center gap-2 font-bold text-on-surface">
                    <x-heroicon-s-shopping-bag class="h-5 w-5 text-primary" /> Produk Unggulan
                </h2>
                <p class="mb-4 text-xs text-on-surface-variant">Satu produk terbaikmu — ikut tampil di katalog begitu pengajuan disetujui.</p>
                <div class="space-y-4">
                    <div>
                        <label for="nama_produk" class="mb-1.5 block text-sm font-semibold text-on-surface">Nama produk</label>
                        <input type="text" id="nama_produk" name="nama_produk" required
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
                        <label for="deskripsi_produk" class="mb-1.5 block text-sm font-semibold text-on-surface">Deskripsi produk</label>
                        <textarea id="deskripsi_produk" name="deskripsi_produk" rows="3" required class="{{ $inputClass }}">{{ old('deskripsi_produk', $product?->deskripsi) }}</textarea>
                        @error('deskripsi_produk') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                    </div>

                    @if($existingPhotos->isNotEmpty())
                        <div>
                            <p class="mb-2 block text-sm font-semibold text-on-surface">Foto dari pengajuan sebelumnya</p>
                            <div class="flex flex-wrap gap-3">
                                @foreach($existingPhotos as $media)
                                    <img src="{{ $media->getUrl('card') }}" alt="" class="h-24 w-24 rounded-xl object-cover ring-1 ring-outline-variant">
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div>
                        <label for="photos" class="mb-1.5 block text-sm font-semibold text-on-surface">
                            {{ $existingPhotos->isNotEmpty() ? 'Tambah foto' : 'Foto produk' }}
                            <span class="font-normal text-on-surface-variant">(maks {{ $maxPhotos }}{{ $existingPhotos->isNotEmpty() ? ', sisa '.max(0, $remaining).' slot' : ', minimal 1' }})</span>
                        </label>
                        <input type="file" id="photos" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple
                            @disabled($remaining <= 0)
                            class="block w-full text-sm text-on-surface-variant file:mr-3 file:rounded-xl file:border-0 file:bg-primary/10 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-primary hover:file:bg-primary/20 disabled:opacity-50">
                        <p class="mt-1.5 text-xs text-on-surface-variant">JPG, PNG, atau WEBP. Maks 2MB per foto.</p>
                        @error('photos') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                        @error('photos.*') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                    </div>
                </div>
            </x-portal.card>

            <div class="flex items-center gap-3">
                <x-portal.button type="submit" size="lg">
                    <x-heroicon-o-paper-airplane class="h-5 w-5" />
                    {{ $ditolak ? 'Ajukan Ulang' : 'Kirim Pengajuan' }}
                </x-portal.button>
                <x-portal.button :href="route('portal.home')" variant="ghost">Batal</x-portal.button>
            </div>
        </form>
    @endif
@endsection
