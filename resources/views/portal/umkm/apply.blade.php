@extends('portal.layouts.app')

@section('title', 'Ajukan Akses UMKM')

@php
    $inputClass = 'block w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3.5 py-2.5 text-sm text-on-surface shadow-sm transition-colors focus:border-primary focus:ring-2 focus:ring-primary/20';
    $menunggu = $profile?->status_pengajuan === \App\Enums\PengajuanUmkmStatus::Menunggu;
    $ditolak = $profile?->status_pengajuan === \App\Enums\PengajuanUmkmStatus::Ditolak;
    $existingPhotos = $product?->getMedia('photos') ?? collect();
    $maxPhotos = \App\Services\UmkmService::MAX_PHOTOS;
    $remaining = $maxPhotos - $existingPhotos->count();
    $firstPhotoUrl = $existingPhotos->first()?->getUrl('card');
@endphp

@section('content')
    <x-portal.breadcrumb :items="[
        ['label' => 'Beranda', 'url' => route('portal.home')],
        ['label' => 'Ajukan Akses UMKM'],
    ]" />

    <div class="mb-5">
        <h1 class="text-xl font-bold text-on-surface sm:text-2xl">Ajukan Akses UMKM</h1>
        <p class="mt-1 text-sm text-on-surface-variant">Promosikan usahamu di katalog {{ config('app.name') }} — gratis, cukup tiga langkah.</p>
    </div>

    {{-- ── Cara kerja: 3 langkah (jelas sejak awal, tanpa tanda tanya) ── --}}
    <div class="mb-5 grid gap-3 sm:grid-cols-3">
        @foreach([
            ['1', 'Isi profil usaha + 1 produk terbaik', 'Cukup SATU produk unggulan untuk pengajuan.'],
            ['2', 'Admin Desa meninjau', 'Hasilnya masuk ke lonceng notifikasi — disetujui atau ditolak beserta alasannya.'],
            ['3', 'Lapak tayang di katalog', 'Setelah disetujui, tambahkan SEMUA produkmu lewat menu "Produk Saya".'],
        ] as [$step, $judul, $ket])
            <div class="flex gap-3 rounded-2xl border border-outline-variant bg-surface-container-lowest p-4">
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary text-sm font-bold text-on-primary">{{ $step }}</span>
                <div>
                    <p class="text-sm font-bold text-on-surface">{{ $judul }}</p>
                    <p class="mt-0.5 text-xs leading-relaxed text-on-surface-variant">{{ $ket }}</p>
                </div>
            </div>
        @endforeach
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
                    {{ $profile->diajukan_at?->diffForHumans() }}. Hasil tinjauan Admin Desa akan muncul di lonceng
                    notifikasi — begitu disetujui, kamu langsung bisa menambahkan semua produkmu.
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

        <form method="POST" action="{{ route('portal.umkm.ajukan.store') }}" enctype="multipart/form-data"
            x-data="{
                namaUsaha: @js(old('nama_usaha', $profile?->nama_usaha ?? '')),
                namaProduk: @js(old('nama_produk', $product?->nama_produk ?? '')),
                harga: @js(old('harga', $product?->harga ? (int) $product->harga : null)),
                deskripsiProduk: @js(old('deskripsi_produk', $product?->deskripsi ?? '')),
                fotoUrl: @js($firstPhotoUrl),
                fotoAwal: @js($firstPhotoUrl),
                hargaTampil() {
                    return this.harga > 0 ? 'Rp ' + Number(this.harga).toLocaleString('id-ID') : 'Rp —';
                },
            }"
            {{-- Kartu pratinjau ikut foto pertama dari pemilih foto (fallback: foto lama). --}}
            @photos-updated="fotoUrl = $event.detail.firstUrl ?? fotoAwal"
            class="grid items-start gap-5 lg:grid-cols-5">
            @csrf

            <div class="space-y-5 lg:col-span-3">
                {{-- ── Profil usaha ── --}}
                <x-portal.card>
                    <h2 class="mb-4 flex items-center gap-2 font-bold text-on-surface">
                        <x-heroicon-s-building-storefront class="h-5 w-5 text-primary" /> Profil Usaha
                    </h2>
                    <div class="space-y-4">
                        <div>
                            <label for="nama_usaha" class="mb-1.5 block text-sm font-semibold text-on-surface">Nama usaha</label>
                            <input type="text" id="nama_usaha" name="nama_usaha" required x-model="namaUsaha"
                                class="{{ $inputClass }}" placeholder="mis. Keripik Balado Uni Ros">
                            @error('nama_usaha') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                        </div>

                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="umkm_category_id" class="mb-1.5 block text-sm font-semibold text-on-surface">Kategori usaha</label>
                                <select id="umkm_category_id" name="umkm_category_id" required class="{{ $inputClass }}">
                                    <option value="">— Pilih kategori —</option>
                                    @foreach($kategori as $id => $nama)
                                        <option value="{{ $id }}" @selected(old('umkm_category_id', $profile?->umkm_category_id) == $id)>{{ $nama }}</option>
                                    @endforeach
                                </select>
                                @error('umkm_category_id') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="whatsapp" class="mb-1.5 block text-sm font-semibold text-on-surface">No. WhatsApp usaha</label>
                                <input type="tel" id="whatsapp" name="whatsapp" required
                                    value="{{ old('whatsapp', $profile?->whatsapp) }}" class="{{ $inputClass }}" placeholder="0812…">
                                <p class="mt-1.5 text-xs text-on-surface-variant">Tombol "Hubungi" di katalog mengarah ke nomor ini.</p>
                                @error('whatsapp') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div>
                            <label for="alamat" class="mb-1.5 block text-sm font-semibold text-on-surface">Alamat lengkap usaha</label>
                            <textarea id="alamat" name="alamat" rows="2" required class="{{ $inputClass }}"
                                placeholder="mis. Jorong Koto Tuo, samping masjid raya, {{ auth()->user()->desa?->nama_lengkap ?? 'desa' }}">{{ old('alamat', $profile?->alamat) }}</textarea>
                            <p class="mt-1.5 text-xs text-on-surface-variant">Tulis selengkap mungkin (jorong/dusun, patokan) — memudahkan pembeli menemukanmu.</p>
                            @error('alamat') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </x-portal.card>

                {{-- ── Produk unggulan ── --}}
                <x-portal.card>
                    <h2 class="mb-1 flex items-center gap-2 font-bold text-on-surface">
                        <x-heroicon-s-shopping-bag class="h-5 w-5 text-primary" /> Produk Unggulan
                    </h2>
                    <p class="mb-4 rounded-xl bg-primary/5 px-3.5 py-2.5 text-xs leading-relaxed text-on-surface-variant">
                        <span class="font-semibold text-primary">Cukup satu produk terbaikmu untuk pengajuan ini.</span>
                        Setelah disetujui, kamu bisa menambahkan semua produk lain lewat menu "Produk Saya" — tanpa batas.
                    </p>
                    <div class="space-y-4">
                        <div>
                            <label for="nama_produk" class="mb-1.5 block text-sm font-semibold text-on-surface">Nama produk</label>
                            <input type="text" id="nama_produk" name="nama_produk" required x-model="namaProduk"
                                class="{{ $inputClass }}" placeholder="mis. Keripik Balado 250gr">
                            @error('nama_produk') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="harga" class="mb-1.5 block text-sm font-semibold text-on-surface">Harga (Rp)</label>
                            <input type="number" id="harga" name="harga" min="0" step="500" required x-model="harga"
                                placeholder="mis. 25000" class="{{ $inputClass }}">
                            @error('harga') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="deskripsi_produk" class="mb-1.5 block text-sm font-semibold text-on-surface">Deskripsi produk</label>
                            <textarea id="deskripsi_produk" name="deskripsi_produk" rows="3" required x-model="deskripsiProduk"
                                placeholder="Bahan, rasa, ukuran/berat, keunggulan…" class="{{ $inputClass }}">{{ old('deskripsi_produk', $product?->deskripsi) }}</textarea>
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
                            <label class="mb-1.5 block text-sm font-semibold text-on-surface">
                                {{ $existingPhotos->isNotEmpty() ? 'Tambah foto' : 'Foto produk' }}
                                <span class="font-normal text-on-surface-variant">({{ $existingPhotos->isNotEmpty() ? 'sisa '.max(0, $remaining).' slot' : 'minimal 1, maks '.$maxPhotos }})</span>
                            </label>
                            <x-portal.photo-picker :max="max(0, $remaining)" />
                            <p class="mt-1.5 text-xs text-on-surface-variant">JPG, PNG, atau WEBP — maks 2MB per foto. Foto pertama jadi sampul produk.</p>
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
            </div>

            {{-- ── Preview hidup: begini tampil produkmu di katalog ── --}}
            <aside class="lg:sticky lg:top-20 lg:col-span-2">
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
                            x-text="deskripsiProduk || 'Deskripsi singkat produk akan tampil di halaman detail.'"></p>
                        <div class="mt-3 flex items-center justify-between gap-2 border-t border-outline-variant pt-2.5">
                            <span class="truncate text-xs text-on-surface-variant" x-text="namaUsaha || 'Nama usahamu'"></span>
                            <span class="inline-flex shrink-0 items-center gap-1 rounded-full bg-sdg-3 px-2.5 py-1 text-[10px] font-bold text-white">
                                <x-heroicon-s-chat-bubble-left-ellipsis class="h-3 w-3" /> WhatsApp
                            </span>
                        </div>
                    </div>
                </div>
                <p class="mt-2 max-w-[18rem] text-xs leading-relaxed text-on-surface-variant">
                    Begini kurang-lebih produkmu tampil di katalog publik setelah pengajuan disetujui.
                </p>
            </aside>
        </form>
    @endif
@endsection
