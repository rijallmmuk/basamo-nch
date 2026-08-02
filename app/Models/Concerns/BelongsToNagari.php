<?php

namespace App\Models\Concerns;

use App\Models\Nagari;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Untuk model milik satu nagari (punya kolom `nagari_id`). Memusatkan relasi `nagari()`
 * dan menyediakan scope `forNagari()` agar penyaringan tenant konsisten & terbaca.
 *
 * Catatan: sengaja TANPA global scope — superadmin perlu melihat lintas-nagari,
 * jadi penyaringan tetap eksplisit di pemanggil (resource/controller).
 */
trait BelongsToNagari
{
    public function nagari(): BelongsTo
    {
        return $this->belongsTo(Nagari::class);
    }

    /**
     * Batasi query ke satu nagari (penyaringan tenant). `null` → cocokkan
     * `nagari_id IS NULL` (mis. operator tanpa nagari = tak bocor lihat nagari lain),
     * BUKAN "lihat semua". superadmin tak memakai scope ini (lihat semua lewat
     * tak memanggilnya).
     *
     * @param  Builder<static>  $query
     */
    public function scopeForNagari(Builder $query, Nagari|int|string|null $nagariId): void
    {
        $query->where(
            $this->getTable().'.nagari_id',
            $nagariId instanceof Nagari ? $nagariId->getKey() : $nagariId,
        );
    }
}
