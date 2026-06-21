@extends('public.layouts.app')

@section('title', 'Beranda')

@section('content')
    {{-- Hero --}}
    <section class="py-10 text-center sm:py-16">
        <span class="inline-flex items-center rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700">
            Platform Digital Desa
        </span>
        <h1 class="mt-4 text-3xl font-bold tracking-tight sm:text-5xl">Basamo NCH</h1>
        <p class="mx-auto mt-3 max-w-2xl text-base text-gray-500 sm:text-lg">
            Satu platform untuk memajukan desa — belajar digital, memasarkan UMKM,
            mencatat capaian SDGs, dan memantau IoT desa.
        </p>
        <div class="mt-6 flex flex-wrap items-center justify-center gap-3">
            <a href="{{ route('public.umkm.index') }}"
                class="rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-indigo-700">
                Lihat Katalog UMKM
            </a>
            <a href="{{ route('public.peta') }}"
                class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                Peta Desa
            </a>
            <a href="{{ route('portal.login') }}"
                class="rounded-lg border border-gray-300 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                Masuk Portal Warga
            </a>
        </div>
    </section>

    {{-- Statistik ringkas --}}
    <section class="grid grid-cols-3 gap-4 rounded-2xl border border-gray-200 bg-white p-6 text-center">
        <div>
            <p class="text-2xl font-bold text-indigo-600 sm:text-3xl">{{ number_format($stats['desa']) }}</p>
            <p class="mt-1 text-xs text-gray-500 sm:text-sm">Desa aktif</p>
        </div>
        <div class="border-x border-gray-100">
            <p class="text-2xl font-bold text-indigo-600 sm:text-3xl">{{ number_format($stats['produk']) }}</p>
            <p class="mt-1 text-xs text-gray-500 sm:text-sm">Produk UMKM</p>
        </div>
        <div>
            <p class="text-2xl font-bold text-indigo-600 sm:text-3xl">{{ number_format($stats['modul']) }}</p>
            <p class="mt-1 text-xs text-gray-500 sm:text-sm">Modul belajar</p>
        </div>
    </section>

    {{-- 4 Pilar --}}
    <section class="mt-10">
        <h2 class="text-center text-lg font-bold sm:text-xl">Empat Pilar Layanan</h2>
        <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @php
                $pilar = [
                    ['LMS', 'Belajar Digital', 'Modul, kuis, dan leaderboard literasi digital untuk warga.', 'bg-emerald-50 text-emerald-700', true],
                    ['UMKM', 'Katalog UMKM', 'Produk pelaku usaha desa, terhubung langsung via WhatsApp.', 'bg-indigo-50 text-indigo-700', true],
                    ['SDGs', 'Capaian Desa', 'Pencatatan dan visualisasi capaian pembangunan berkelanjutan.', 'bg-amber-50 text-amber-700', false],
                    ['IoT', 'Pemantauan', 'Pemantauan sensor dan infrastruktur desa secara real-time.', 'bg-sky-50 text-sky-700', false],
                ];
            @endphp

            @foreach($pilar as [$tag, $judul, $desc, $warna, $aktif])
                <div class="rounded-xl border border-gray-200 bg-white p-5">
                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $warna }}">{{ $tag }}</span>
                    <h3 class="mt-3 font-semibold">{{ $judul }}</h3>
                    <p class="mt-1 text-sm text-gray-500">{{ $desc }}</p>
                    @unless($aktif)
                        <span class="mt-3 inline-block text-xs font-medium text-gray-400">Segera hadir</span>
                    @endunless
                </div>
            @endforeach
        </div>
    </section>
@endsection
