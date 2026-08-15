@props([
    'baris' => 4,
    'labelBuka' => 'Baca selengkapnya',
    'labelTutup' => 'Tampilkan lebih sedikit',
    /* Warna tombol dapat diganti untuk latar gelap, misalnya hero pelatihan. */
    'kelasTombol' => 'text-primary hover:underline',
])

{{-- Teks panjang yang dilipat, dengan tombol buka tutup.

     Deskripsi di sistem ini SENGAJA tidak dibatasi panjangnya, jadi tampilanlah yang
     harus tahan. Pemangkasan memakai line-clamp CSS, bukan pemotongan string, supaya
     isi aslinya tetap utuh untuk mesin pencari dan pembaca layar.

     Tombolnya disembunyikan lewat kelas `hidden` dan baru ditampilkan oleh
     `app.js` bila teksnya memang meluap; teks pendek tidak perlu tombol. Halaman
     publik tidak memuat Alpine, jadi perilakunya ditangani JS terdelegasi. --}}
@php
    /* Kelas ditulis HARFIAH, bukan dirangkai 'line-clamp-'.$baris. Tailwind memindai
       berkas sumber apa adanya; kelas yang baru terbentuk saat runtime tidak pernah
       ikut dikompilasi, dan pelipatannya diam-diam tidak berlaku sama sekali. */
    $clamp = match ((int) $baris) {
        2 => 'line-clamp-2',
        3 => 'line-clamp-3',
        5 => 'line-clamp-5',
        6 => 'line-clamp-6',
        default => 'line-clamp-4',
    };
@endphp

<div data-teks-lipat data-teks-lipat-clamp="{{ $clamp }}" {{ $attributes->class(['group']) }}>
    <div data-teks-lipat-isi class="{{ $clamp }} transition-all duration-300">
        {{ $slot }}
    </div>

    <button type="button"
            data-teks-lipat-tombol
            data-label-buka="{{ $labelBuka }}"
            data-label-tutup="{{ $labelTutup }}"
            class="hidden mt-2 inline-flex items-center gap-1 text-sm font-bold focus:outline-none focus-visible:ring-2 focus-visible:ring-current {{ $kelasTombol }}">
        <span data-teks-lipat-label>{{ $labelBuka }}</span>
        <x-heroicon-s-chevron-down class="h-4 w-4 transition-transform duration-300" data-teks-lipat-ikon />
    </button>
</div>
