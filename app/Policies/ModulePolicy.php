<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Module;
use App\Models\Pelatihan;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Modul dibuat dan diubah pengelola pelatihannya: superadmin, pengajar pemilik/
 * kolaborator, atau operator pemilik. Operator hanya menonton modul dari
 * pelatihan lain yang menyasar nagarinya.
 */
class ModulePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isOperator() || $user->isPengajar();
    }

    public function view(User $user, Module $module): bool
    {
        return $this->visibleToOperator($user, $module)
            || $this->mengelolaPelatihan($user, $module->pelatihan_id);
    }

    /**
     * Pemilihan pelatihannya dijaga PelatihanPolicy::kelolaKonten (jangkar
     * per-pelatihan; ability create tak membawa konteks pelatihan).
     */
    public function create(User $user): bool
    {
        return $user->isPengajar() || $user->isOperator();
    }

    public function update(User $user, Module $module): bool
    {
        return $this->mengelolaPelatihan($user, $module->pelatihan_id);
    }

    public function delete(User $user, Module $module): bool
    {
        return $this->mengelolaPelatihan($user, $module->pelatihan_id);
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }

    public function restore(User $user, Module $module): bool
    {
        return $this->mengelolaPelatihan($user, $module->pelatihan_id);
    }

    public function forceDelete(User $user, Module $module): bool
    {
        return ! $module->hasLearningActivity()
            && ($user->isSuperAdmin() || $this->mengelolaPelatihan($user, $module->pelatihan_id));
    }

    public function forceDeleteAny(User $user): bool
    {
        return false;
    }

    public function restoreAny(User $user): bool
    {
        return false;
    }

    public function replicate(User $user, Module $module): bool
    {
        return false;
    }

    public function reorder(User $user): bool
    {
        return false;
    }

    /**
     * Pengajar pengelola pelatihan modul ini? Query-based (bukan lazy relation) —
     * policy sering menerima model tanpa eager load `pelatihan`.
     */
    private function mengelolaPelatihan(User $user, ?int $pelatihanId): bool
    {
        return ($user->isPengajar() || $user->isOperator())
            && $pelatihanId !== null
            && Pelatihan::whereKey($pelatihanId)
                ->manageableBy($user)
                ->exists();
    }

    private function visibleToOperator(User $user, Module $module): bool
    {
        return $user->isOperator()
            && Module::query()
                ->visibleTo($user)
                ->whereKey($module->getKey())
                ->exists();
    }
}
