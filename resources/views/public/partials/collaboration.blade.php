{{-- Logo resmi lembaga pemrakarsa dan pendukung BASAMO NCH. --}}
<section class="border-t border-outline-variant bg-surface-container-lowest py-16">
    <div class="mx-auto max-w-container-page px-margin-mobile lg:px-margin-page">
        <x-public.section-heading
            class="mx-auto mb-10"
            align="center"
            eyebrow="Kolaborasi"
            title="Diprakarsai dan didukung oleh."
            description="Kolaborasi pemerintah daerah dan perguruan tinggi untuk penguatan ekosistem digital nagari."
        />
        @php
            $prakarsa = [
                ['sumbar.webp', 'Pemerintah Provinsi Sumatera Barat'],
                ['unp.webp', 'Universitas Negeri Padang'],
                ['unj.webp', 'Universitas Negeri Jakarta'],
                ['ut.webp', 'Universitas Terbuka'],
                ['uny.webp', 'Universitas Negeri Yogyakarta'],
            ];
        @endphp
        <div class="grid grid-cols-2 items-start gap-6 sm:grid-cols-3 lg:grid-cols-5" role="list" aria-label="Lembaga pemrakarsa dan pendukung">
            @foreach($prakarsa as [$berkas, $nama])
                <figure class="flex h-full flex-col items-center justify-center rounded-2xl border border-outline-variant bg-background p-5 text-center shadow-sm" role="listitem">
                    <img src="{{ asset('img/prakarsa/'.$berkas) }}" alt="Logo {{ $nama }}" class="h-16 w-auto object-contain" height="64" loading="lazy" decoding="async">
                    <figcaption class="mt-4 text-xs font-semibold leading-snug text-on-surface-variant">{{ $nama }}</figcaption>
                </figure>
            @endforeach
        </div>
    </div>
</section>
