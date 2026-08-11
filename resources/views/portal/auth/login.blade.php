<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Masuk{{ $situsNagari ? ' · '.$situsNagari->nama_lengkap : '' }} · Basamo NCH</title>
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="icon" href="/favicon.ico" sizes="32x32">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-full bg-surface font-sans text-on-surface antialiased">
    <main class="relative isolate flex min-h-screen items-center justify-center overflow-hidden px-5 py-8 sm:px-8 sm:py-12">
        <div class="gonjong-bg pointer-events-none absolute inset-0 -z-20 opacity-45"></div>
        <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 h-72 bg-gradient-to-b from-primary-fixed/45 to-transparent"></div>
        <div class="pointer-events-none absolute -right-32 -top-32 -z-10 h-96 w-96 rounded-full bg-secondary-container/15 blur-3xl"></div>

        <section class="grid w-full max-w-[68rem] overflow-hidden rounded-[2rem] border border-outline-variant/60 bg-surface-container-lowest shadow-[0_24px_80px_rgba(0,56,87,0.14)] lg:grid-cols-[0.9fr_1.1fr]">
            {{-- Identitas dan konteks akses --}}
            <div class="relative hidden min-h-[42rem] overflow-hidden bg-primary px-10 py-10 text-on-primary lg:flex lg:flex-col">
                <div class="songket-pattern pointer-events-none absolute inset-0 opacity-25"></div>
                <div class="pointer-events-none absolute -bottom-20 -right-16 h-72 w-72 rounded-full border-[48px] border-white/5"></div>
                <div class="pointer-events-none absolute bottom-10 right-10 h-24 w-24 rounded-full bg-secondary-container/10 blur-2xl"></div>

                <a href="{{ url('/') }}" class="public-footer-brand relative z-10 w-fit rounded-xl transition-opacity hover:opacity-85" aria-label="Kembali ke beranda">
                    @include('filament.brand')
                </a>

                <div class="relative z-10 my-auto py-14">
                    <span class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3.5 py-2 text-xs font-bold uppercase tracking-[0.14em] text-primary-fixed">
                        <x-heroicon-s-shield-check class="h-4 w-4" />
                        Akses satu pintu
                    </span>

                    <h1 class="mt-6 max-w-md text-[2.4rem] font-extrabold leading-[1.12] tracking-[-0.04em]">
                        Ruang digital yang aman untuk seluruh ekosistem nagari.
                    </h1>
                    <p class="mt-5 max-w-md text-base leading-7 text-on-primary-container">
                        Satu akun menghubungkan warga dan pengelola dengan pelatihan, layanan nagari, serta ruang kolaborasi Basamo NCH.
                    </p>

                </div>

                <p class="relative z-10 text-xs font-medium tracking-wide text-on-primary-container/75">
                    Basamo Nagari Creative Hub · Smart Learning Center
                </p>
            </div>

            {{-- Form autentikasi --}}
            <div class="flex min-h-[38rem] flex-col px-6 py-7 sm:px-12 sm:py-10 lg:px-16 lg:py-12">
                <div class="flex items-center justify-between gap-4 lg:justify-end">
                    <a href="{{ url('/') }}" class="inline-flex items-center gap-2 text-sm font-bold text-on-surface-variant transition-colors hover:text-primary">
                        <x-heroicon-o-arrow-left class="h-4 w-4" />
                        Beranda
                    </a>
                    <div class="lg:hidden">
                        @include('filament.brand')
                    </div>
                </div>

                <div class="my-auto py-10 sm:py-12">
                    @if ($situsNagari)
                        <div class="mb-6 flex items-center gap-3 rounded-2xl border border-primary-fixed-dim bg-primary-fixed/35 px-4 py-3.5">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary text-on-primary shadow-sm">
                                <x-heroicon-s-map-pin class="h-5 w-5" />
                            </span>
                            <div class="min-w-0">
                                <p class="text-xs font-bold uppercase tracking-[0.12em] text-on-surface-muted">Anda berada di situs</p>
                                <p class="truncate text-sm font-extrabold text-primary">{{ $situsNagari->nama_lengkap }}</p>
                            </div>
                        </div>
                    @endif

                    <p class="text-xs font-extrabold uppercase tracking-[0.16em] text-secondary">{{ $konteks->gerbang?->judul() ?? 'Akses akun' }}</p>
                    <h2 class="mt-2 text-3xl font-extrabold tracking-[-0.035em] text-primary sm:text-4xl">
                        Selamat datang kembali
                    </h2>
                    <p class="mt-3 max-w-lg text-sm leading-6 text-on-surface-variant sm:text-base">
                        {{ $konteks->gerbang?->keterangan()
                            ?? 'Masuk menggunakan akun Basamo NCH Anda.' }}
                    </p>

                    @if (session('error') || $errors->has('login'))
                        <div id="login-error" role="alert" class="mt-6 flex items-start gap-3 rounded-2xl border border-error/20 bg-error-container/55 px-4 py-3.5 text-sm font-medium leading-6 text-on-error-container">
                            <x-heroicon-s-exclamation-triangle class="mt-0.5 h-5 w-5 shrink-0" />
                            <span>{{ session('error') ?: $errors->first('login') }}</span>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login') }}" class="mt-7 space-y-5">
                        @csrf
                        {{-- Gerbang menyeberang lewat input tersembunyi, bukan lewat query
                             string action, supaya percobaan login yang gagal (`back()` tanpa
                             query) tidak diam-diam kehilangan konteksnya. --}}
                        @foreach ($konteks->sebagaiParameter() as $nama => $nilai)
                            <input type="hidden" name="{{ $nama }}" value="{{ $nilai }}">
                        @endforeach

                        <div>
                            <label for="login" class="mb-2 block text-sm font-bold text-on-surface">Username atau NIK</label>
                            <div class="relative">
                                <x-heroicon-o-user class="pointer-events-none absolute left-4 top-1/2 h-5 w-5 -translate-y-1/2 text-on-surface-muted" />
                                <input type="text" id="login" name="login" value="{{ old('login') }}"
                                    autocomplete="username" autofocus inputmode="text" required
                                    @error('login') aria-invalid="true" aria-describedby="login-error" @enderror
                                    class="block min-h-13 w-full rounded-2xl border bg-surface-container-low py-3 pl-12 pr-4 text-base text-on-surface shadow-sm outline-none transition placeholder:text-on-surface-muted invalid:border-control-border hover:border-outline focus:bg-surface-container-lowest focus:ring-4 aria-[invalid=true]:border-error
                                           @error('login') focus:border-error focus:ring-error-container/60
                                           @else border-control-border focus:border-primary focus:ring-primary-fixed/60 @enderror"
                                    placeholder="Masukkan username atau NIK">
                            </div>
                            <p class="mt-2 text-xs leading-5 text-on-surface-muted">Warga menggunakan NIK 16 digit; pengelola menggunakan username.</p>
                        </div>

                        <div>
                            <label for="password" class="mb-2 block text-sm font-bold text-on-surface">Kata sandi</label>
                            <x-portal.password-input id="password" name="password"
                                leading-icon="heroicon-o-lock-closed"
                                autocomplete="current-password" required
                                aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}"
                                class="block min-h-13 w-full rounded-2xl border border-control-border bg-surface-container-low py-3 pl-12 pr-12 text-base text-on-surface shadow-sm outline-none transition placeholder:text-on-surface-muted invalid:border-control-border hover:border-outline focus:border-primary focus:bg-surface-container-lowest focus:ring-4 focus:ring-primary-fixed/60 aria-[invalid=true]:border-error aria-[invalid=true]:focus:border-error aria-[invalid=true]:focus:ring-error-container/60"
                                placeholder="Masukkan kata sandi" />
                            @error('password')
                                <p class="mt-2 text-xs font-semibold text-error">{{ $message }}</p>
                            @enderror
                        </div>

                        <label for="remember" class="flex w-fit cursor-pointer items-center gap-3 text-sm font-semibold text-on-surface-variant">
                            <input type="checkbox" id="remember" name="remember" value="1"
                                class="h-4.5 w-4.5 rounded border-control-border accent-primary">
                            Ingat saya
                        </label>

                        <button type="submit" data-loading
                            class="inline-grid min-h-13 w-full items-center justify-center rounded-2xl bg-primary px-5 py-3.5 text-sm font-extrabold text-on-primary shadow-lg shadow-primary/20 transition hover:-translate-y-0.5 hover:bg-primary-highlight hover:shadow-xl active:translate-y-0 disabled:cursor-not-allowed disabled:opacity-70">
                            <span data-loading-label class="col-start-1 row-start-1 flex items-center justify-center gap-2.5">
                                Masuk ke akun
                                <x-heroicon-s-arrow-right class="h-5 w-5" />
                            </span>
                            <span data-loading-spinner class="invisible col-start-1 row-start-1 flex items-center justify-center gap-2.5">
                                <svg class="h-5 w-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                                Memproses…
                            </span>
                        </button>
                    </form>

                    <div class="mt-6 flex items-start gap-2.5 border-t border-outline-variant/60 pt-5 text-xs leading-5 text-on-surface-muted">
                        <x-heroicon-o-lock-closed class="mt-0.5 h-4 w-4 shrink-0 text-success" />
                        <p>Jangan membagikan kata sandi kepada siapa pun.</p>
                    </div>
                </div>
            </div>
        </section>
    </main>
</body>
</html>
