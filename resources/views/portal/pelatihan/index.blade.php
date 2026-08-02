@extends('portal.layouts.app')

@section('title', 'Katalog Pelatihan SLC')

@section('content')
    <div class="mb-6">
        <div>
            <div class="inline-flex items-center gap-2 rounded-full bg-primary/10 px-3 py-1 text-xs font-bold text-primary mb-2">
                <x-heroicon-s-academic-cap class="h-4 w-4" />
                <span>Pelatihan Nagari</span>
            </div>
            <h1 class="text-xl font-extrabold text-on-surface sm:text-2xl lg:text-3xl">Katalog Pelatihan SLC</h1>
            <p class="mt-1 text-sm text-on-surface-variant">Pilih pelatihan literasi dan keterampilan digital yang tersedia untuk nagari Anda.</p>
        </div>
    </div>

    @if($pelatihans->isEmpty())
        <x-portal.empty
            icon="heroicon-o-academic-cap"
            title="Belum Ada Pelatihan Tersedia"
            subtitle="Pelatihan akan muncul setelah pengelola menambahkan modul yang berisi materi." />
    @else
        {{-- 4-Column Grid Layout --}}
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach($pelatihans as $program)
                <x-slc.pelatihan-card
                    :program="$program"
                    :href="route('portal.pelatihan.show', $program)"
                    cta="Lihat Modul" />
            @endforeach
        </div>

        <div class="mt-8">{{ $pelatihans->links() }}</div>
    @endif
@endsection
