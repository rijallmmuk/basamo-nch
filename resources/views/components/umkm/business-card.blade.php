{{-- Kartu lapau usaha untuk direktori publik. Informasi ringkas saja; profil dan
     produk lengkap tetap berada di etalase. --}}
@props(['profile', 'nagari', 'showNagari' => false, 'global' => false])

@php
    $etalaseUrl = $global
        ? route('public.umkm.etalase', $profile)
        : (request()->routeIs('*.fallback')
            ? route('public.nagari.umkm.etalase.fallback', [$nagari, $profile])
            : route('public.nagari.umkm.etalase', [$nagari, $profile]));

    $jumlahProduk = (int) ($profile->produk_count ?? 0);
    // Lokasi selalu memiliki konteks nagari yang valid. Alamat rinci hanya
    // menggantikannya bila tersedia, sehingga isian opsional tidak membuat
    // tinggi kartu berbeda atau memunculkan teks placeholder karangan.
    $lokasi = filled($profile->alamat) ? $profile->alamat : $nagari->nama_lengkap;
@endphp

<a href="{{ $etalaseUrl }}"
   class="group flex h-full flex-col overflow-hidden rounded-2xl border border-outline-variant bg-surface-container-lowest shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-primary/35 hover:shadow-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2">

    {{-- Logo menjadi visual utama 1:1 selebar kartu, seperti foto pada kartu
         produk e-commerce. Informasi usaha seluruhnya berada di bawahnya. --}}
    <div class="aspect-square w-full overflow-hidden border-b border-outline-variant/70 bg-white">
        <img src="{{ $profile->logoUrl() }}" alt="Logo {{ $profile->nama_usaha }}" loading="lazy"
             class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-[1.02]">
    </div>

    <div class="flex flex-1 flex-col p-4">
        <h3 class="line-clamp-2 min-h-12 text-base font-extrabold leading-6 text-on-surface transition-colors group-hover:text-primary">
            {{ $profile->nama_usaha }}
        </h3>

        <div class="mt-2 min-h-10 text-xs text-on-surface-variant">
            <p class="flex min-w-0 items-start gap-1.5">
                <x-heroicon-o-map-pin class="mt-0.5 h-4 w-4 shrink-0" />
                <span class="line-clamp-2 leading-5">{{ $lokasi }}</span>
            </p>
            @if($showNagari && filled($profile->alamat))
                <p class="mt-0.5 truncate pl-5 text-on-surface-muted">{{ $nagari->nama_lengkap }}</p>
            @endif
        </div>

        <div class="mt-auto flex items-center justify-between gap-3 border-t border-outline-variant/70 pt-4">
            <span class="flex items-center gap-1.5 text-xs font-semibold text-on-surface-variant">
                <x-heroicon-o-shopping-bag class="h-4 w-4 text-primary" />
                {{ number_format($jumlahProduk, 0, ',', '.') }} Produk
            </span>
            <span class="inline-flex items-center gap-1 text-xs font-bold text-primary">
                Lihat etalase <x-heroicon-o-arrow-right class="h-4 w-4 transition-transform group-hover:translate-x-0.5" />
            </span>
        </div>
    </div>
</a>
