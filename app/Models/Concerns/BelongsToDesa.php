<?php

namespace App\Models\Concerns;

use App\Models\Desa;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Untuk model milik satu desa (punya kolom `desa_id`). Memusatkan relasi `desa()`
 * dan menyediakan scope `forDesa()` agar penyaringan tenant konsisten & terbaca.
 *
 * Catatan: sengaja TANPA global scope — super_admin perlu melihat lintas-desa,
 * jadi penyaringan tetap eksplisit di pemanggil (resource/controller).
 */
trait BelongsToDesa
{
    public function desa(): BelongsTo
    {
        return $this->belongsTo(Desa::class);
    }

    /**
     * Batasi query ke satu desa. Abaikan bila $desaId null (mis. super_admin global).
     *
     * @param  Builder<static>  $query
     */
    public function scopeForDesa(Builder $query, Desa|int|string|null $desaId): void
    {
        if ($desaId === null) {
            return;
        }

        $query->where($this->getTable().'.desa_id', $desaId instanceof Desa ? $desaId->getKey() : $desaId);
    }
}
