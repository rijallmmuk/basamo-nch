<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Katalog UMKM') — Basamo NCH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="min-h-full bg-slate-50 text-gray-900 antialiased">
    <header class="border-b border-gray-200 bg-white">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4 sm:px-6">
            <a href="{{ route('public.home') }}" class="text-lg font-bold">Basamo NCH</a>
            <nav class="flex items-center gap-4 text-sm font-semibold">
                <a href="{{ route('public.umkm.index') }}" class="text-gray-600 hover:text-gray-900">Katalog UMKM</a>
                <a href="{{ route('public.peta') }}" class="text-gray-600 hover:text-gray-900">Peta Desa</a>
                <a href="{{ route('portal.login') }}" class="text-indigo-600 hover:text-indigo-700">Masuk Portal</a>
            </nav>
        </div>
    </header>

    <main class="mx-auto w-full max-w-6xl px-4 py-6 sm:px-6">
        @yield('content')
    </main>

    <footer class="mt-12 border-t border-gray-200 py-6 text-center text-xs text-gray-400">
        Basamo NCH — Platform Desa
    </footer>

    @stack('scripts')
</body>
</html>
