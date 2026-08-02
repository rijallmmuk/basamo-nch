<?php

namespace App\Models;

use App\Enums\ActiveStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Perangkat sensor EWS banjir bandang milik satu nagari.
 *
 * Token Blynk-nya TERENKRIPSI di basis data dan tidak pernah ikut ke tampilan.
 * Ia bukan kredensial baca-saja: endpoint `update` Blynk memakai token yang sama,
 * jadi yang memegangnya bisa menulis nilai palsu ke alat peringatan dini banjir.
 */
class EwsDevice extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = ['nagari_id', 'nama_lokasi', 'blynk_token', 'aktif'];

    /**
     * Token tidak boleh ikut serialisasi apa pun (JSON respons, dd(), log).
     *
     * @var list<string>
     */
    protected $hidden = ['blynk_token'];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'blynk_token' => 'encrypted',
            'aktif' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            // Token SENGAJA tidak dicatat: log aktivitas dapat dibaca admin dan
            // diekspor, dan mencatatnya di sana membatalkan gunanya enkripsi kolom.
            ->logOnly(['nagari_id', 'nama_lokasi', 'aktif'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('ews');
    }

    public function nagari(): BelongsTo
    {
        return $this->belongsTo(Nagari::class);
    }

    public function readings(): HasMany
    {
        return $this->hasMany(EwsReading::class);
    }

    /**
     * Pembacaan terakhir, agar daftar banyak titik pantau bisa dimuat sekaligus
     * (`with('pembacaanTerakhir')`) alih-alih satu query per perangkat.
     */
    public function pembacaanTerakhir(): HasOne
    {
        return $this->hasOne(EwsReading::class)->latestOfMany('direkam_pada');
    }

    /** Nama yang ditampilkan: nama titik pantau bila diisi, selain itu nama nagarinya. */
    public function namaTampil(): string
    {
        if (filled($this->nama_lokasi)) {
            return (string) $this->nama_lokasi;
        }

        $this->loadMissing('nagari');

        return $this->nagari?->nama ?? 'Titik pantau';
    }

    /**
     * Perangkat yang benar-benar boleh dipanggil dan ditampilkan: aktif, dan
     * nagarinya juga aktif. Nagari yang dibekukan tidak boleh tetap menyiarkan
     * data lewat halaman pemantauan publik.
     *
     * @param  Builder<EwsDevice>  $query
     */
    public function scopeSiapPakai(Builder $query): void
    {
        $query->where('aktif', true)
            ->whereHas('nagari', fn (Builder $nagari) => $nagari->where('status', ActiveStatus::Active));
    }
}
