<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class FirstLoginPasswordService
{
    /**
     * Hanya satu sesi dapat memenangkan penggantian password pertama.
     * Mengembalikan null bila sesi lain sudah lebih dahulu menggantinya.
     */
    public function change(User $user, string $newPassword, ?string $newUsername = null, ?string $newLembaga = null): ?User
    {
        return DB::transaction(function () use ($user, $newPassword, $newUsername, $newLembaga): ?User {
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->getKey());

            if (! $lockedUser->must_change_password) {
                return null;
            }

            $attributes = ['password' => $newPassword];

            if (filled($newUsername) && $lockedUser->hasAnyRole(['superadmin', 'pengajar', 'dpmd']) && $lockedUser->penduduk_id === null) {
                $attributes['username'] = trim($newUsername);
            }

            if (filled($newLembaga) && $lockedUser->hasRole('pengajar')) {
                $attributes['lembaga'] = trim($newLembaga);
            }

            $lockedUser->forceFill($attributes)->save();

            return $lockedUser;
        });
    }
}
