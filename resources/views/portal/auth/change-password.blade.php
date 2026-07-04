<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ganti Sandi — Basamo NCH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="relative flex min-h-full flex-col items-center justify-center overflow-hidden bg-background px-margin-mobile py-12 antialiased">

    {{-- Latar dekoratif Minangkabau (selaras halaman login) --}}
    <div class="gonjong-bg pointer-events-none absolute inset-0 opacity-60"></div>
    <div class="pointer-events-none absolute -top-24 -right-24 h-72 w-72 rounded-full bg-primary/5 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-24 -left-24 h-72 w-72 rounded-full bg-secondary-container/20 blur-3xl"></div>

    @php
        $inputClass = 'block w-full rounded-xl border-2 px-4 py-3 text-body-md text-on-surface outline-none transition-all placeholder:text-on-surface-variant/50';
        $inputState = fn (string $field) => $errors->has($field)
            ? 'border-error bg-error-container/30 focus:border-error'
            : 'border-transparent bg-surface-container-low focus:border-secondary-container focus:bg-surface-container-lowest';
    @endphp

    <div class="relative z-10 w-full max-w-[24rem]">

        {{-- Brand --}}
        <div class="mb-8 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center bg-primary gonjong-peak shadow-lg">
                <x-heroicon-s-academic-cap class="h-7 w-7 text-secondary-container" />
            </div>
            <h1 class="text-headline-lg font-extrabold tracking-tight text-primary">Basamo <span class="text-secondary">NCH</span></h1>
            <p class="mt-1 text-body-md text-on-surface-variant">Portal Pembelajaran Warga</p>
        </div>

        {{-- Card --}}
        <div class="rounded-3xl border border-outline-variant/50 bg-surface-container-lowest p-8 card-shadow">
            <h2 class="text-headline-sm font-bold text-on-surface">Buat kata sandi baru</h2>
            <p class="mb-6 mt-1 text-body-md text-on-surface-variant">
                Demi keamanan, ganti sandi sementara (OTP) Anda dengan sandi pribadi.
            </p>

            <form method="POST" action="{{ route('portal.password.update') }}" class="space-y-5">
                @csrf

                {{-- Sandi lama — hanya untuk ganti sandi biasa (bukan paksa-ganti login pertama) --}}
                @unless(auth()->user()->must_change_password)
                    <div>
                        <label for="current_password" class="mb-2 block text-body-md font-semibold text-on-surface">
                            Kata Sandi Saat Ini
                        </label>
                        <x-portal.password-input id="current_password" name="current_password"
                            autocomplete="current-password"
                            class="{{ $inputClass }} {{ $inputState('current_password') }}"
                            placeholder="••••••••" />
                        @error('current_password')
                            <p class="mt-1.5 text-label-md font-medium text-error">{{ $message }}</p>
                        @enderror
                    </div>
                @endunless

                {{-- Password baru --}}
                <div>
                    <label for="password" class="mb-2 block text-body-md font-semibold text-on-surface">
                        Kata Sandi Baru
                    </label>
                    <x-portal.password-input id="password" name="password"
                        autocomplete="new-password" autofocus
                        class="{{ $inputClass }} {{ $inputState('password') }}"
                        placeholder="Minimal 8 karakter" />
                    @error('password')
                        <p class="mt-1.5 text-label-md font-medium text-error">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Konfirmasi --}}
                <div>
                    <label for="password_confirmation" class="mb-2 block text-body-md font-semibold text-on-surface">
                        Ulangi Kata Sandi
                    </label>
                    <x-portal.password-input id="password_confirmation" name="password_confirmation"
                        autocomplete="new-password"
                        class="{{ $inputClass }} border-transparent bg-surface-container-low focus:border-secondary-container focus:bg-surface-container-lowest"
                        placeholder="••••••••" />
                </div>

                {{-- Kontak (opsional) — lengkapi sekalian saat login pertama --}}
                <div class="border-t border-outline-variant pt-5">
                    <p class="mb-3 text-label-md font-medium uppercase tracking-wide text-on-surface-variant">Kontak (opsional)</p>

                    <div class="space-y-4">
                        <div>
                            <label for="phone" class="mb-2 block text-body-md font-semibold text-on-surface">No. HP</label>
                            <input type="tel" id="phone" name="phone"
                                value="{{ old('phone', auth()->user()->phone) }}"
                                class="{{ $inputClass }} {{ $inputState('phone') }}"
                                placeholder="0812xxxxxxxx">
                            @error('phone')
                                <p class="mt-1.5 text-label-md font-medium text-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="email" class="mb-2 block text-body-md font-semibold text-on-surface">Email</label>
                            <input type="email" id="email" name="email"
                                value="{{ old('email', auth()->user()->email) }}"
                                class="{{ $inputClass }} {{ $inputState('email') }}"
                                placeholder="nama@contoh.com">
                            @error('email')
                                <p class="mt-1.5 text-label-md font-medium text-error">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <button type="submit" data-loading
                    class="flex w-full items-center justify-center gap-2 rounded-full bg-primary px-4 py-3.5 text-body-md font-bold text-on-primary shadow-lg shadow-primary/20 transition-all hover:-translate-y-0.5 hover:shadow-xl active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-70 disabled:hover:translate-y-0 disabled:hover:shadow-lg">
                    <span data-loading-label class="flex items-center gap-2">
                        <x-heroicon-s-check-circle class="h-5 w-5" />
                        Simpan Sandi Baru
                    </span>
                    <span data-loading-spinner class="hidden items-center gap-2">
                        <svg class="h-5 w-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Menyimpan…
                    </span>
                </button>
            </form>
        </div>

        {{-- Logout --}}
        <form method="POST" action="{{ route('portal.logout') }}" class="mt-6 text-center">
            @csrf
            <button type="submit" class="text-label-md text-on-surface-variant/70 transition-colors hover:text-on-surface-variant hover:underline">
                Keluar
            </button>
        </form>
    </div>

</body>
</html>
