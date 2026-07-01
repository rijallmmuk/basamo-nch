@extends('portal.layouts.app')

@section('title', 'Profil Saya')

@php
    $inputClass = 'block w-full rounded-xl border border-outline-variant bg-surface-container-lowest px-3.5 py-2.5 text-sm text-on-surface shadow-sm transition-colors focus:border-primary focus:ring-2 focus:ring-primary/20';
    $errClass = 'border-error focus:border-error focus:ring-error/20';

    $p = $user->penduduk;
    $sebutanSubUnit = $user->desa?->jenisSubUnit?->nama ?: 'Wilayah';
    $sebutanDesa = $user->desa?->jenisDesa?->nama ?: 'desa';
    $alamat = $p?->desaUnit ? trim($sebutanSubUnit.' '.$p->desaUnit->nama) : null;

    // Baris data kependudukan (read-only). Nilai kosong ditampilkan sebagai "—".
    $identitas = [
        ['Nama lengkap', $p?->nama ?? $user->name],
        ['NIK', $user->nik ?? $p?->nik],
        ['Tempat lahir', $p?->tempat_lahir],
        ['Tanggal lahir', $p?->tanggal_lahir?->translatedFormat('d F Y')],
        ['Jenis kelamin', $p?->jenis_kelamin?->getLabel()],
        ['Agama', $p?->agama?->nama],
        ['Status perkawinan', $p?->statusPerkawinan?->nama],
        ['Pekerjaan', $p?->pekerjaan?->nama],
        [$sebutanSubUnit, $alamat],
    ];
@endphp

