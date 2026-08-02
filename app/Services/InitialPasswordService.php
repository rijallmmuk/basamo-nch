<?php

namespace App\Services;

use App\Models\User;
use RuntimeException;

/**
 * Password awal akun yang dibuatkan operator, dipakai sekali lalu WAJIB diganti
 * pemiliknya saat pertama masuk.
 *
 * Nilainya datang dari environment, TIDAK pernah dari kode. Sebelumnya lima
 * password ini tertulis sebagai konstanta di berkas ini, dan berkas ini ikut ke
 * repo: siapa pun yang membuka GitHub tahu password awal superadmin. Environment
 * tidak ikut ke repo, jadi tiap lingkungan memakai nilainya sendiri.
 *
 * Satu password bersama (`INITIAL_PASSWORD`) sudah cukup untuk semua peran.
 * `INITIAL_PASSWORD_<PERAN>` hanya perlu diisi bila satu peran memang harus
 * berbeda; yang dikosongkan jatuh ke password bersama.
 */
class InitialPasswordService
{
    /** Peran yang punya password awal. Urutan tidak berarti. */
    public const ROLES = ['superadmin', 'operator', 'pengajar', 'dpmd', 'warga'];

    /** Terapkan ulang password awal peran dan paksa penggantian saat login. */
    public function apply(User $user): User
    {
        $role = $user->getRoleNames()->first() ?? 'warga';

        $user->forceFill([
            'password' => $this->forRole($role),
            'must_change_password' => true,
        ])->save();

        return $user;
    }

    /**
     * Password awal untuk sebuah peran.
     *
     * Sengaja MELEMPAR, bukan mengembalikan string kosong, bila environment belum
     * diisi. Diam-diam memakai '' berarti membuat akun dengan password kosong yang
     * hash-nya tetap valid, dan tak seorang pun akan menyadarinya sampai akun itu
     * dibobol.
     */
    public function forRole(?string $role): string
    {
        $roleKey = mb_strtolower(trim((string) $role));

        $sandi = config("onboarding.initial_passwords.{$roleKey}")
            ?: config('onboarding.initial_password');

        if (! is_string($sandi) || trim($sandi) === '') {
            throw new RuntimeException(
                'Password awal belum dikonfigurasi. Isi INITIAL_PASSWORD pada .env '
                ."(atau INITIAL_PASSWORD_{$this->envSuffix($roleKey)} bila peran ini perlu password sendiri), "
                .'lalu jalankan `php artisan config:clear`.'
            );
        }

        return $sandi;
    }

    /**
     * Seluruh password awal yang berlaku, untuk mencegah pengguna "mengganti"
     * sandinya menjadi password awal itu lagi ({@see \App\Rules\NotInitialPassword}).
     *
     * @return list<string>
     */
    public function allInitialPasswords(): array
    {
        $sandi = collect(self::ROLES)
            ->map(fn (string $role): mixed => config("onboarding.initial_passwords.{$role}"))
            ->push(config('onboarding.initial_password'))
            ->filter(fn (mixed $nilai): bool => is_string($nilai) && trim($nilai) !== '')
            ->unique()
            ->values();

        return $sandi->all();
    }

    private function envSuffix(string $role): string
    {
        return mb_strtoupper($role === '' ? 'WARGA' : $role);
    }
}
