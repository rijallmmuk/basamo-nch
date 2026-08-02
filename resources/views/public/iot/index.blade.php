@extends('public.layouts.app')

@section('title', 'Pemantauan IoT & EWS Nagari')
@section('meta_description', 'Pemantauan perangkat IoT dan sistem peringatan dini seluruh nagari mitra BASAMO NCH.')
@section('main-class', 'w-full')

@section('content')
<x-public.pillar-header
    eyebrow="IoT · Pemantauan Lintas Nagari"
    title="Satu halaman untuk seluruh titik pantau."
    description="Pembacaan perangkat EWS ditampilkan bersama status sambungan dan waktu rekamnya. Data berasal dari rekaman sensor nagari, bukan angka simulasi.">
    <x-slot:aside>
        <div class="rounded-2xl border border-on-primary/15 bg-on-primary/8 p-5 backdrop-blur-sm">
            <div class="flex items-start gap-3">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-secondary-container text-on-secondary-container"><x-heroicon-o-cpu-chip class="h-5 w-5" /></span>
                <div><p class="font-extrabold">{{ number_format($devices->total(), 0, ',', '.') }} titik pantau</p><p class="mt-1 text-sm text-on-primary/65">Hanya perangkat aktif dari nagari aktif.</p></div>
            </div>
        </div>
    </x-slot:aside>
</x-public.pillar-header>

<section class="border-b border-outline-variant bg-surface-container-lowest py-6">
    <div class="mx-auto max-w-container-page px-margin-mobile lg:px-margin-page">
        <form method="GET" action="{{ route('public.iot') }}" class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <label class="min-w-0 flex-1">
                <span class="mb-1.5 block text-xs font-bold uppercase tracking-wider text-on-surface-variant">Filter titik pantau</span>
                <select name="nagari" class="select-nch w-full rounded-full">
                    <option value="">Semua nagari</option>
                    @foreach($nagariOptions as $option)
                        <option value="{{ $option->id }}" @selected($selectedNagariId === $option->id)>{{ $option->nama }}{{ $option->kabupaten ? ' · '.$option->kabupaten : '' }}</option>
                    @endforeach
                </select>
            </label>
            <button class="mt-0 rounded-full bg-primary px-6 py-3 text-sm font-extrabold text-on-primary sm:mt-5">Terapkan</button>
            @if($selectedNagariId)
                <a href="{{ route('public.iot') }}" class="mt-0 inline-flex h-11 w-11 items-center justify-center rounded-full border border-outline-variant text-primary sm:mt-5" aria-label="Reset filter"><x-heroicon-o-arrow-path class="h-5 w-5" /></a>
            @endif
        </form>
    </div>
</section>

<section class="bg-background py-section-gap">
    <div class="mx-auto max-w-container-page px-margin-mobile lg:px-margin-page">
        <x-public.section-heading
            eyebrow="Data Perangkat"
            :title="$devices->isEmpty() ? 'Belum ada perangkat yang dapat ditampilkan.' : number_format($devices->total(), 0, ',', '.').' titik pantau aktif.'"
            description="Ringkasan selalu terbuka. Buka rincian untuk melihat keempat sensor dan tren 24 jam pada satu titik."
        />

        @if($devices->isEmpty())
            <x-public.empty-state class="mt-8" icon="heroicon-o-signal-slash" title="Belum ada titik pantau aktif" description="Perangkat tampil setelah didaftarkan pada nagari aktif dan diaktifkan oleh pengelola." />
        @else
            <div class="mt-8 space-y-5">
                @foreach($devices as $device)
                    @php
                        $panel = $panels[$device->getKey()];
                        $reading = $panel['pembacaan'];
                        $tepercaya = $panel['terhubung'] && ! $panel['basi'];
                    @endphp
                    <details class="group overflow-hidden rounded-3xl border border-outline-variant bg-surface-container-lowest shadow-sm">
                        <summary class="flex cursor-pointer list-none flex-wrap items-center gap-4 p-6 [&::-webkit-details-marker]:hidden">
                            <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-primary text-on-primary"><x-heroicon-o-cpu-chip class="h-6 w-6" /></span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-xs font-bold uppercase tracking-wider text-on-surface-variant">{{ $device->nagari?->nama_lengkap }}</span>
                                <span class="mt-1 block text-xl font-black text-primary">{{ $device->namaTampil() }}</span>
                            </span>
                            <span class="text-right">
                                <span class="block text-xl font-black {{ $tepercaya ? $panel['status']->kelasWarna() : 'text-on-surface-variant' }}">{{ $panel['status']->getLabel() }}</span>
                                <span class="mt-1 block text-xs text-on-surface-variant">{{ $reading?->direkam_pada?->locale('id')?->diffForHumans() ?? 'Belum ada pembacaan' }}</span>
                            </span>
                            <span class="inline-flex items-center gap-2 rounded-full border border-outline-variant px-3 py-2 text-xs font-bold"><span class="h-2 w-2 rounded-full {{ $tepercaya ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>{{ $tepercaya ? 'Data terkini' : 'Periksa data' }}</span>
                            <x-heroicon-o-chevron-down class="h-5 w-5 text-on-surface-variant transition-transform group-open:rotate-180" />
                        </summary>
                        <div class="border-t border-outline-variant bg-background p-6 sm:p-8">
                            <x-public.ews-panel :panel="$panel" :panel-id="'ews-panel-'.$device->getKey()" />
                        </div>
                    </details>
                @endforeach
            </div>
            <div class="mt-10">{{ $devices->onEachSide(1)->links('public.pagination', ['label' => 'titik pantau']) }}</div>
        @endif
    </div>
</section>
@endsection
