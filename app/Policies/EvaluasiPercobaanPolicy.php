<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\EvaluasiPercobaan;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * superadmin dilewatkan via Gate::before. operator boleh melihat (untuk pemantauan).
 * Attempt dibuat oleh warga lewat portal (di luar policy ini), bukan oleh admin.
 */
class EvaluasiPercobaanPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isOperator() || $user->isPengajar();
    }

    public function view(User $user, EvaluasiPercobaan $evaluasiAttempt): bool
    {
        if ($user->isOperator()) {
            return $user->nagari_id !== null
                && EvaluasiPercobaan::query()
                    ->whereKey($evaluasiAttempt->getKey())
                    ->whereHas('user', fn ($query) => $query->where('nagari_id', $user->nagari_id))
                    ->exists();
        }

        return $user->isPengajar()
            && EvaluasiPercobaan::query()
                ->whereKey($evaluasiAttempt->getKey())
                ->whereHas('evaluasi.module', fn ($query) => $query->manageableBy($user))
                ->exists();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, EvaluasiPercobaan $evaluasiAttempt): bool
    {
        return false;
    }

    public function delete(User $user, EvaluasiPercobaan $evaluasiAttempt): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, EvaluasiPercobaan $evaluasiAttempt): bool
    {
        return false;
    }

    public function forceDelete(User $user, EvaluasiPercobaan $evaluasiAttempt): bool
    {
        return false;
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return false;
    }

    public function replicate(User $user, EvaluasiPercobaan $evaluasiAttempt): bool
    {
        return false;
    }

    public function reorder(User $user): bool
    {
        return false;
    }
}
