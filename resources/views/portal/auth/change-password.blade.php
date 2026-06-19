<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ganti Sandi — Basamo NCH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-full flex-col items-center justify-center bg-slate-50 px-4 py-12 antialiased">

    <div class="w-full max-w-sm">

        {{-- Brand --}}
        <div class="mb-8 text-center">
            <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-2xl bg-indigo-600 shadow-lg">
                <span class="text-xl font-bold text-white">B</span>
            </div>
            <h1 class="text-2xl font-bold text-gray-900">Basamo NCH</h1>
            <p class="mt-1 text-sm text-gray-500">Portal Pembelajaran Warga</p>
        </div>

        {{-- Card --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="text-base font-semibold text-gray-800">Buat kata sandi baru</h2>
            <p class="mb-5 mt-1 text-sm text-gray-500">
                Demi keamanan, ganti sandi sementara (OTP) Anda dengan sandi pribadi.
            </p>

            <form method="POST" action="{{ route('portal.password.update') }}" class="space-y-4">
                @csrf

                {{-- Password baru --}}
                <div>
                    <label for="password" class="mb-1.5 block text-sm font-medium text-gray-700">
                        Kata Sandi Baru
                    </label>
                    <input type="password" id="password" name="password"
                        autocomplete="new-password" autofocus
                        class="block w-full rounded-xl border px-3.5 py-2.5 text-sm text-gray-900 outline-none transition-all placeholder:text-gray-300
                               @error('password') border-red-300 bg-red-50 focus:border-red-400 focus:ring-2 focus:ring-red-100
                               @else border-gray-300 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 @enderror"
                        placeholder="Minimal 8 karakter">
                    @error('password')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Konfirmasi --}}
                <div>
                    <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-gray-700">
                        Ulangi Kata Sandi
                    </label>
                    <input type="password" id="password_confirmation" name="password_confirmation"
                        autocomplete="new-password"
                        class="block w-full rounded-xl border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 outline-none transition-all placeholder:text-gray-300 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100"
                        placeholder="••••••••">
                </div>

                <button type="submit"
                    class="flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-indigo-700 active:scale-[0.99]">
                    Simpan Sandi Baru
                </button>
            </form>
        </div>

        {{-- Logout --}}
        <form method="POST" action="{{ route('portal.logout') }}" class="mt-5 text-center">
            @csrf
            <button type="submit" class="text-xs text-gray-400 transition-colors hover:text-gray-600 hover:underline">
                Keluar
            </button>
        </form>
    </div>

</body>
</html>
