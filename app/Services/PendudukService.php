<?php

namespace App\Services;

use App\Models\Penduduk;
use App\Models\User;

/**
 * Menjembatani akun warga (`users`) dengan identitas kependudukannya (`penduduk`).
 * NIK/nama/desa di-mirror dari akun ke penduduk (kanonik di penduduk); demografi
 * disimpan hanya di penduduk.
 */
class PendudukService
{
    /** Field identitas yang dikelola di tabel `penduduk` (dipisah dari kolom akun `users`). */
    public const FIELDS = [
        'tempat_lahir', 'tanggal_lahir', 'jenis_kelamin',
        'agama_id', 'status_perkawinan_id', 'pekerjaan_id',
    ];

    /**
     * Upsert baris `penduduk` untuk akun warga lalu tautkan via `users.penduduk_id`.
     *
     * @param  array<string, mixed>  $identity  subset FIELDS dari form
     */
    public function syncForUser(User $user, array $identity): void
    {
        $penduduk = $user->penduduk ?: new Penduduk;

        $penduduk->fill(array_intersect_key($identity, array_flip(self::FIELDS)));

        $penduduk->forceFill([
            'nik' => $user->nik,
            'nama' => $user->name,
            'desa_id' => $user->desa_id,
            'desa_unit_id' => $user->desa_unit_id,
        ])->save();

        if ($user->penduduk_id !== $penduduk->id) {
            $user->forceFill(['penduduk_id' => $penduduk->id])->saveQuietly();
        }
    }
}
