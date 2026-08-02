<?php

namespace App\Models;

use App\Enums\JenisEvaluasi;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use LogicException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Evaluasi milik satu modul, dua jenis ({@see JenisEvaluasi}):
 *
 *  - Pre-test  → gerbang opsional sebelum materi terbuka. Tanpa nilai lulus,
 *                dikunci satu percobaan, nilainya disimpan sebagai data awal.
 *  - Evaluasi Kegiatan → penutup modul (dulu bernama "kuis").
 *
 * Satu modul paling banyak punya satu evaluasi AKTIF per jenis; dijaga unique
 * gabungan (active_module_id, jenis) di basis data dan diperiksa ulang saat restore.
 */
class Evaluasi extends Model
{
    use HasFactory;
    use LogsActivity;
    use SoftDeletes {
        forceDelete as private forceDeleteModel;
        restore as private restoreModel;
    }

    protected $attributes = [
        'jenis' => JenisEvaluasi::Kegiatan->value,
        'nilai_lulus' => 70,
        'maks_percobaan' => 0,
    ];

    protected $fillable = [
        'module_id', 'jenis', 'nilai_lulus', 'maks_percobaan', 'ready_notified_at',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['module_id', 'jenis', 'nilai_lulus', 'maks_percobaan'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('evaluasi');
    }

    protected function casts(): array
    {
        return [
            'jenis' => JenisEvaluasi::class,
            'nilai_lulus' => 'integer',
            'maks_percobaan' => 'integer',
            'ready_notified_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Aturan jenis ditegakkan di satu tempat: pre-test tidak mengenal nilai lulus
        // dan selalu satu percobaan, berapa pun yang dikirim form.
        static::saving(function (self $evaluasi): void {
            if ($evaluasi->jenis === JenisEvaluasi::Pretest) {
                $evaluasi->nilai_lulus = 0;
                $evaluasi->maks_percobaan = 1;
            }
        });

        static::restoring(function (self $evaluasi): void {
            if (self::query()
                ->where('module_id', $evaluasi->module_id)
                ->where('jenis', $evaluasi->jenis)
                ->whereKeyNot($evaluasi->getKey())
                ->exists()) {
                throw new LogicException('Evaluasi tidak dapat dipulihkan karena modul sudah memiliki evaluasi aktif berjenis sama.');
            }
        });
    }

    /** Label diturunkan dari jenis + modulnya (tanpa kolom judul tersendiri). */
    protected function title(): Attribute
    {
        return Attribute::get(fn (): string => $this->jenis->getLabel().': '.($this->module?->judul ?? ''));
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function pertanyaans(): HasMany
    {
        return $this->hasMany(EvaluasiPertanyaan::class)->orderBy('urutan')->orderBy('id');
    }

    public function percobaans(): HasMany
    {
        return $this->hasMany(EvaluasiPercobaan::class);
    }

    public function isPretest(): bool
    {
        return $this->jenis === JenisEvaluasi::Pretest;
    }

    /**
     * Siap dikerjakan bila memiliki soal dan setiap soal memiliki minimal dua opsi,
     * setidaknya satu jawaban benar, serta satu pengecoh.
     *
     * @param  Builder<Evaluasi>  $query
     * @return Builder<Evaluasi>
     */
    public function scopeReady(Builder $query): Builder
    {
        return $query
            ->whereHas('pertanyaans')
            ->whereDoesntHave('pertanyaans', fn (Builder $pertanyaans) => $pertanyaans
                ->has('opsis', '<', 2)
                ->orWhereDoesntHave('opsis', fn (Builder $opsis) => $opsis
                    ->where('is_correct', true))
                ->orWhereDoesntHave('opsis', fn (Builder $opsis) => $opsis
                    ->where('is_correct', false)));
    }

    /**
     * @param  Builder<Evaluasi>  $query
     * @return Builder<Evaluasi>
     */
    public function scopeJenis(Builder $query, JenisEvaluasi $jenis): Builder
    {
        return $query->where('jenis', $jenis);
    }

    public function isReady(): bool
    {
        if (! $this->exists) {
            return false;
        }

        if ($this->relationLoaded('pertanyaans')) {
            $pertanyaans = $this->pertanyaans;

            if ($pertanyaans->isEmpty()) {
                return false;
            }

            foreach ($pertanyaans as $pertanyaan) {
                $opsis = $pertanyaan->opsis;

                if ($opsis->count() < 2) {
                    return false;
                }

                if (! $opsis->contains('is_correct', true) || ! $opsis->contains('is_correct', false)) {
                    return false;
                }
            }

            return true;
        }

        return self::query()->ready()->whereKey($this->getKey())->exists();
    }

    /** Dapat dikerjakan warga begitu soalnya lengkap. Tidak ada saklar terbit. */
    public function isPlayable(): bool
    {
        return $this->isReady();
    }

    /**
     * Sisa percobaan warga; null = tak dibatasi. Auto-grade sinkron membuat SETIAP
     * baris percobaan berstatus final ({@see StatusPercobaan}), jadi menghitung
     * seluruh baris sama dengan menghitung percobaan yang sudah terpakai.
     */
    public function sisaPercobaan(User $user): ?int
    {
        if ($this->maks_percobaan === 0) {
            return null;
        }

        $terpakai = $this->percobaans()->where('user_id', $user->getKey())->count();

        return max(0, $this->maks_percobaan - $terpakai);
    }

    public function sudahDikerjakan(User $user): bool
    {
        return $this->percobaans()->where('user_id', $user->getKey())->exists();
    }

    public function delete()
    {
        return DB::transaction(fn () => parent::delete());
    }

    public function restore()
    {
        return DB::transaction(fn () => $this->restoreModel());
    }

    public function forceDelete()
    {
        return DB::transaction(fn () => $this->forceDeleteModel());
    }
}
