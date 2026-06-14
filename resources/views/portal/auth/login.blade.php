<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk — Basamo NCH</title>
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
            <h2 class="mb-5 text-base font-semibold text-gray-800">Masuk ke akun Anda</h2>

            {{-- Flash error --}}
            @if(session('error'))
                <div class="mb-4 flex items-start gap-2.5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.29 4.5-2.599 4.5H4.645c-2.309 0-3.752-2.5-2.598-4.5L9.4 3.003ZM12 8.25a.75.75 0 0 1 .75.75v3.75a.75.75 0 0 1-1.5 0V9a.75.75 0 0 1 .75-.75Zm0 8.25a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Z" clip-rule="evenodd"/></svg>
                    {{ session('error') }}
                </div>
            @endif

            <form method="POST" action="{{ route('portal.login') }}" class="space-y-4">
                @csrf

                {{-- Email --}}
                <div>
                    <label for="email" class="mb-1.5 block text-sm font-medium text-gray-700">
                        Email
                    </label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}"
                        autocomplete="email" autofocus
                        class="block w-full rounded-xl border px-3.5 py-2.5 text-sm text-gray-900 outline-none transition-all placeholder:text-gray-300
                               @error('email') border-red-300 bg-red-50 focus:border-red-400 focus:ring-2 focus:ring-red-100
                               @else border-gray-300 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 @enderror"
                        placeholder="nama@email.com">
                    @error('email')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Password --}}
                <div>
                    <label for="password" class="mb-1.5 block text-sm font-medium text-gray-700">
                        Kata Sandi
                    </label>
                    <input type="password" id="password" name="password"
                        autocomplete="current-password"
                        class="block w-full rounded-xl border px-3.5 py-2.5 text-sm text-gray-900 outline-none transition-all placeholder:text-gray-300
                               @error('password') border-red-300 bg-red-50 focus:border-red-400 focus:ring-2 focus:ring-red-100
                               @else border-gray-300 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 @enderror"
                        placeholder="••••••••">
                    @error('password')
                        <p class="mt-1.5 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Remember --}}
                <div class="flex items-center gap-2">
                    <input type="checkbox" id="remember" name="remember"
                        class="h-4 w-4 rounded border-gray-300 accent-indigo-600">
                    <label for="remember" class="text-sm text-gray-600">Ingat saya</label>
                </div>

                {{-- Submit --}}
                <button type="submit"
                    class="flex w-full items-center justify-center gap-2 rounded-xl bg-indigo-600 px-4 py-3 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-indigo-700 active:scale-[0.99]">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9"/></svg>
                    Masuk
                </button>
            </form>
        </div>

        {{-- Register link --}}
        <p class="mt-5 text-center text-sm text-gray-500">
            Belum punya akun?
            <a href="{{ route('portal.register') }}"
                class="font-semibold text-indigo-600 transition-colors hover:text-indigo-700 hover:underline">
                Daftar sekarang
            </a>
        </p>
    </div>

</body>
</html>
