@extends('public.layouts.app')

@section('title', 'Verifikasi Sertifikat')
@section('meta_description', 'Periksa keaslian sertifikat pelatihan Basamo Nagari Creative Hub berdasarkan nomor serinya.')
@section('robots', 'noindex, follow')

@section('content')
<section class="bg-background py-section-gap">
    <div class="mx-auto max-w-3xl px-margin-mobile lg:px-margin-page">
        <p class="text-xs font-black uppercase tracking-[0.16em] text-on-surface-variant">Verifikasi Sertifikat</p>

        @if($certificate)
            <h1 class="mt-2 text-2xl font-black text-on-surface sm:text-3xl">Sertifikat ini sah.</h1>

            <div class="mt-8 overflow-hidden rounded-2xl border border-outline-variant bg-surface-container-lowest shadow-sm">
                <div class="flex items-center gap-3 border-b border-outline-variant bg-success/10 px-6 py-4">
                    <x-heroicon-s-check-badge class="h-6 w-6 shrink-0 text-success" />
                    <p class="text-sm font-bold text-on-surface">Terdaftar pada Basamo Nagari Creative Hub</p>
                </div>

                <dl class="divide-y divide-outline-variant">
                    @foreach([
                        ['Nomor seri', $certificate->nomor_seri],
                        ['Nama penerima', $certificate->user->name],
                        ['Pelatihan', $certificate->pelatihan->temaNama()],
                        ['Nagari', $certificate->user->nagari?->nama_lengkap ?? 'Tidak tercatat'],
                        ['Tanggal terbit', $certificate->diterbitkan_pada->translatedFormat('j F Y')],
                    ] as [$label, $nilai])
                        <div class="flex flex-col gap-1 px-6 py-4 sm:flex-row sm:items-baseline sm:gap-6">
                            <dt class="w-44 shrink-0 text-xs font-bold uppercase tracking-wider text-on-surface-variant">{{ $label }}</dt>
                            <dd class="min-w-0 text-sm font-semibold text-on-surface">{{ $nilai }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        @else
            <h1 class="mt-2 text-2xl font-black text-on-surface sm:text-3xl">Nomor ini tidak ditemukan.</h1>

            <div class="mt-8 rounded-2xl border border-outline-variant bg-surface-container-lowest p-6 shadow-sm">
                <div class="flex items-start gap-3">
                    <x-heroicon-o-exclamation-triangle class="h-6 w-6 shrink-0 text-error" />
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-on-surface">
                            Tidak ada sertifikat bernomor
                            <span class="break-all font-mono">{{ $nomor }}</span>
                            dalam catatan kami.
                        </p>
                        <p class="mt-2 text-sm text-on-surface-variant">
                            Periksa kembali penulisan nomornya. Huruf I, L, O, dan U tidak pernah dipakai,
                            jadi karakter yang mirip biasanya angka 1 atau 0.
                        </p>
                    </div>
                </div>
            </div>
        @endif

        <p class="mt-6 text-xs text-on-surface-variant">
            Halaman ini hanya menyatakan bahwa nomor tersebut terdaftar beserta pemiliknya.
        </p>
    </div>
</section>
@endsection
