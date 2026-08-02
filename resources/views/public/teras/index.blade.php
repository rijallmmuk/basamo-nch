@extends('public.layouts.app')

@section('title', 'Teras Nagari')
@section('meta_description', 'Dashboard data agregat seluruh nagari mitra BASAMO NCH dengan filter per nagari.')
@section('main-class', 'w-full')

@section('content')
<x-public.pillar-header
    eyebrow="Pilar 1 · Teras Nagari"
    title="Data lintas nagari dalam satu teras."
    description="Lihat gambaran seluruh ekosistem atau pilih satu nagari untuk membuka SDGs, IDM, demografi, cuaca, dan data peringatan dininya secara lengkap.">
    <x-slot:aside>
        <div class="inline-flex w-fit items-center gap-2 rounded-full border border-on-primary/20 bg-on-primary/10 px-4 py-2 text-xs font-bold">
            <x-heroicon-s-shield-check class="h-4 w-4" /> Data agregat non-pribadi
        </div>
    </x-slot:aside>
</x-public.pillar-header>

<section class="border-b border-outline-variant bg-surface-container-lowest py-6">
    <div class="mx-auto max-w-container-page px-margin-mobile lg:px-margin-page">
        <form method="GET" action="{{ route('public.teras') }}" class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <label class="min-w-0 flex-1">
                <span class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-on-surface-variant">Tampilkan data nagari</span>
                <select name="nagari" class="select-nch w-full rounded-full">
                    <option value="">Semua nagari</option>
                    @foreach($nagariOptions as $option)
                        <option value="{{ $option->id }}">{{ $option->nama }}{{ $option->kabupaten ? ' · '.$option->kabupaten : '' }}</option>
                    @endforeach
                </select>
            </label>
            <button class="mt-0 rounded-full bg-primary px-6 py-3 text-sm font-extrabold text-on-primary sm:mt-5">Terapkan</button>
        </form>
    </div>
</section>

<x-public.teras-overview :overview="$overview" global />

<section class="border-t border-outline-variant bg-surface-container-lowest py-section-gap">
    <div class="mx-auto max-w-container-page px-margin-mobile lg:px-margin-page">
        <x-public.section-heading
            eyebrow="Perbandingan Nagari"
            title="Kinerja nagari mitra dalam satu tampilan."
            description="Pilih nama nagari untuk membuka seluruh rincian datanya pada Teras."
        />

        @if($performa->isEmpty())
            <x-public.empty-state class="mt-8" icon="heroicon-o-map-pin" title="Belum ada nagari mitra aktif" description="Daftar terisi setelah nagari pertama aktif di dalam sistem." />
        @else
            <div class="mt-8 overflow-x-auto rounded-3xl border border-outline-variant bg-background shadow-sm">
                <table class="w-full min-w-[52rem] text-sm">
                    <thead class="border-b border-outline-variant bg-surface-container-low text-left text-xs uppercase tracking-wider text-on-surface-variant">
                        <tr>
                            <th class="px-5 py-3">Nagari</th><th class="px-5 py-3">Penduduk</th><th class="px-5 py-3">Akun Portal</th><th class="px-5 py-3">UMKM</th><th class="px-5 py-3">Produk</th><th class="px-5 py-3">Modul Selesai</th><th class="px-5 py-3">SDGs</th><th class="px-5 py-3">IDM</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-outline-variant">
                        @foreach($performa as $baris)
                            <tr class="hover:bg-surface-container-low">
                                <td class="px-5 py-3"><a href="{{ route('public.teras', ['nagari' => $baris->id]) }}" class="font-extrabold text-primary hover:underline">{{ $baris->nama_lengkap }}</a><span class="block text-xs text-on-surface-variant">{{ $baris->kabupaten }}</span></td>
                                <td class="px-5 py-3 tabular-nums">{{ number_format((int) $baris->total_penduduk, 0, ',', '.') }}</td>
                                <td class="px-5 py-3 tabular-nums">{{ number_format((int) $baris->total_warga, 0, ',', '.') }}</td>
                                <td class="px-5 py-3 tabular-nums">{{ number_format((int) $baris->total_umkm, 0, ',', '.') }}</td>
                                <td class="px-5 py-3 tabular-nums">{{ number_format((int) $baris->total_produk, 0, ',', '.') }}</td>
                                <td class="px-5 py-3 tabular-nums">{{ number_format((int) $baris->modul_selesai, 0, ',', '.') }}</td>
                                <td class="px-5 py-3 tabular-nums">{{ $baris->skor_sdgs !== null ? number_format((float) $baris->skor_sdgs, 1, ',', '.').'%' : '—' }}</td>
                                <td class="px-5 py-3">{{ $baris->status_idm ? (\App\Enums\StatusIdm::tryFrom((string) $baris->status_idm)?->label() ?? $baris->status_idm) : '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>
@endsection
