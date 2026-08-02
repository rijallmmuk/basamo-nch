{{--
    Kartu usaha UMKM untuk direktori nagari (entri utama publik, bukan produk).
    Menaut ke halaman etalase usaha. Pemanggil eager-load 'media' & withCount produk.

    Bentuknya SATU keluarga dengan kartu pelatihan dan kartu produk: sudut rounded-2xl,
    garis outline-variant, bayangan tipis yang menguat saat disorot, sampul 3:1,
    badge di atas sampul, dan kaki kartu berisi angka plus ajakan.

    Isian opsional (sampul, logo, deskripsi) TIDAK boleh menggeser tinggi kartu.
    Tiap bagian yang bisa kosong diberi tinggi minimum, sehingga satu baris kartu
    tetap rata walau isinya berbeda-beda.
--}}
@props(['profile', 'nagari', 'showNagari' => false, 'global' => false])

@php
    $etalaseUrl = $global
        ? route('public.umkm.etalase', $profile)
        : (request()->routeIs('*.fallback')
            ? route('public.nagari.umkm.etalase.fallback', [$nagari, $profile])
            : route('public.nagari.umkm.etalase', [$nagari, $profile]));

    $jumlahProduk = (int) ($profile->produk_count ?? 0);
@endphp

<a href="{{ $etalaseUrl }}"
   class="group flex h-full cursor-pointer flex-col justify-between overflow-hidden rounded-2xl border border-outline-variant bg-surface-container-lowest shadow-sm transition-all hover:border-primary/40 hover:shadow-md">

    <div>
        {{-- Sampul: rasio dikunci supaya usaha yang belum mengunggah foto tetap
             sejajar dengan yang sudah. --}}
        <div class="relative aspect-[3/1] w-full shrink-0 overflow-hidden bg-gradient-to-br from-primary via-primary-container to-primary-highlight">
            @if($sampul = $profile->sampulUrl())
                <img src="{{ $sampul }}" alt="" loading="lazy"
                     class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105">
            @else
                <div class="songket-pattern absolute inset-0 opacity-20" aria-hidden="true"></div>
            @endif
            <div class="absolute inset-0 bg-gradient-to-t from-black/75 via-black/20 to-transparent"></div>

            @if($showNagari)
                <span class="absolute left-2.5 top-2.5 max-w-[75%] truncate rounded-full bg-white/95 px-2.5 py-0.5 text-[10px] font-extrabold text-[#003857] shadow-xs backdrop-blur-md">
                    {{ $nagari->nama_lengkap }}
                </span>
            @endif
        </div>

        <div class="space-y-2 p-4">
            <div class="flex items-start gap-2.5">
                @if($logo = $profile->logoUrl())
                    <img src="{{ $logo }}" alt="Logo {{ $profile->nama_usaha }}" loading="lazy"
                         class="h-10 w-10 shrink-0 rounded-xl border border-outline-variant object-cover">
                @else
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-outline-variant bg-secondary-container text-on-secondary-container">
                        <x-umkm.icon-toko class="h-5 w-5" />
                    </span>
                @endif

                <h3 class="line-clamp-2 min-h-[2.5rem] text-sm font-extrabold leading-snug text-on-surface transition-colors group-hover:text-primary">
                    {{ $profile->nama_usaha }}
                </h3>
            </div>

            {{-- Deskripsi opsional. Kosong = ruangnya tetap, tanpa kalimat karangan
                 yang seolah ditulis pemilik usahanya. --}}
            <div class="min-h-[4.5rem] rounded-xl border border-outline-variant bg-surface-container-low p-2.5">
                <div class="border-b border-outline-variant/60 pb-1 text-[10px] font-bold uppercase tracking-wider text-on-surface-variant">
                    Tentang Usaha
                </div>
                <p class="mt-1.5 line-clamp-3 text-xs leading-relaxed text-on-surface-variant">
                    {{ $profile->deskripsi ?: 'Belum ada deskripsi usaha.' }}
                </p>
            </div>
        </div>
    </div>

    <div class="p-4 pt-0">
        <div class="flex items-center justify-between gap-2 border-t border-outline-variant pt-2.5">
            <span class="flex shrink-0 items-center gap-1 text-[11px] font-bold text-on-surface-variant">
                <x-heroicon-s-shopping-bag class="h-3.5 w-3.5 text-primary" />
                <span>{{ number_format($jumlahProduk, 0, ',', '.') }} Produk</span>
            </span>

            <span class="inline-flex shrink-0 items-center justify-center gap-1 rounded-lg bg-primary/10 px-2.5 py-1.5 text-xs font-extrabold text-primary transition-all group-hover:bg-primary group-hover:text-on-primary">
                <span>Lihat Etalase</span>
                <x-heroicon-s-arrow-right class="h-3.5 w-3.5" />
            </span>
        </div>
    </div>
</a>
