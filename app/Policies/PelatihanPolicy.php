<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Pelatihan;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * superadmin dilewatkan via Gate::before (akses penuh); DPMD hanya membaca data
 * SLC dan dapat membalas forum lewat ability terpisah `reply`.
 *
 * PEMILIK pelatihan adalah PEMBUATNYA. Ia sendiri yang boleh mengubah identitas
 * pelatihan, membuka atau mengunci untuk warga, mengatur daftar kolaborator, serta
 * mengarsipkan dan menghapusnya.
 *
 * KOLABORATOR (pivot `pelatihan_pengajar`) boleh mengisi konten serta membuka dan
 * mengunci akses warga. Identitas pelatihan, daftar kolaborator, dan siklus hidup
 * rekaman tetap hanya boleh dikelola pemilik.
 *
 * OPERATOR dapat membuat serta mengelola pelatihan miliknya sendiri. Sasaran
 * pelatihan operator dikunci ke nagari akunnya. Pelatihan lain yang menyasar
 * nagarinya tetap hanya dapat dilihat untuk kebutuhan supervisi.
 */
class PelatihanPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->isOperator() || $user->isPengajar();
    }

    public function view(User $user, Pelatihan $program): bool
    {
        return $this->targetsOperatorNagari($user, $program)
            || $this->mengelola($user, $program);
    }

    /** Pemilik = pembuat pelatihan. Operator tidak dapat mengatur kolaborator. */
    public function kelolaKolaborator(User $user, Pelatihan $program): bool
    {
        if ($user->isOperator()) {
            return false;
        }

        return $this->memiliki($user, $program);
    }

    public function create(User $user): bool
    {
        return $user->isPengajar() || $user->isOperator();
    }

    /** Mengubah identitas pelatihan: tetap khusus pemilik. */
    public function update(User $user, Pelatihan $program): bool
    {
        return $this->memiliki($user, $program);
    }

    /** Membuka/mengunci akses warga: pemilik maupun pengajar kolaborator. */
    public function kelolaStatus(User $user, Pelatihan $program): bool
    {
        return $this->mengelola($user, $program);
    }

    public function delete(User $user, Pelatihan $program): bool
    {
        return $this->memiliki($user, $program);
    }

    public function restore(User $user, Pelatihan $program): bool
    {
        return $this->memiliki($user, $program);
    }

    public function forceDelete(User $user, Pelatihan $program): bool
    {
        return ! $program->hasLearningActivity()
            && ($user->isSuperAdmin() || $this->memiliki($user, $program));
    }

    /**
     * Boleh mengisi KONTEN pelatihan (buat/ubah modul, materi, evaluasi): pemilik
     * DAN kolaborator. Ini jangkar authorization untuk "tambah modul ke pelatihan X".
     */
    public function kelolaKonten(User $user, Pelatihan $program): bool
    {
        return $this->mengelola($user, $program);
    }

    /** Pemilik pelatihan, yaitu pembuatnya. */
    private function memiliki(User $user, Pelatihan $program): bool
    {
        return $program->created_by !== null
            && $program->created_by === $user->getKey();
    }

    /** Pemilik atau kolaborator: keduanya boleh menggarap isi pelatihan. */
    private function mengelola(User $user, Pelatihan $program): bool
    {
        if ($this->memiliki($user, $program)) {
            return true;
        }

        if (! $user->isPengajar()) {
            return false;
        }

        return $program->pengajars()->whereKey($user->getKey())->exists();
    }

    private function targetsOperatorNagari(User $user, Pelatihan $program): bool
    {
        return $user->isOperator()
            && $user->nagari_id !== null
            && Pelatihan::query()
                ->visibleTo($user)
                ->whereKey($program->getKey())
                ->exists();
    }
}
