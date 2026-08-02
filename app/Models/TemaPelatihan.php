<?php

namespace App\Models;

use App\Support\TemaNormalizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Tema Pelatihan = data referensi ringan berisi identitas tema saja. Tanpa
 * deskripsi dan tanpa cover; cover kartu digambar otomatis dari namanya
 * (lihat komponen `x-slc.tema-cover`).
 *
 * Tema dipakai BERSAMA lintas pengguna: satu tema boleh dipakai banyak
 * pelaksanaan milik pengajar, nagari, dan tahun berbeda. Memakai tema yang sama
 * TIDAK memberi akses apa pun ke pelaksanaan milik orang lain.
 *
 * Keunikan dijaga di `nama_normal` ({@see TemaNormalizer}), bukan di `nama`
 * mentah, sehingga beda kapital/spasi jatuh ke baris yang sama sementara simbol
 * bermakna (C++, UI/UX) tetap dibedakan.
 */
class TemaPelatihan extends Model
{
    use HasFactory;
    use LogsActivity;

    protected $fillable = ['nama'];

    protected static function booted(): void
    {
        static::saving(function (self $tema): void {
            $tema->nama = trim((string) $tema->nama);
            $tema->nama_normal = TemaNormalizer::normalize($tema->nama);

            if ($tema->nama_normal === '') {
                throw ValidationException::withMessages([
                    'nama' => 'Nama tema pelatihan tidak boleh kosong.',
                ]);
            }
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['nama'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('tema_pelatihan');
    }

    /** Pelaksanaan pelatihan yang memakai tema ini. */
    public function pelatihans(): HasMany
    {
        return $this->hasMany(Pelatihan::class);
    }

    /**
     * Ambil tema dengan nama setara, atau catat baru bila belum ada.
     *
     * Saat tema yang setara sudah ada, baris LAMA dikembalikan apa adanya:
     * ejaan/nama tampilan yang sudah tersimpan TIDAK ditimpa oleh variasi
     * penulisan yang baru diketik.
     */
    public static function findOrCreateByNama(string $nama): self
    {
        $normal = TemaNormalizer::normalize($nama);

        if ($normal === '') {
            throw ValidationException::withMessages([
                'nama' => 'Nama tema pelatihan tidak boleh kosong.',
            ]);
        }

        $existing = static::query()->where('nama_normal', $normal)->first();

        if ($existing) {
            return $existing;
        }

        return static::create(['nama' => trim($nama)]);
    }

    /** Apakah nama ini akan jatuh ke tema yang sudah tercatat (bukan tema baru)? */
    public static function sudahTercatat(string $nama): ?self
    {
        $normal = TemaNormalizer::normalize($nama);

        return $normal === ''
            ? null
            : static::query()->where('nama_normal', $normal)->first();
    }

    /**
     * Tema yang MIRIP tetapi tidak setara, untuk ditawarkan sebagai saran sebelum
     * tema baru dicatat. Perbandingan dilakukan di PHP (bukan fungsi SQL khusus
     * driver seperti SOUNDS LIKE) supaya perilakunya sama di semua lingkungan.
     * Tabel tema kecil, jadi biayanya wajar.
     *
     * @return Collection<int, self>
     */
    public static function miripDengan(string $nama, int $limit = 3): Collection
    {
        $normal = TemaNormalizer::normalize($nama);
        $sidik = TemaNormalizer::fingerprint($nama);

        if ($sidik === '') {
            return collect();
        }

        return static::query()
            ->where('nama_normal', '!=', $normal)
            ->get()
            ->map(function (self $tema) use ($sidik): array {
                $lain = TemaNormalizer::fingerprint($tema->nama);
                similar_text($sidik, $lain, $persen);

                return ['tema' => $tema, 'skor' => $persen];
            })
            ->filter(fn (array $baris): bool => $baris['skor'] >= 80.0)
            ->sortByDesc('skor')
            ->take($limit)
            ->map(fn (array $baris): self => $baris['tema'])
            ->values();
    }

    /** Tema sedang dipakai pelaksanaan mana pun (termasuk yang ter-arsip). */
    public function dipakai(): bool
    {
        return $this->pelatihans()->withTrashed()->exists();
    }

    /**
     * Tema yang belum dipakai pelaksanaan mana pun (aman dihapus).
     *
     * @param  Builder<TemaPelatihan>  $query
     */
    public function scopeTidakDipakai(Builder $query): void
    {
        $query->whereDoesntHave('pelatihans', fn (Builder $pelatihans) => $pelatihans->withTrashed());
    }
}
