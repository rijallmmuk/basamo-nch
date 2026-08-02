@extends('portal.layouts.app')

@section('title', 'Profil Saya')
@section('main-class', 'py-6 pb-28 lg:pb-10')

@php
    $user = auth()->user();
    $p = $user->penduduk;
    $errClass = 'border-error focus:border-error focus:ring-error/20';

    $identitas = [
        ['label' => 'Nama Lengkap', 'value' => $p?->nama ?? $user->name, 'icon' => 'heroicon-o-user'],
        ['label' => 'NIK', 'value' => $user->nik ?? $p?->nik, 'icon' => 'heroicon-o-identification'],
        ['label' => 'Tempat Lahir', 'value' => $p?->tempat_lahir, 'icon' => 'heroicon-o-map-pin'],
        ['label' => 'Tanggal Lahir', 'value' => $p?->tanggal_lahir?->translatedFormat('d F Y'), 'icon' => 'heroicon-o-calendar'],
        ['label' => 'Jenis Kelamin', 'value' => $p?->jenis_kelamin?->getLabel(), 'icon' => 'heroicon-o-user-group'],
        ['label' => 'Agama', 'value' => $p?->agama?->nama, 'icon' => 'heroicon-o-sparkles'],
        ['label' => 'Pendidikan Terakhir', 'value' => $p?->pendidikan?->nama, 'icon' => 'heroicon-o-academic-cap'],
        ['label' => 'Status Perkawinan', 'value' => $p?->statusPerkawinan?->nama, 'icon' => 'heroicon-o-heart'],
        ['label' => 'Pekerjaan', 'value' => $p?->pekerjaan?->nama, 'icon' => 'heroicon-o-briefcase'],
    ];

    $isUmkmOwner = $user->hasUmkmAccess();
    $hasAvatar = filled($user->avatarUrl());
    $hasEmail = filled($user->email);
    $hasPhone = filled($user->phone);
@endphp

