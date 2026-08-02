<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Evaluasi;
use App\Models\Module;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Pre-test dan Evaluasi Kegiatan mengikuti hak atas MODUL induknya: dibuat dan
 * diubah pengelola, dilihat operator sebatas nagarinya.
 */
class EvaluasiPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isOperator() || $user->isPengajar();
    }

    public function view(User $user, Evaluasi $evaluasi): bool
    {
        return Module::query()
            ->visibleTo($user)
            ->whereKey($evaluasi->module_id)
            ->exists();
    }

    public function create(User $user): bool
    {
        return $user->isPengajar() || $user->isOperator();
    }

    public function update(User $user, Evaluasi $evaluasi): bool
    {
        return $this->mengelolaModul($user, $evaluasi);
    }

    public function delete(User $user, Evaluasi $evaluasi): bool
    {
        return $this->mengelolaModul($user, $evaluasi);
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, Evaluasi $evaluasi): bool
    {
        return $this->mengelolaModul($user, $evaluasi);
    }

    public function forceDelete(User $user, Evaluasi $evaluasi): bool
    {
        if ($evaluasi->percobaans()->exists()) {
            return false;
        }

        return $user->isSuperAdmin() || $this->mengelolaModul($user, $evaluasi);
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return false;
    }

    public function replicate(User $user, Evaluasi $evaluasi): bool
    {
        return false;
    }

    public function reorder(User $user): bool
    {
        return false;
    }

    /** Evaluasi hanya dapat dimutasi pengelola modul induknya. */
    private function mengelolaModul(User $user, Evaluasi $evaluasi): bool
    {
        return ($user->isPengajar() || $user->isOperator())
            && Module::query()
                ->manageableBy($user)
                ->whereKey($evaluasi->module_id)
                ->exists();
    }
}
