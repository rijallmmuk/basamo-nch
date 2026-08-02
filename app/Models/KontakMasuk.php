<?php

namespace App\Models;

use App\Enums\KategoriKontak;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * Pesan masuk dari section "Hubungi Kami" beranda publik (base URL) — Jadi
 * Mitra / Keluhan & Saran. Dibuat warga/tamu publik (tanpa login), dibaca
 * superadmin lewat panel. TANPA email — semua tersimpan di sini.
 */
class KontakMasuk extends Model implements HasMedia
{
    use InteractsWithMedia;
    use SoftDeletes;

    protected $fillable = [
        'user_id', 'kategori', 'nama', 'email', 'no_hp', 'nama_nagari', 'isi', 'balasan', 'balasan_dibaca_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'kategori' => KategoriKontak::class,
            'balasan_dibaca_at' => 'datetime',
        ];
    }

    /** @return \Illuminate\Database\Eloquent\Relations\BelongsTo<User, $this> */
    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reads(): HasMany
    {
        return $this->hasMany(KontakMasukRead::class);
    }

    public function isReadBy(User $user): bool
    {
        return $this->reads()->where('user_id', $user->id)->exists();
    }

    public function markAsReadBy(User $user): void
    {
        $this->reads()->updateOrCreate(
            ['user_id' => $user->id],
            ['read_at' => now()],
        );
    }

    public function markAsUnreadBy(User $user): void
    {
        $this->reads()->where('user_id', $user->id)->delete();
    }
}