@section('content')
<div class="space-y-6 max-w-[120rem] mx-auto">

    {{-- ── 1. NAVIGASI BACK LINK ───────────────────────────────────────── --}}
    <div>
        <a href="{{ route('portal.home') }}"
            class="inline-flex items-center gap-1.5 text-xs sm:text-sm font-bold text-on-surface-variant hover:text-primary transition-colors">
            <x-heroicon-s-arrow-left class="h-4 w-4" />
            <span>Kembali ke Beranda</span>
        </a>
    </div>

    {{-- ── 2. HERO PROFILE BANNER CARD ────────────────────────────────── --}}
    <section class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-[#003857] via-[#004f7a] to-[#0a6291] p-6 text-white shadow-md md:p-8" x-data="avatarCropper()">
        <div class="relative z-10 flex flex-col gap-6 md:flex-row md:items-center md:justify-between">
            
            {{-- User Main Info & Avatar --}}
            <div class="flex flex-col sm:flex-row items-center sm:items-start md:items-center gap-5 text-center sm:text-left">
                
                {{-- Avatar Container with Trigger Camera Overlay --}}
                <div class="relative shrink-0 group cursor-pointer" @click="$refs.picker.click()">
                    <div class="h-24 w-24 sm:h-28 sm:w-28 overflow-hidden rounded-full ring-4 ring-white/30 shadow-xl bg-surface-container-high transition-transform duration-300 group-hover:scale-105">
                        <x-portal.avatar :name="$user->name" :src="$user->avatarUrl()" variant="solid" class="!h-full !w-full !text-3xl" />
                    </div>
                    
                    {{-- Camera Badge Overlay --}}
                    <div class="absolute inset-0 flex flex-col items-center justify-center rounded-full bg-black/40 text-white opacity-0 transition-opacity duration-200 group-hover:opacity-100 backdrop-blur-xs">
                        <x-heroicon-s-camera class="h-7 w-7 text-amber-300" />
                        <span class="text-[10px] font-black tracking-wider uppercase mt-0.5">Ubah</span>
                    </div>

                    {{-- Verified Badge --}}
                    <span class="absolute bottom-1 right-1 flex h-7 w-7 items-center justify-center rounded-full bg-emerald-500 text-white ring-2 ring-white shadow-md" title="Akun Warga Terverifikasi">
                        <x-heroicon-s-check-badge class="h-4 w-4" />
                    </span>
                </div>

                {{-- Text Details --}}
                <div class="min-w-0 space-y-1.5">
                    <div class="flex flex-wrap items-center justify-center sm:justify-start gap-2">
                        <span class="inline-flex items-center gap-1 rounded-full bg-white/15 px-3 py-0.5 text-xs font-semibold text-sky-200 backdrop-blur-sm">
                            <x-heroicon-s-map-pin class="h-3.5 w-3.5 text-amber-300" />
                            <span>{{ $user->nagari?->nama_lengkap ?? 'Nagari Registered' }}</span>
                        </span>

                        @if($isUmkmOwner)
                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-400/90 px-3 py-0.5 text-xs font-black text-[#003857] shadow-xs">
                                <x-heroicon-s-building-storefront class="h-3.5 w-3.5" />
                                <span>Pemilik UMKM</span>
                            </span>
                        @endif
                    </div>

                    <h1 class="text-2xl font-black tracking-tight sm:text-3xl lg:text-4xl text-white">{{ $user->name }}</h1>
                    
                    <p class="text-xs sm:text-sm text-slate-200 flex items-center justify-center sm:justify-start gap-2 font-medium">
                        <span>NIK: <strong class="text-white font-mono tracking-wider">{{ $user->nik ?? $p?->nik ?? '—' }}</strong></span>
                        <span class="text-slate-400">•</span>
                        <span>Terdaftar {{ $user->created_at?->translatedFormat('F Y') ?? '—' }}</span>
                    </p>
                </div>
            </div>

            {{-- Right CTA / Quick Avatar Change --}}
            <div class="flex flex-col sm:flex-row md:flex-col items-center md:items-end justify-center gap-3 shrink-0">
                <button type="button" @click="$refs.picker.click()"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 rounded-xl bg-amber-400 px-4 py-2.5 text-xs font-black text-[#003857] shadow-sm transition-all hover:bg-amber-300 active:scale-95">
                    <x-heroicon-s-camera class="h-4 w-4" />
                    <span>{{ $hasAvatar ? 'Ganti Foto Profil' : 'Unggah Foto Profil' }}</span>
                </button>
                <p class="text-[11px] text-slate-300">Format JPEG/PNG/WEBP (Maks. 10 MB)</p>
            </div>

        </div>

        {{-- Decorative Blur Orb --}}
        <div class="absolute -bottom-12 -right-12 h-48 w-48 rounded-full bg-amber-400/10 blur-2xl pointer-events-none"></div>

        {{-- Hidden Picker & Form for Cropper --}}
        <input type="file" x-ref="picker" @change="pick" accept="image/*" class="hidden">
        <form x-ref="form" method="POST" action="{{ route('portal.profile.update') }}" enctype="multipart/form-data" class="hidden">
            @csrf
            <input type="file" name="avatar" x-ref="upload">
        </form>

        {{-- Modal Cropper --}}
        <template x-teleport="body">
            <div x-show="open" x-cloak @keydown.escape.window="cancel()"
                class="fixed inset-0 z-[70] flex items-end justify-center sm:items-center sm:p-4">

                <div x-show="open" x-transition.opacity @click="cancel()"
                    class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>

                <div x-show="open"
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-y-6 sm:translate-y-0 sm:scale-95"
                    x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                    x-transition:leave-end="opacity-0 translate-y-6 sm:scale-95"
                    class="relative flex w-full max-w-[30rem] flex-col overflow-hidden rounded-t-3xl border border-outline-variant bg-surface shadow-2xl sm:rounded-3xl"
                    role="dialog" aria-modal="true" aria-label="Atur foto profil">

                    <div class="flex shrink-0 items-center justify-between gap-3 border-b border-outline-variant px-5 py-4 bg-surface-bright">
                        <div>
                            <h3 class="text-base font-bold text-on-surface">Atur Bingkai Foto</h3>
                            <p class="text-xs text-on-surface-variant">Geser & zoom untuk mengatur framing foto Anda.</p>
                        </div>
                        <button type="button" @click="cancel()" aria-label="Tutup"
                            class="flex h-8 w-8 items-center justify-center rounded-lg text-on-surface-variant transition-colors hover:bg-surface-container-high">
                            <x-heroicon-o-x-mark class="h-5 w-5" />
                        </button>
                    </div>

                    {{-- Cropper 2 menyembunyikan <img> ini lalu menyisipkan <cropper-canvas> --}}
                    {{-- tepat sesudahnya, jadi wadahnya block agar kanvas dapat lebar penuh. --}}
                    <div class="bg-slate-900 p-3">
                        <img x-ref="image" alt="" class="block max-h-[55vh] max-w-full">
                    </div>

                    <div class="flex shrink-0 items-center justify-center gap-3 border-t border-outline-variant bg-surface-container-low px-5 py-3">
                        <button type="button" @click="zoom(0.1)" title="Perbesar"
                            class="flex h-10 w-10 items-center justify-center rounded-xl border border-outline-variant bg-surface text-on-surface transition-colors hover:bg-surface-container-high">
                            <x-heroicon-o-magnifying-glass-plus class="h-5 w-5" />
                        </button>
                        <button type="button" @click="zoom(-0.1)" title="Perkecil"
                            class="flex h-10 w-10 items-center justify-center rounded-xl border border-outline-variant bg-surface text-on-surface transition-colors hover:bg-surface-container-high">
                            <x-heroicon-o-magnifying-glass-minus class="h-5 w-5" />
                        </button>
                        <button type="button" @click="rotate(-90)" title="Putar 90 Derajat"
                            class="flex h-10 w-10 items-center justify-center rounded-xl border border-outline-variant bg-surface text-on-surface transition-colors hover:bg-surface-container-high">
                            <x-heroicon-o-arrow-path class="h-5 w-5" />
                        </button>
                    </div>

                    <div class="flex shrink-0 justify-end gap-2.5 border-t border-outline-variant px-5 py-4 bg-surface">
                        <button type="button" @click="cancel()"
                            class="rounded-xl border border-outline-variant bg-surface px-4 py-2.5 text-xs font-bold text-on-surface-variant transition-colors hover:bg-surface-container-high">
                            Batal
                        </button>
                        <button type="button" @click="save()" :disabled="busy"
                            class="flex items-center justify-center gap-2 rounded-xl bg-primary px-5 py-2.5 text-xs font-extrabold text-on-primary shadow-sm transition-colors hover:bg-primary/90 disabled:opacity-60">
                            <span x-show="! busy">Simpan & Aplikasikan</span>
                            <span x-show="busy" x-cloak class="flex items-center gap-2">
                                <svg class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Memproses...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </section>

    {{-- ── 3. MAIN CONTENT GRID (2 COLUMNS) ───────────────────────────── --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
        
        {{-- ── LEFT COLUMN (ACTIONS & FORMS: 7 Cols) ──────────────────── --}}
        <div class="space-y-6 lg:col-span-7">
            
            {{-- CARD 1: PENGATURAN KONTAK (Email & Phone) --}}
            <div class="rounded-xl border border-outline-variant bg-surface p-5 shadow-xs transition-all"
                 x-data="{ editing: {{ $errors->hasAny(['email', 'phone']) ? 'true' : 'false' }} }">
                
                <div class="flex items-center justify-between border-b border-outline-variant pb-3 mb-4">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-sky-500/10 text-sky-600 dark:text-sky-400">
                            <x-heroicon-s-envelope class="h-5 w-5" />
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-on-surface">Informasi Kontak</h2>
                            <p class="text-[11px] text-on-surface-variant">Alamat email dan nomor seluler untuk korespondensi.</p>
                        </div>
                    </div>

                    <button type="button" x-show="! editing" @click="editing = true"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-outline-variant bg-surface px-3 py-1.5 text-xs font-bold text-primary hover:bg-surface-container-high transition-colors">
                        <x-heroicon-o-pencil-square class="h-4 w-4" />
                        <span>Ubah Kontak</span>
                    </button>
                </div>

                {{-- READ-ONLY DISPLAY --}}
                <div x-show="! editing" class="space-y-3">
                    <div class="flex items-center justify-between rounded-lg border border-outline-variant/60 p-3 bg-surface-container-lowest">
                        <div class="flex items-center gap-2.5">
                            <x-heroicon-o-envelope class="h-4 w-4 text-on-surface-variant" />
                            <span class="text-xs font-medium text-on-surface-variant">Email:</span>
                        </div>
                        <span class="text-xs font-bold text-on-surface">{{ $user->email ?: 'Belum diisi' }}</span>
                    </div>

                    <div class="flex items-center justify-between rounded-lg border border-outline-variant/60 p-3 bg-surface-container-lowest">
                        <div class="flex items-center gap-2.5">
                            <x-heroicon-o-phone class="h-4 w-4 text-on-surface-variant" />
                            <span class="text-xs font-medium text-on-surface-variant">No. HP / WhatsApp:</span>
                        </div>
                        <span class="text-xs font-bold text-on-surface">{{ $user->phone ?: 'Belum diisi' }}</span>
                    </div>
                </div>

                {{-- EDIT FORM --}}
                <form id="form-kontak" x-show="editing" x-cloak method="POST" action="{{ route('portal.profile.contact') }}" class="space-y-4 pt-1">
                    @csrf
                    <div>
                        <label for="email" class="mb-1.5 block text-xs font-bold text-on-surface">Email <span class="font-normal text-on-surface-variant">(Opsional)</span></label>
                        <div class="relative">
                            <input type="email" id="email" name="email" value="{{ old('email', $user->email) }}"
                                placeholder="nama@email.com" class="input-nch pl-9 text-xs {{ $errors->has('email') ? $errClass : '' }}">
                            <x-heroicon-o-envelope class="absolute left-3 top-2.5 h-4 w-4 text-on-surface-variant" />
                        </div>
                        @error('email')
                            <p class="mt-1 text-xs text-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="phone" class="mb-1.5 block text-xs font-bold text-on-surface">No. HP / WhatsApp <span class="font-normal text-on-surface-variant">(Opsional)</span></label>
                        <div class="relative">
                            <input type="tel" id="phone" name="phone" value="{{ old('phone', $user->phone) }}"
                                placeholder="08xxxxxxxxxx" class="input-nch pl-9 text-xs {{ $errors->has('phone') ? $errClass : '' }}">
                            <x-heroicon-o-phone class="absolute left-3 top-2.5 h-4 w-4 text-on-surface-variant" />
                        </div>
                        <p class="mt-1 text-[11px] text-on-surface-variant">Nomor akan otomatis dinormalkan ke format WhatsApp <code>62xxx</code>.</p>
                        @error('phone')
                            <p class="mt-1 text-xs text-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-outline-variant">
                        <button type="button" @click="editing = false"
                            class="rounded-xl border border-outline-variant bg-surface px-4 py-2 text-xs font-bold text-on-surface-variant transition-colors hover:bg-surface-container-high">
                            Batal
                        </button>
                        <x-portal.confirm-dialog
                            form="form-kontak"
                            icon="heroicon-o-check-circle"
                            title="Simpan Kontak?"
                            message="Informasi email dan nomor HP Anda akan diperbarui."
                            confirm-label="Ya, Simpan"
                            loading-label="Menyimpan..."
                            trigger-class="gap-1.5 rounded-xl bg-primary px-5 py-2 text-xs font-bold text-on-primary shadow-xs transition-colors hover:bg-primary/90">
                            Simpan Kontak
                        </x-portal.confirm-dialog>
                    </div>
                </form>
            </div>

            {{-- CARD 2: KEAMANAN & KATA SANDI --}}
            <div class="rounded-xl border border-outline-variant bg-surface p-5 shadow-xs transition-all"
                 x-data="{ editing: {{ $errors->hasAny(['current_password', 'password']) ? 'true' : 'false' }} }">
                
                <div class="flex items-center justify-between border-b border-outline-variant pb-3 mb-4">
                    <div class="flex items-center gap-2.5">
                        <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-500/10 text-amber-600 dark:text-amber-400">
                            <x-heroicon-s-key class="h-5 w-5" />
                        </div>
                        <div>
                            <h2 class="text-sm font-bold text-on-surface">Keamanan Akun</h2>
                            <p class="text-[11px] text-on-surface-variant">Perbarui kata sandi secara berkala demi keamanan akun Anda.</p>
                        </div>
                    </div>

                    <button type="button" x-show="! editing" @click="editing = true"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-outline-variant bg-surface px-3 py-1.5 text-xs font-bold text-primary hover:bg-surface-container-high transition-colors">
                        <x-heroicon-o-pencil-square class="h-4 w-4" />
                        <span>Ganti Sandi</span>
                    </button>
                </div>

                {{-- READ-ONLY DISPLAY --}}
                <div x-show="! editing" class="flex items-center justify-between rounded-lg border border-outline-variant/60 p-3 bg-surface-container-lowest">
                    <div class="flex items-center gap-2.5">
                        <x-heroicon-o-lock-closed class="h-4 w-4 text-on-surface-variant" />
                        <span class="text-xs font-medium text-on-surface-variant">Kata Sandi Akun:</span>
                    </div>
                    <span class="text-sm font-black tracking-widest text-on-surface-variant">••••••••••••</span>
                </div>

                {{-- EDIT FORM --}}
                <form id="form-sandi" x-show="editing" x-cloak method="POST" action="{{ route('portal.profile.password') }}" class="space-y-4 pt-1">
                    @csrf
                    <div>
                        <label for="current_password" class="mb-1.5 block text-xs font-bold text-on-surface">Sandi Lama</label>
                        <x-portal.password-input id="current_password" name="current_password" autocomplete="current-password"
                            class="input-nch text-xs {{ $errors->has('current_password') ? $errClass : '' }}" />
                        @error('current_password')
                            <p class="mt-1 text-xs text-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="mb-1.5 block text-xs font-bold text-on-surface">Sandi Baru</label>
                        <x-portal.password-input id="password" name="password" autocomplete="new-password"
                            class="input-nch text-xs {{ $errors->has('password') ? $errClass : '' }}" />
                        <p class="mt-1 text-[11px] text-on-surface-variant">Minimal 8 karakter.</p>
                        @error('password')
                            <p class="mt-1 text-xs text-error">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="mb-1.5 block text-xs font-bold text-on-surface">Ulangi Sandi Baru</label>
                        <x-portal.password-input id="password_confirmation" name="password_confirmation" autocomplete="new-password"
                            class="input-nch text-xs" />
                    </div>

                    <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-outline-variant">
                        <button type="button" @click="editing = false"
                            class="rounded-xl border border-outline-variant bg-surface px-4 py-2 text-xs font-bold text-on-surface-variant transition-colors hover:bg-surface-container-high">
                            Batal
                        </button>
                        <x-portal.confirm-dialog
                            form="form-sandi"
                            icon="heroicon-o-key"
                            title="Ganti Kata Sandi?"
                            message="Pastikan kata sandi baru sudah diingat dengan baik."
                            confirm-label="Ya, Perbarui"
                            loading-label="Mengubah..."
                            trigger-class="gap-1.5 rounded-xl bg-primary px-5 py-2 text-xs font-bold text-on-primary shadow-xs transition-colors hover:bg-primary/90">
                            Perbarui Sandi
                        </x-portal.confirm-dialog>
                    </div>
                </form>
            </div>

        </div>

        {{-- ── RIGHT COLUMN (DEMOGRAPHICS & DETAILS: 5 Cols) ───────────── --}}
        <div class="space-y-6 lg:col-span-5">
            
            {{-- CARD 3: DATA KEPENDUDUKAN (READ-ONLY) --}}
            <div class="overflow-hidden rounded-xl border border-outline-variant bg-surface shadow-xs">
                <div class="border-b border-outline-variant bg-surface-bright p-4">
                    <h2 class="text-sm font-bold text-on-surface flex items-center gap-2">
                        <x-heroicon-s-identification class="h-5 w-5 text-primary" />
                        <span>Data Kependudukan</span>
                    </h2>
                    <p class="mt-0.5 text-[11px] text-on-surface-variant">Data kependudukan terdaftar di Nagari.</p>
                </div>

                <div class="divide-y divide-outline-variant">
                    @foreach($identitas as $item)
                        <div class="flex items-center justify-between gap-3 px-4 py-3 hover:bg-surface-container-lowest transition-colors">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <x-dynamic-component :component="$item['icon']" class="h-4 w-4 text-on-surface-variant shrink-0" />
                                <span class="text-xs text-on-surface-variant truncate font-medium">{{ $item['label'] }}</span>
                            </div>
                            <span class="text-xs font-bold text-on-surface text-right truncate">
                                {{ filled($item['value']) ? $item['value'] : '—' }}
                            </span>
                        </div>
                    @endforeach
                </div>

                <div class="border-t border-outline-variant bg-surface-container-low p-4">
                    <p class="flex items-start gap-2 text-[11px] text-on-surface-variant">
                        <x-heroicon-s-lock-closed class="h-4 w-4 shrink-0 text-amber-500 mt-0.5" />
                        <span>Hanya bisa diubah oleh admin nagari. Jika terdapat kekeliruan data, silakan hubungi admin.</span>
                    </p>
                </div>
            </div>

        </div>

    </div>

</div>
@endsection
