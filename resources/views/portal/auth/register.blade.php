<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar — Basamo NCH</title>
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
            <p class="mt-1 text-sm text-gray-500">Daftar sebagai Warga</p>
        </div>

        {{-- Card --}}
        <div class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm">
            <h2 class="mb-5 text-base font-semibold text-gray-800">Buat akun baru</h2>

            <form method="POST" action="{{ route('portal.register') }}" class="space-y-4">
                @csrf

                {{-- Nama --}}
                <div>
                    <label for="name" class="mb-1.5 block text-sm font-medium text-gray-700">
                        Nama Lengkap
                    </label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}"
                        autocomplete="name" autofocus
                        class="block w-full rounded-xl border px-3.5 py-2.5 text-sm text-gray-900 outline-none transition-all placeholder:text-gray-300
                               @error('name') border-red-300 bg-red-50 focus:border-red-400 focus:ring-2 focus:ring-red-100
                               @else border-gray-300 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 @enderror"
                        placeholder="Nama sesuai KTP">
                    @error('name')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Email --}}
                <div>
                    <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700">
                        Email
                    </label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}"
                        autocomplete="email"
                        class="block w-full rounded-xl border px-3.5 py-2.5 text-sm text-gray-900 outline-none transition-all placeholder:text-gray-300
                               @error('email') border-red-300 bg-red-50 focus:border-red-400 focus:ring-2 focus:ring-red-100
                               @else border-gray-300 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 @enderror"
                        placeholder="nama@email.com">
                    @error('email')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Nagari --}}
                <div>
                    <label for="nagari_id" class="mb-1.5 block text-sm font-medium text-gray-700">
                        Nagari
                    </label>
                    <select id="nagari_id" name="nagari_id"
                        class="block w-full rounded-xl border px-3.5 py-2.5 text-sm text-gray-900 outline-none transition-all
                               @error('nagari_id') border-red-300 bg-red-50 focus:border-red-400 focus:ring-2 focus:ring-red-100
                               @else border-gray-300 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 @enderror">
                        <option value="">— Pilih Nagari —</option>
                        @foreach($nagaris as $nagari)
                            <option value="{{ $nagari->id }}" {{ old('nagari_id') == $nagari->id ? 'selected' : '' }}>
                                {{ $nagari->nama }}
                            </option>
                        @endforeach
                    </select>
                    @error('nagari_id')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password --}}
                <div>
                    <label for="password" class="mb-1.5 block text-sm font-medium text-gray-700">
                        Kata Sandi
                    </label>
                    <input type="password" id="password" name="password"
                        autocomplete="new-password"
                        class="block w-full rounded-xl border px-3.5 py-2.5 text-sm text-gray-900 outline-none transition-all placeholder:text-gray-300
                               @error('password') border-red-300 bg-red-50 focus:border-red-400 focus:ring-2 focus:ring-red-100
                               @else border-gray-300 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 @enderror"
                        placeholder="••••••••">
                    <p class="mt-1 text-xs text-gray-400">Minimal 8 karakter</p>
                    @error('password')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Confirm password --}}
                <div>
                    <label for="password_confirmation" class="mb-1.5 block text-sm font-medium text-gray-700">
                        Ulangi Kata Sandi
                    </label>
                    <input type="password" id="password_confirmation" name="password_confirmation"
                        autocomplete="new-password"
                        class="block w-full rounded-xl border border-gray-300 px-3.5 py-2.5 text-sm text-gray-900 outline-none transition-all placeholder:text-gray-300 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100"
                        placeholder="••••••••">
                </div>

                {{-- Submit --}}
                <button type="submit"
                    class="flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-indigo-700 active:scale-[0.99]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z"/></svg>
                    Daftar Sekarang
                </button>
            </form>
        </div>

        {{-- Login link --}}
        <p class="mt-5 text-center text-sm text-gray-500">
            Sudah punya akun?
            <a href="{{ route('portal.login') }}"
                class="font-semibold text-indigo-600 transition-colors hover:text-indigo-700 hover:underline">
                Masuk di sini
            </a>
        </p>
    </div>

</body>
</html>
