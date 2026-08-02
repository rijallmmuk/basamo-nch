{{--
    Modal pemblokir ganti sandi untuk warga yang masih memakai sandi awal bersama.

    Sengaja TIDAK berupa halaman tersendiri: warga perlu melihat lebih dulu bahwa
    login-nya berhasil (dashboard ada di belakang lapisan gelap ini), baru diminta
    mengganti sandi. Pola yang sama dipakai panel, lihat EnsureAdminPasswordChanged
    beserta hook force-password-change.

    Tidak ada tombol tutup dan tidak ada penutup lewat Esc, karena penjaganya bukan
    di sini melainkan di EnsurePortalUser: selama flag wajib-ganti masih menyala,
    halaman portal mana pun memantulkan warga kembali ke beranda ini. Menyembunyikan
    lapisannya lewat devtools tidak membuka apa pun.

    Tanpa JavaScript sama sekali. Galat validasi datang lewat redirect back, jadi
    modal ini ter-render ulang bersama $errors seperti biasa.
--}}
@php
    $inputClass = 'block w-full rounded-xl border-2 px-4 py-3 text-body-md text-on-surface outline-none transition-all placeholder:text-on-surface-muted';
    $inputState = fn (string $field) => $errors->has($field)
        ? 'border-error bg-error-container/30 focus:border-error'
        : 'border-control-border bg-surface-container-low focus:border-secondary focus:bg-surface-container-lowest';
@endphp

<div class="fixed inset-0 z-[100] flex items-center justify-center overflow-y-auto bg-black/50 p-4 backdrop-blur-sm"
    role="dialog" aria-modal="true" aria-labelledby="wajib-ganti-sandi-judul">

    <div class="my-auto w-full max-w-[26rem] rounded-3xl border border-outline-variant/50 bg-surface-container-lowest p-6 shadow-2xl sm:p-8">

        <div class="mb-5 flex items-start gap-3">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-2xl bg-primary/10 text-primary">
                <x-heroicon-s-key class="h-6 w-6" />
            </div>
            <div class="min-w-0">
                <h2 id="wajib-ganti-sandi-judul" class="text-headline-sm font-bold text-on-surface">
                    Buat kata sandi baru
                </h2>
                <p class="mt-1 text-body-md text-on-surface-variant">
                    Anda berhasil masuk. Kata sandi awal Anda dipakai bersama, jadi gantilah dulu dengan kata sandi milik Anda sendiri.
                </p>
            </div>
        </div>

        <form method="POST" action="{{ route('portal.password.update') }}" class="space-y-5">
            @csrf

            <div>
                <label for="wajib-password" class="mb-2 block text-body-md font-semibold text-on-surface">
                    Kata sandi baru
                </label>
                <x-portal.password-input id="wajib-password" name="password"
                    autocomplete="new-password" autofocus
                    class="{{ $inputClass }} {{ $inputState('password') }}"
                    placeholder="Minimal 8 karakter" />
                @error('password')
                    <p class="mt-1.5 text-label-md font-medium text-error">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="wajib-password-confirmation" class="mb-2 block text-body-md font-semibold text-on-surface">
                    Ulangi kata sandi baru
                </label>
                <x-portal.password-input id="wajib-password-confirmation" name="password_confirmation"
                    autocomplete="new-password"
                    class="{{ $inputClass }} border-control-border bg-surface-container-low focus:border-secondary focus:bg-surface-container-lowest"
                    placeholder="••••••••" />
            </div>

            {{-- Label & spinner ditumpuk di sel grid yang sama agar lebar tombol tak
                 berubah saat teks berganti ke "Menyimpan…" (lebih panjang dari labelnya). --}}
            <button type="submit" data-loading
                class="inline-grid w-full items-center justify-center gap-2 rounded-full bg-primary px-4 py-3.5 text-body-md font-bold text-on-primary shadow-lg shadow-primary/20 transition-all hover:-translate-y-0.5 hover:shadow-xl active:scale-[0.99] disabled:cursor-not-allowed disabled:opacity-70 disabled:hover:translate-y-0 disabled:hover:shadow-lg">
                <span data-loading-label class="col-start-1 row-start-1 flex items-center justify-center gap-2">
                    <x-heroicon-s-check-circle class="h-5 w-5" />
                    Simpan kata sandi
                </span>
                <span data-loading-spinner class="invisible col-start-1 row-start-1 flex items-center justify-center gap-2">
                    <svg class="h-5 w-5 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Menyimpan…
                </span>
            </button>
        </form>

        <form method="POST" action="{{ route('portal.logout') }}" class="mt-5 text-center">
            @csrf
            <button type="submit" class="text-label-md text-on-surface-muted transition-colors hover:text-on-surface hover:underline">
                Keluar
            </button>
        </form>
    </div>
</div>