@section('content')
    <x-portal.breadcrumb :items="[
        ['label' => 'Beranda', 'url' => route('portal.home')],
        ['label' => 'Profil Saya'],
    ]" />

    <div class="mb-5">
        <h1 class="text-xl font-bold text-on-surface sm:text-2xl">Profil Saya</h1>
        <p class="mt-1 text-sm text-on-surface-variant">Kelola foto, kontak, dan kata sandi akunmu.</p>
    </div>

    {{-- Flash sukses dirender global oleh layout. --}}

    <div class="mx-auto max-w-[44rem] space-y-5">

        {{-- ── FOTO PROFIL ──────────────────────────────────────────── --}}
        <x-portal.card x-data="avatarCropper()">
            <h2 class="text-base font-bold text-on-surface">Foto Profil</h2>

            <div class="mt-4 flex items-center gap-4">
                <x-portal.avatar :name="$user->name" :src="$user->avatarUrl()" size="lg" variant="solid" class="!h-20 !w-20 !text-2xl" />
                <div class="min-w-0 flex-1">
                    <button type="button" @click="$refs.picker.click()"
                        class="inline-flex items-center gap-2 rounded-xl border border-outline-variant bg-surface-container-lowest px-4 py-2.5 text-sm font-bold text-on-surface transition-colors hover:bg-surface-container-low">
                        <x-heroicon-o-camera class="h-5 w-5 text-primary" />
                        {{ $user->avatarUrl() ? 'Ganti Foto' : 'Unggah Foto' }}
                    </button>
                    <p class="mt-2 text-xs text-on-surface-variant">JPG, PNG, atau WEBP</p>
                    @error('avatar')
                        <p class="mt-1.5 text-xs text-error">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Pemilih file (tersembunyi) → buka editor. --}}
            <input type="file" x-ref="picker" @change="pick" accept="image/*" class="hidden">

            {{-- Form pembawa hasil crop (di-submit oleh save()). --}}
            <form x-ref="form" method="POST" action="{{ route('portal.profile.update') }}" enctype="multipart/form-data" class="hidden">
                @csrf
                <input type="file" name="avatar" x-ref="upload">
            </form>

            {{-- Editor foto (modal) --}}
            <template x-teleport="body">
                <div x-show="open" x-cloak @keydown.escape.window="cancel()"
                    class="fixed inset-0 z-[70] flex items-end justify-center sm:items-center sm:p-4">

                    <div x-show="open" x-transition.opacity @click="cancel()"
                        class="absolute inset-0 bg-black/50 backdrop-blur-sm"></div>

                    <div x-show="open"
                        x-transition:enter="transition ease-out duration-200"
                        x-transition:enter-start="opacity-0 translate-y-6 sm:translate-y-0 sm:scale-95"
                        x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave="transition ease-in duration-150"
                        x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                        x-transition:leave-end="opacity-0 translate-y-6 sm:scale-95"
                        class="relative flex w-full max-w-[30rem] flex-col overflow-hidden rounded-t-3xl border border-outline-variant bg-surface-container-lowest shadow-xl sm:rounded-3xl"
                        role="dialog" aria-modal="true" aria-label="Atur foto profil">

                        <div class="flex shrink-0 items-center justify-between gap-3 border-b border-outline-variant px-5 py-4">
                            <div>
                                <h3 class="text-base font-bold text-on-surface">Atur Foto</h3>
                                <p class="text-xs text-on-surface-variant">Geser & zoom untuk mengatur bingkai.</p>
                            </div>
                            <button type="button" @click="cancel()" aria-label="Tutup"
                                class="flex h-8 w-8 items-center justify-center rounded-lg text-on-surface-variant transition-colors hover:bg-surface-container-high">
                                <x-heroicon-o-x-mark class="h-5 w-5" />
                            </button>
                        </div>

                        {{-- Area crop. Batas tinggi di <img> agar Cropper mengukur kotak
                             yang sudah dibatasi (mencegah crop area terpotong pada foto portrait). --}}
                        <div class="flex items-center justify-center bg-surface-container-high p-2">
                            <img x-ref="image" alt="" class="block max-h-[55vh] max-w-full">
                        </div>

                        {{-- Kontrol --}}
                        <div class="flex shrink-0 items-center justify-center gap-2 border-t border-outline-variant px-5 py-3">
                            <button type="button" @click="zoom(0.1)" aria-label="Perbesar"
                                class="flex h-10 w-10 items-center justify-center rounded-xl border border-outline-variant text-on-surface-variant transition-colors hover:bg-surface-container-high">
                                <x-heroicon-o-magnifying-glass-plus class="h-5 w-5" />
                            </button>
                            <button type="button" @click="zoom(-0.1)" aria-label="Perkecil"
                                class="flex h-10 w-10 items-center justify-center rounded-xl border border-outline-variant text-on-surface-variant transition-colors hover:bg-surface-container-high">
                                <x-heroicon-o-magnifying-glass-minus class="h-5 w-5" />
                            </button>
                            <button type="button" @click="rotate(-90)" aria-label="Putar"
                                class="flex h-10 w-10 items-center justify-center rounded-xl border border-outline-variant text-on-surface-variant transition-colors hover:bg-surface-container-high">
                                <x-heroicon-o-arrow-path class="h-5 w-5" />
                            </button>
                        </div>

                        <div class="flex shrink-0 gap-2.5 border-t border-outline-variant px-5 py-4">
                            <button type="button" @click="cancel()"
                                class="flex-1 rounded-xl border border-outline-variant bg-surface-container-lowest px-4 py-3 text-sm font-bold text-on-surface-variant transition-colors hover:bg-surface-container-low">
                                Batal
                            </button>
                            <button type="button" @click="save()" :disabled="busy"
                                class="flex flex-1 items-center justify-center gap-2 rounded-xl bg-primary px-4 py-3 text-sm font-bold text-on-primary shadow-sm transition-colors hover:bg-surface-tint disabled:opacity-60">
                                <span x-show="! busy">Simpan Foto</span>
                                <span x-show="busy" x-cloak class="flex items-center gap-2">
                                    <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                    </svg>
                                    Menyimpan…
                                </span>
                            </button>
                        </div>
                    </div>
                </div>
            </template>
        </x-portal.card>

        {{-- ── KONTAK ───────────────────────────────────────────────── --}}
        {{-- Nilai read-only dulu; field editable baru muncul setelah menekan "Ubah"
             (cegah perubahan tak sengaja). Terbuka otomatis bila ada error validasi. --}}
        <x-portal.card x-data="{ editing: {{ $errors->hasAny(['email', 'phone']) ? 'true' : 'false' }} }">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-bold text-on-surface">Kontak</h2>
                </div>
                <button type="button" x-show="! editing" @click="editing = true"
                    class="shrink-0 rounded-lg px-3 py-1.5 text-sm font-bold text-primary transition-colors hover:bg-primary/10">
                    Ubah
                </button>
            </div>

            {{-- Tampilan read-only --}}
            <dl x-show="! editing" class="mt-4 space-y-3">
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-sm text-on-surface-variant">Email</dt>
                    <dd class="text-right text-sm font-medium {{ $user->email ? 'text-on-surface' : 'text-on-surface-variant' }}">{{ $user->email ?: 'Belum diisi' }}</dd>
                </div>
                <div class="flex items-center justify-between gap-4">
                    <dt class="text-sm text-on-surface-variant">No. HP</dt>
                    <dd class="text-right text-sm font-medium {{ $user->phone ? 'text-on-surface' : 'text-on-surface-variant' }}">{{ $user->phone ?: 'Belum diisi' }}</dd>
                </div>
            </dl>

            {{-- Form (muncul setelah "Ubah") --}}
            <form x-show="editing" x-cloak method="POST" action="{{ route('portal.profile.contact') }}" class="mt-4 space-y-4">
                @csrf
                <div>
                    <label for="email" class="mb-1.5 block text-sm font-medium text-on-surface">Email <span class="font-normal text-on-surface-variant">(opsional)</span></label>
                    <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}"
                        placeholder="nama@email.com" class="{{ $inputClass }} {{ $errors->has('email') ? $errClass : '' }}">
                    @error('email')
                        <p class="mt-1.5 text-xs text-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="phone" class="mb-1.5 block text-sm font-medium text-on-surface">No. HP <span class="font-normal text-on-surface-variant">(opsional)</span></label>
                    <input type="tel" id="phone" name="phone" value="{{ old('phone', $user->phone) }}"
                        placeholder="08xxxxxxxxxx" class="{{ $inputClass }} {{ $errors->has('phone') ? $errClass : '' }}">
                    <p class="mt-1.5 text-xs text-on-surface-variant">Bisa untuk WhatsApp/Telegram. Otomatis dirapikan ke format 62.</p>
                    @error('phone')
                        <p class="mt-1.5 text-xs text-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end gap-2.5 pt-1">
                    <button type="button" @click="editing = false"
                        class="rounded-xl border border-outline-variant bg-surface-container-lowest px-4 py-2 text-sm font-bold text-on-surface-variant transition-colors hover:bg-surface-container-low">
                        Batal
                    </button>
                    <x-portal.button type="submit" size="sm">Simpan Kontak</x-portal.button>
                </div>
            </form>
        </x-portal.card>

        {{-- ── KEAMANAN (GANTI SANDI) ───────────────────────────────── --}}
        {{-- Sandi tak terbuka begitu saja; form muncul hanya setelah menekan
             "Ubah Kata Sandi". Terbuka otomatis bila ada error validasi. --}}
        <x-portal.card x-data="{ editing: {{ $errors->hasAny(['current_password', 'password']) ? 'true' : 'false' }} }">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <h2 class="text-base font-bold text-on-surface">Keamanan</h2>
                </div>
                <button type="button" x-show="! editing" @click="editing = true"
                    class="shrink-0 rounded-lg px-3 py-1.5 text-sm font-bold text-primary transition-colors hover:bg-primary/10">
                    Ubah
                </button>
            </div>

            {{-- Tampilan read-only --}}
            <div x-show="! editing" class="mt-4 flex items-center justify-between gap-4">
                <dt class="text-sm text-on-surface-variant">Kata sandi</dt>
                <dd class="text-right text-lg font-bold tracking-widest text-on-surface-variant">••••••••</dd>
            </div>

            {{-- Form (muncul setelah "Ubah") --}}
            <form x-show="editing" x-cloak method="POST" action="{{ route('portal.profile.password') }}" class="mt-4 space-y-4">
                @csrf
                <p class="text-sm text-on-surface-variant">Perlu sandi lama untuk konfirmasi.</p>
                <div>
                    <label for="current_password" class="mb-1.5 block text-sm font-medium text-on-surface">Sandi lama</label>
                    <input type="password" id="current_password" name="current_password" autocomplete="current-password"
                        class="{{ $inputClass }} {{ $errors->has('current_password') ? $errClass : '' }}">
                    @error('current_password')
                        <p class="mt-1.5 text-xs text-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password" class="mb-1.5 block text-sm font-medium text-on-surface">Sandi baru</label>
                    <input type="password" id="password" name="password" autocomplete="new-password"
                        class="{{ $inputClass }} {{ $errors->has('password') ? $errClass : '' }}">
                    <p class="mt-1.5 text-xs text-on-surface-variant">Minimal 8 karakter.</p>
                    @error('password')
                        <p class="mt-1.5 text-xs text-error">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-on-surface">Ulangi sandi baru</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password"
                        class="{{ $inputClass }}">
                </div>

                <div class="flex justify-end gap-2.5 pt-1">
                    <button type="button" @click="editing = false"
                        class="rounded-xl border border-outline-variant bg-surface-container-lowest px-4 py-2 text-sm font-bold text-on-surface-variant transition-colors hover:bg-surface-container-low">
                        Batal
                    </button>
                    <x-portal.button type="submit" size="sm">Ganti Sandi</x-portal.button>
                </div>
            </form>
        </x-portal.card>

        {{-- ── DATA KEPENDUDUKAN (READ-ONLY) ────────────────────────── --}}
        <x-portal.card :padded="false">
            <div class="border-b border-outline-variant p-5">
                <h2 class="text-base font-bold text-on-surface">Data Kependudukan</h2>
                <p class="mt-1 flex items-start gap-1.5 text-sm text-on-surface-variant">
                    <x-heroicon-s-lock-closed class="mt-0.5 h-4 w-4 shrink-0 text-outline" />
                    <span>Hanya bisa diubah oleh admin. Jika ada yang keliru, hubungi admin {{ $sebutanDesa }}.</span>
                </p>
            </div>
            <dl class="divide-y divide-outline-variant">
                @foreach($identitas as [$label, $value])
                    <div class="flex items-start justify-between gap-4 px-5 py-3">
                        <dt class="text-sm text-on-surface-variant">{{ $label }}</dt>
                        <dd class="text-right text-sm font-medium text-on-surface">{{ filled($value) ? $value : '—' }}</dd>
                    </div>
                @endforeach
            </dl>
        </x-portal.card>
    </div>
@endsection
