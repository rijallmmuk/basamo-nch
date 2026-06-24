<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Provisioning warga dari data form admin (halaman Create/Edit Warga maupun
 * Relation Manager "Warga" pada Detail Desa). Sumber kebenaran tunggal:
 * pisah field akun vs identitas `penduduk`, atur OTP/sandi, dan sinkron penduduk.
 *
 * OTP TIDAK di-generate otomatis: bila admin mengisi `initial_otp` itu jadi sandi
 * awal; bila kosong, akun dibuat tanpa OTP (sandi acak) — OTP diterbitkan terpisah
 * lewat aksi "Reset OTP" saat warga siap login.
 */
class WargaProvisioningService
{
    public function __construct(private PendudukService $penduduk) {}

    /**
     * Buat warga baru untuk $desaId dari data form.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, int $desaId): User
    {
        [$account, $penduduk] = $this->split($data);

        $account['role'] = 'warga';
        $account['desa_id'] = $desaId;

        $otp = $account['initial_otp'] ?? null;
        if (filled($otp)) {
            $account['password'] = $otp;
            $account['initial_otp'] = $otp;
        } else {
            $account['password'] = Str::random(40); // tak terpakai sampai OTP diterbitkan
            $account['initial_otp'] = null;
        }

        $account['must_change_password'] = true;

        return DB::transaction(function () use ($account, $penduduk): User {
            $user = User::create($account);
            $this->penduduk->syncForUser($user->refresh(), $penduduk);

            return $user;
        });
    }

    /**
     * Perbarui warga + sinkron identitas penduduk. Peran/sandi/OTP tak diubah di sini.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User
    {
        [$account, $penduduk] = $this->split($data);

        return DB::transaction(function () use ($user, $account, $penduduk): User {
            $user->update($account);
            $this->penduduk->syncForUser($user->refresh(), $penduduk);

            return $user;
        });
    }

    /**
     * Data identitas penduduk untuk mengisi form Edit.
     *
     * @return array<string, mixed>
     */
    public function pendudukFormData(User $user): array
    {
        $data = [];

        if ($penduduk = $user->penduduk) {
            foreach (PendudukService::FIELDS as $field) {
                $data[$field] = $field === 'jenis_kelamin'
                    ? $penduduk->jenis_kelamin?->value
                    : $penduduk->{$field};
            }
        }

        return $data;
    }

    /**
     * Pisahkan data form: [field akun `users`, field identitas `penduduk`].
     *
     * @param  array<string, mixed>  $data
     * @return array{0: array<string, mixed>, 1: array<string, mixed>}
     */
    private function split(array $data): array
    {
        $penduduk = [];

        foreach (PendudukService::FIELDS as $field) {
            if (array_key_exists($field, $data)) {
                $penduduk[$field] = $data[$field];
                unset($data[$field]);
            }
        }

        return [$data, $penduduk];
    }
}
