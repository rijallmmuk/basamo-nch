<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\SdgAchievement;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * Capaian SDGs — viewer baca-saja utk operator nagari (skor ditarik dari API
 * Kemendesa via job terjadwal, bukan input manual). Scoping nagari dijaga
 * query resource. superadmin dilewatkan via Gate::before.
 */
class SdgAchievementPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isOperator();
    }

    public function view(User $user, SdgAchievement $achievement): bool
    {
        return $user->isOperator() && $achievement->nagari_id === $user->nagari_id;
    }
}
