<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'Portal Warga' }} — Basamo NCH</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full bg-gray-50 text-gray-900 antialiased">

    {{-- Header --}}
    <header class="bg-white border-b border-gray-200 sticky top-0 z-20">
        <div class="max-w-3xl mx-auto px-4 h-14 flex items-center justify-between">
            <a href="{{ route('portal.modules.index') }}" class="font-bold text-indigo-600 text-lg">
                Basamo NCH
            </a>
            <div class="flex items-center gap-4">
                <span class="text-sm text-gray-500 hidden sm:block truncate max-w-40">
                    {{ auth()->user()->name }}
                </span>
                <form method="POST" action="{{ route('portal.logout') }}">
                    @csrf
                    <button type="submit"
                        class="text-sm text-gray-400 hover:text-red-500 transition-colors">
                        Keluar
                    </button>
                </form>
            </div>
        </div>
    </header>

    {{-- Flash messages --}}
    @if(session('error') || session('info') || session('success'))
        <div class="max-w-3xl mx-auto px-4 pt-4">
            @if(session('error'))
                <div class="p-3 bg-red-50 border border-red-200 rounded-lg text-red-700 text-sm">
                    {{ session('error') }}
                </div>
            @endif
            @if(session('info'))
                <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg text-blue-700 text-sm">
                    {{ session('info') }}
                </div>
            @endif
            @if(session('success'))
                <div class="p-3 bg-green-50 border border-green-200 rounded-lg text-green-700 text-sm">
                    {{ session('success') }}
                </div>
            @endif
        </div>
    @endif

    {{-- Main content --}}
    <main class="max-w-3xl mx-auto px-4 py-6">
        @yield('content')
    </main>

    @livewireScripts
</body>
</html>
