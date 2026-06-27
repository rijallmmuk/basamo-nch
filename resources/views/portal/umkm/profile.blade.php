@extends('portal.layouts.app')

@section('title', $profile ? 'Ubah Profil Usaha' : 'Isi Profil Usaha')

@php
    $inputClass = 'block w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3.5 py-2.5 text-sm text-on-surface shadow-sm transition-colors focus:border-primary focus:ring-2 focus:ring-primary/20';
@endphp

@section('content')
    <x-portal.breadcrumb :items="[
        ['label' => 'Beranda', 'url' => route('portal.home')],
        ['label' => 'Produk Saya', 'url' => route('portal.umkm.index')],
        ['label' => 'Profil Usaha'],
    ]" />

    <div class="mb-5">
        <h1 class="text-xl font-bold text-on-surface sm:text-2xl">{{ $profile ? 'Ubah Profil Usaha' : 'Isi Profil Usaha' }}</h1>
        <p class="mt-1 text-sm text-on-surface-variant">Data ini tampil di katalog UMKM untuk calon pembeli.</p>
    </div>

    <x-portal.card>
        <form method="POST" action="{{ route('portal.umkm.profile.store') }}" class="space-y-5">
            @csrf

            <div>
                <label for="nama_usaha" class="mb-1.5 block text-sm font-semibold text-on-surface">Nama usaha</label>
                <input type="text" id="nama_usaha" name="nama_usaha" required
                    value="{{ old('nama_usaha', $profile?->nama_usaha) }}" class="{{ $inputClass }}">
                @error('nama_usaha') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <div>
                    <label for="umkm_category_id" class="mb-1.5 block text-sm font-semibold text-on-surface">Kategori</label>
                    <select id="umkm_category_id" name="umkm_category_id" required class="{{ $inputClass }}">
                        <option value="">— Pilih kategori —</option>
                        @foreach($kategori as $value => $label)
                            <option value="{{ $value }}" @selected((int) old('umkm_category_id', $profile?->umkm_category_id) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('umkm_category_id') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label for="whatsapp" class="mb-1.5 block text-sm font-semibold text-on-surface">No. WhatsApp</label>
                    <input type="tel" id="whatsapp" name="whatsapp" required placeholder="08xxxxxxxxxx"
                        value="{{ old('whatsapp', $profile?->whatsapp) }}" class="{{ $inputClass }}">
                    @error('whatsapp') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
                </div>
            </div>

            <div>
                <label for="deskripsi" class="mb-1.5 block text-sm font-semibold text-on-surface">Deskripsi <span class="font-normal text-on-surface-variant">(opsional)</span></label>
                <textarea id="deskripsi" name="deskripsi" rows="3" class="{{ $inputClass }}">{{ old('deskripsi', $profile?->deskripsi) }}</textarea>
                @error('deskripsi') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="alamat" class="mb-1.5 block text-sm font-semibold text-on-surface">Alamat usaha <span class="font-normal text-on-surface-variant">(opsional)</span></label>
                <textarea id="alamat" name="alamat" rows="2" class="{{ $inputClass }}">{{ old('alamat', $profile?->alamat) }}</textarea>
                @error('alamat') <p class="mt-1.5 text-xs text-error">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-3 pt-1">
                <x-portal.button type="submit">Simpan Profil</x-portal.button>
                <x-portal.button :href="route('portal.umkm.index')" variant="ghost">Batal</x-portal.button>
            </div>
        </form>
    </x-portal.card>
@endsection
