<?php

namespace App\Enums;

/**
 * Gerbang login: halaman publik tempat seseorang menekan tombol Masuk.
 *
 * Gunanya membuat login peka konteks. Orang yang menekan Masuk dari Lapau Nagari
 * sedang berpikir tentang lapaknya, bukan tentang pelatihan, dan sebaliknya di
 * Medan Nan Balinduang. Gerbang inilah yang membawa maksud itu melewati halaman
 * login sampai ke penentuan tujuan.
 *
 * SENGAJA BUKAN URL, dan karena itu bukan pula `intended()`. Yang berpindah dari
 * halaman ke login hanya satu kata dari daftar tertutup ini, jadi tidak ada
 * permukaan open-redirect sama sekali. Alasan lama menolak `intended()` juga
 * masih berlaku: URL tersimpan dapat melintasi area, misalnya `/panel` yang
 * tersimpan saat masih tamu lalu dibuka oleh warga, dan berujung pada penolakan
 * tepat setelah login berhasil.
 *
 * Tujuannya sendiri tidak diputuskan di sini melainkan di
 * {@see \App\Support\Auth\TujuanSetelahLogin}, karena tujuan adalah fungsi dari
 * gerbang DAN identitas pengguna, bukan dari gerbangnya saja.
 */
enum GerbangLogin: string
{
    /** Medan Nan Balinduang: katalog pelatihan dan halaman muka pelatihan. */
    case Belajar = 'belajar';

    /** Lapau Nagari: direktori usaha, etalase, dan detail produk. */
    case Umkm = 'umkm';

    /**
     * Gerbang dari nama route halaman publik, atau null bila halaman itu netral.
     *
     * Dicocokkan pada PENGGALAN nama, bukan daftar nama lengkap. Satu halaman
     * publik hidup dalam tiga keluarga route sekaligus: subdomain nagari
     * (`public.nagari.slc`), domain induk (`public.slc`), dan fallback tanpa
     * subdomain (`public.nagari.slc.fallback`). Mendaftar nama lengkap berarti
     * menulis tiga baris untuk satu halaman dan lupa salah satunya, biasanya
     * fallback, yang membuat fitur ini mati diam-diam justru di lingkungan tanpa
     * DNS wildcard. Dengan mencocokkan penggalan, satu aturan melayani ketiganya
     * dan halaman publik baru ikut tercakup tanpa perlu diingat-ingat.
     */
    public static function dariNamaRute(?string $namaRute): ?self
    {
        if ($namaRute === null || ! str_starts_with($namaRute, 'public.')) {
            return null;
        }

        $bagian = explode('.', $namaRute);

        // Medan Nan Balinduang. `pelatihan` mencakup halaman muka satu pelatihan,
        // yang justru gerbang belajar paling kuat: orang sudah melihat pelatihan
        // tertentu sebelum menekan Masuk.
        if (array_intersect(['slc', 'pelatihan'], $bagian) !== []) {
            return self::Belajar;
        }

        // Lapau Nagari, termasuk etalase usaha dan detail produk.
        if (array_intersect(['umkm', 'produk'], $bagian) !== []) {
            return self::Umkm;
        }

        return null;
    }

    /**
     * Gerbang dari nilai mentah yang datang lewat query string atau input form.
     *
     * Nilai asing DIABAIKAN menjadi null, bukan ditolak dengan galat. Gerbang
     * cuma pemandu tujuan: tautan usang atau tempelan tangan yang salah
     * seharusnya menurunkan pengalaman menjadi login biasa, bukan menghalangi
     * orang masuk ke akunnya sendiri.
     */
    public static function dariInput(mixed $nilai): ?self
    {
        return is_string($nilai) ? self::tryFrom($nilai) : null;
    }

    /** Judul kecil di halaman login, menegaskan konteks yang sedang dituju. */
    public function judul(): string
    {
        return match ($this) {
            self::Belajar => 'Menuju ruang belajar',
            self::Umkm => 'Menuju pengelolaan lapak',
        };
    }

    /** Kalimat penjelas di halaman login, satu kalimat sesuai gaya form proyek ini. */
    public function keterangan(): string
    {
        return match ($this) {
            self::Belajar => 'Setelah masuk, Anda langsung diarahkan ke portal belajar.',
            self::Umkm => 'Setelah masuk, Anda langsung diarahkan ke pengelolaan lapak Anda.',
        };
    }
}
