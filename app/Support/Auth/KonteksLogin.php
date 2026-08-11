<?php

namespace App\Support\Auth;

use App\Enums\GerbangLogin;
use Illuminate\Http\Request;

/**
 * Apa yang dibawa halaman publik menyeberangi halaman login.
 *
 * Isinya SELALU berupa penanda, tidak pernah berupa URL: satu kata dari daftar
 * tertutup {@see GerbangLogin}, dan satu id pelatihan berupa angka. Keduanya
 * ditafsirkan ulang di sisi kita menjadi alamat, sehingga tidak ada masukan luar
 * yang pernah menjadi tujuan redirect apa adanya.
 *
 * Dibungkus jadi satu objek supaya tanda tangan {@see TujuanSetelahLogin::untuk()}
 * tidak berderet parameter nullable yang gampang tertukar urutannya, dan supaya
 * konteks berikutnya cukup ditambahkan di sini.
 */
readonly class KonteksLogin
{
    public function __construct(
        public ?GerbangLogin $gerbang = null,
        /** Pelatihan yang sedang dilihat saat menekan Masuk; hanya berarti pada gerbang belajar. */
        public ?int $pelatihanId = null,
    ) {}

    /**
     * Baca dari permintaan, baik query string halaman login maupun input
     * tersembunyi form-nya.
     *
     * Nilai asing menjadi null, bukan galat. Konteks cuma pemandu tujuan: tautan
     * usang atau tempelan tangan yang keliru harus menurunkan pengalaman menjadi
     * login biasa, bukan menghalangi orang masuk ke akunnya sendiri.
     */
    public static function dariRequest(Request $request): self
    {
        $pelatihan = $request->input('pelatihan');

        return new self(
            gerbang: GerbangLogin::dariInput($request->input('gerbang')),
            // ctype_digit menolak angka negatif, desimal, dan spasi sekaligus,
            // yang semuanya tak pernah menjadi id pelatihan yang sah.
            pelatihanId: is_scalar($pelatihan) && ctype_digit((string) $pelatihan)
                ? (int) $pelatihan
                : null,
        );
    }

    /** Parameter query untuk tautan Masuk; kosong bila tak ada konteks sama sekali. */
    public function sebagaiParameter(): array
    {
        return array_filter([
            'gerbang' => $this->gerbang?->value,
            'pelatihan' => $this->pelatihanId,
        ], fn (mixed $nilai): bool => $nilai !== null);
    }
}
