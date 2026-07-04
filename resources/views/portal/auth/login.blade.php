<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — Basamo NCH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="relative flex min-h-full flex-col items-center justify-center overflow-hidden bg-background px-margin-mobile py-12 antialiased">

    {{-- Latar dekoratif Minangkabau --}}
    <div class="gonjong-bg pointer-events-none absolute inset-0 opacity-60"></div>
    <div class="pointer-events-none absolute -top-24 -right-24 h-72 w-72 rounded-full bg-primary/5 blur-3xl"></div>
    <div class="pointer-events-none absolute -bottom-24 -left-24 h-72 w-72 rounded-full bg-secondary-container/20 blur-3xl"></div>

    <div class="relative z-10 w-full max-w-[24rem]">

        {{-- Brand --}}
        <div class="mb-8 text-center">
            <div class="mx-auto mb-4 flex h-14 w-14 items-center justify-center bg-primary gonjong-peak shadow-lg">
                <x-heroicon-s-academic-cap class="h-7 w-7 text-secondary-container" />
            </div>
            <h1 class="text-headline-lg font-extrabold tracking-tight text-primary">Basamo <span class="text-secondary">NCH</span></h1>
            <p class="mt-1 text-body-md text-on-surface-variant">Pembelajaran Digital Nagari</p>
        </div>

        {{-- Card --}}
        <div class="rounded-3xl border border-outline-variant/50 bg-surface-container-lowest p-8 card-shadow">
            <h2 class="mb-6 text-headline-sm font-bold text-on-surface">Masuk ke akun Anda</h2>

            {{-- Flash error --}}
            @if(session('error'))
                <div class="mb-5 flex items-start gap-2.5 rounded-xl border border-error-container bg-error-container/40 px-4 py-3 text-body-md text-on-error-container">
                    <x-heroicon-s-exclamation-triangle class="mt-0.5 h-4 w-4 shrink-0" />
                    {{ session('error') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                {{-- NIK (warga) / Username (admin) --}}
                <div>
                    <label for="login" class="mb-2 block text-body-md font-semibold text-on-surface">
                        NIK atau Username
                    </label>
                    <input type="text" id="login" name="login" value="{{ old('login') }}"
                        autocomplete="username" autofocus inputmode="text"
                        class="block w-full rounded-xl border-2 px-4 py-3 text-body-md text-on-surface outline-none transition-all placeholder:text-on-surface-variant/50
                               @error('login') border-error bg-error-container/30 focus:border-error
                               @else border-transparent bg-surface-container-low focus:border-secondary-container focus:bg-surface-container-lowest @enderror"
                        placeholder="NIK atau username">
                    @error('login')
                        <p class="mt-1.5 text-label-md font-medium text-error">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password --}}
                <div>
                    <label for="password" class="mb-2 block text-body-md font-semibold text-on-surface">
                        Kata Sandi
                    </label>
                    <x-portal.password-input id="password" name="password"
                        autocomplete="current-password"
                        class="block w-full rounded-xl border-2 px-4 py-3 text-body-md text-on-surface outline-none transition-all placeholder:text-on-surface-variant/50
                               @error('password') border-error bg-error-container/30 focus:border-error
                               @else border-transparent bg-surface-container-low focus:border-secondary-container focus:bg-surface-container-lowest @enderror"
                        placeholder="••••••••" />
                    @error('password')
                        <p class="mt-1.5 text-label-md font-medium text-error">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Remember --}}
                <div class="flex items-center gap-2">
                    <input type="checkbox" id="remember" name="remember"
                        class="h-4 w-4 rounded border-outline-variant accent-primary">
                    <label for="remember" class="text-body-md text-on-surface-variant">Ingat saya</label>
                </div>

                {{-- Submit (dengan state loading agar tak terkesan macet / cegah klik ganda) --}}
                <button type="submit" data-loading
                    class="flex w-full items-center justify-center gap-2 rounded-full bg-primary px-4 py-3.5 text-body-md font-bold text-on-primary shadow-lg shadow-primary/20 transition-all hover:-translate-y-0.5 hover:shadow-xl active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-70 disabled:hover:translate-y-0 disabled:hover:shadow-lg">
                    <span data-loading-label class="flex items-center gap-2">
                        <x-heroicon-s-arrow-right-end-on-rectangle class="h-5 w-5" />
                        Masuk
                    </span>
                    <span data-loading-spinner class="hidden items-center gap-2">
                        <svg class="h-5 w-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                        </svg>
                        Memproses…
                    </span>
                </button>
            </form>
        </div>

        {{-- Akun warga dibuat oleh Admin Desa --}}
        <p class="mt-6 text-center text-label-md text-on-surface-variant/70">
            Akun dibuat oleh Admin Desa. Hubungi admin desa Anda untuk mendapatkan NIK &amp; kode OTP.
        </p>
    </div>

</body>
</html>
