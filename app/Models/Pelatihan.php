<?php

namespace App\Models;

use App\Enums\ActiveStatus;
use App\Enums\ModeSertifikat;
use App\Enums\StatusPelatihan;
use App\Models\Concerns\BelongsToNagari;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * Pelatihan = milik PENGAJAR PEMBUATNYA, wadah bagi modul-modulnya.
 *
 * Pengajar yang hendak menaruh modul membuat pelatihannya lebih dulu bila belum ada.
 * GERBANG AKSES warga ada di sini (`status`); modul di dalamnya sengaja tanpa status
 * agar tidak ada dua saklar untuk satu keputusan.
 *
 * Tidak punya nama/deskripsi/cover sendiri. Nama tampil dirakit dari tema dan sasaran
 * ({@see namaTampil()}); cover digambar otomatis dari nama temanya.
 *
 * SASARAN AUDIENS ada di pivot `pelatihan_nagari` (relasi {@see nagaris()}) atau
 * `semua_nagari` = true (dinamis). `nagari_id` = nagari PENYELENGGARA saja, dasar
 * scoping operator; `created_by` = pembuat.
 *
 * Warga mengakses pelaksanaan yang menyasar nagarinya tanpa enrollment — progres
 * dibuat otomatis saat mulai belajar, bukan syarat akses.
 */
class Pelatihan extends Model implements HasMedia
{
    use BelongsToNagari, InteractsWithMedia, LogsActivity;
    use HasFactory;
    use SoftDeletes {
        forceDelete as private forceDeleteModel;
        restore as private restoreModel;
    }

    protected $attributes = [
        'status' => StatusPelatihan::Terkunci->value,
        'semua_nagari' => false,
    ];

    protected $fillable = [
        'tema_pelatihan_id', 'nagari_id', 'deskripsi', 'created_by', 'status', 'semua_nagari',
        'sertifikat_mode',
        'pertemuan_url', 'pertemuan_platform', 'pertemuan_mulai', 'pertemuan_selesai',
    ];

    protected static function booted(): void
    {
        // Hapus (soft) pelaksanaan → seluruh isinya ikut disampah: modul (yang
        // meng-cascade ke materi/evaluasi/diskusi modulnya). Dapat dipulihkan bersama
        // lewat Restore. Hapus PERMANEN ditangani cascade FK (bukan di sini).
        static::deleting(function (self $pelatihan): void {
            if ($pelatihan->isForceDeleting()) {
                $pelatihan->modules()
                    ->withTrashed()
                    ->eachById(fn (Module $module) => $module->forceDelete());

                return;
            }

            $cascadeAt = now();

            $pelatihan->modules()->eachById(function (Module $module) use ($cascadeAt): void {
                $module->forceFill(['cascade_deleted_at' => $cascadeAt])->saveQuietly();
                $module->delete();
            });
        });

        static::restoring(function (self $pelatihan): void {
            $pelatihan->modules()
                ->onlyTrashed()
                ->whereNotNull('cascade_deleted_at')
                ->eachById(function (Module $module): void {
                    $module->restore();
                    $module->forceFill(['cascade_deleted_at' => null])->saveQuietly();
                });
        });
    }

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => StatusPelatihan::class,
            'semua_nagari' => 'boolean',
            'sertifikat_mode' => ModeSertifikat::class,
            'pertemuan_mulai' => 'datetime',
            'pertemuan_selesai' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['tema_pelatihan_id', 'nagari_id', 'status', 'semua_nagari'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('pelatihan');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')
            ->useDisk(config('media-library.disk_name'))
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

        // Berkas sertifikat siap pakai milik penyelenggara, satu untuk semua peserta.
        $this->addMediaCollection('sertifikat')
            ->useDisk(config('media-library.disk_name'))
            ->singleFile()
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg', 'image/png']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('card')
            ->fit(Fit::Crop, 800, 600)
            ->format('webp')
            ;
    }

    /** Ada cover unggahan? Bila tidak, tampilan memakai gambar otomatis dari nama tema. */
    public function punyaCover(): bool
    {
        return $this->getFirstMedia('cover') !== null;
    }

    /** URL cover unggahan (konversi 'card'); null bila belum ada unggahan. */
    public function coverUrl(): ?string
    {
        $media = $this->getFirstMedia('cover');

        if (! $media) {
            return null;
        }

        $url = $media->getAvailableUrl(['card']);

        return ! empty($url) ? $url : $media->getUrl();
    }

    public function tema(): BelongsTo
    {
        return $this->belongsTo(TemaPelatihan::class, 'tema_pelatihan_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function modules(): HasMany
    {
        return $this->hasMany(Module::class);
    }

    /** Progres warga diturunkan dari seluruh modul pelaksanaan, tanpa tabel enrollment. */
    public function progress(): HasManyThrough
    {
        return $this->hasManyThrough(
            UserModuleProgress::class,
            Module::class,
            'pelatihan_id',
            'module_id',
        );
    }

    /** Pengajar yang ditugaskan ke pelaksanaan ini (banyak-ke-banyak). */
    public function pengajars(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'pelatihan_pengajar')->withTimestamps();
    }

    /**
     * Nagari SASARAN pelaksanaan (audiens, pivot `pelatihan_nagari`). Diabaikan bila
     * `semua_nagari` = true. Seluruh modul di dalamnya mewarisi sasaran ini.
     */
    public function nagaris(): BelongsToMany
    {
        return $this->belongsToMany(Nagari::class, 'pelatihan_nagari')
            ->withTimestamps();
    }

    /** Nama tampil dirakit, bukan diketik: "Digital Marketing UMKM · Nagari X". */
    public function namaTampil(): string
    {
        $this->loadMissing('tema');

        return implode(' · ', [
            $this->tema?->nama ?? 'Tanpa Tema',
            $this->targetAudienceLabel(),
        ]);
    }

    public function temaNama(): string
    {
        $this->loadMissing('tema');

        return $this->tema?->nama ?? 'Tanpa Tema';
    }

    public function targetAudienceLabel(): string
    {
        if ($this->semua_nagari) {
            return 'Seluruh Nagari';
        }

        $this->loadMissing('nagaris');
        $nagaris = $this->nagaris;

        if ($nagaris->count() === 1) {
            return 'Nagari '.$nagaris->first()->nama;
        }

        return $nagaris->count() > 0
            ? "{$nagaris->count()} Nagari Sasaran"
            : 'Sasaran Spesifik';
    }

    public function dapatDimasuki(): bool
    {
        return $this->status->dapatDimasuki();
    }

    /** Id nagari sasaran efektif; null berarti seluruh nagari (dinamis). */
    public function sasaranNagariIds(): ?array
    {
        if ($this->semua_nagari) {
            return null;
        }

        return $this->nagaris()->pluck('nagaris.id')->all();
    }

    /**
     * Siap dibuka: punya sasaran nagari dan minimal satu modul yang sudah berisi materi.
     *
     * @param  Builder<Pelatihan>  $query
     */
    public function scopeReady(Builder $query): void
    {
        $query->where(fn (Builder $target) => $target
            ->where('semua_nagari', true)
            ->orWhereHas('nagaris'))
            // Pelatihan yang diisi lewat pertemuan daring boleh tidak berisi modul.
            // Tanpa cabang ini ia tidak akan pernah muncul di katalog warga.
            ->where(fn (Builder $isi) => $isi
                ->whereHas('modules', fn (Builder $modules) => $modules->ready())
                ->orWhereNotNull('pertemuan_url'));
    }

    public function isReady(): bool
    {
        if (! $this->exists) {
            return false;
        }

        if (! $this->semua_nagari && ! $this->nagaris()->exists()) {
            return false;
        }

        return $this->punyaPertemuan() || $this->modules()->ready()->exists();
    }

    /** Pelatihan ini diisi lewat pertemuan daring? Cukup dilihat dari tautannya. */
    public function punyaPertemuan(): bool
    {
        return filled($this->pertemuan_url);
    }

    /** @return HasMany<PelatihanAttendance, $this> */
    public function kehadirans(): HasMany
    {
        return $this->hasMany(PelatihanAttendance::class);
    }

    public function sudahHadir(User $user): bool
    {
        return $this->kehadirans()->where('user_id', $user->getKey())->exists();
    }

    /**
     * Pertemuannya sudah dimulai? Dipakai untuk menolak penandaan hadir pada pertemuan
     * yang belum berlangsung. Tanpa waktu mulai, penandaan dibiarkan terbuka.
     */
    public function pertemuanSudahMulai(): bool
    {
        return $this->pertemuan_mulai === null || $this->pertemuan_mulai->isPast();
    }

    /** @return Collection<int, User> */
    public function participants(): Collection
    {
        $this->loadMissing(['creator', 'pengajars']);

        return collect([$this->creator])
            ->filter()
            ->merge($this->pengajars)
            ->unique(fn (User $user): int => $user->getKey())
            ->values();
    }

    /** Sudah ada interaksi warga yang tidak boleh dihapus permanen. */
    public function hasLearningActivity(): bool
    {
        return $this->progress()->exists()
            || EvaluasiPercobaan::query()
                ->whereHas('evaluasi.module', fn (Builder $modules) => $modules
                    ->where('pelatihan_id', $this->getKey()))
                ->exists()
            || Discussion::query()
                ->whereHas('module', fn (Builder $modules) => $modules
                    ->where('pelatihan_id', $this->getKey()))
                ->exists();
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

    /**
     * Query warga aktif SASARAN pelaksanaan (penerima notifikasi): `semua_nagari` →
     * seluruh warga lintas nagari; else → warga di nagari pivot. Tanpa audiens →
     * query kosong. Selalu di-chunk pemanggil: bisa menyasar ribuan warga.
     *
     * @return Builder<User>
     */
    public function wargaSasaran(): Builder
    {
        $query = User::query()
            ->role('warga')
            ->where('status', ActiveStatus::Active);

        if ($this->semua_nagari) {
            return $query;
        }

        return $query->whereIn('nagari_id', $this->sasaranNagariIds() ?: [0]);
    }

    /**
     * Pelatihan yang tampil ke warga: menyasar nagarinya dan punya modul berisi materi.
     * Status `terkunci` muncul sebagai "Belum dibuka"; isinya baru boleh dimasuki saat
     * `terbuka`.
     *
     * @param  Builder<Pelatihan>  $query
     * @return Builder<Pelatihan>
     */
    public function scopeAccessibleToWarga(Builder $query, User $user): Builder
    {
        return $query
            ->forNagari((int) $user->nagari_id)
            ->ready();
    }

    /**
     * @param  Builder<Pelatihan>  $query
     * @return Builder<Pelatihan>
     */
    public function scopeTerbuka(Builder $query): Builder
    {
        return $query->where('status', StatusPelatihan::Terbuka);
    }

    /**
     * Pelaksanaan yang menyasar satu nagari. Tidak bergantung akun, sehingga aman
     * dipakai katalog publik yang hanya menampilkan metadata.
     *
     * @param  Builder<Pelatihan>  $query
     * @return Builder<Pelatihan>
     */
    public function scopeForNagari(Builder $query, int $nagariId): Builder
    {
        return $query
            ->where(fn (Builder $scope) => $scope
                ->where('semua_nagari', true)
                ->orWhereHas('nagaris', fn (Builder $nagaris) => $nagaris
                    ->where('nagaris.id', $nagariId)));
    }

    /**
     * @param  Builder<Pelatihan>  $query
     * @return Builder<Pelatihan>
     */
    public function scopeWithPublicAudience(Builder $query): Builder
    {
        return $query
            ->where(fn (Builder $scope) => $scope
                ->where('semua_nagari', true)
                ->orWhereHas('nagaris', fn (Builder $nagaris) => $nagaris
                    ->where('nagaris.status', ActiveStatus::Active)));
    }

    /**
     * Pelaksanaan yang terlihat pada back-office sesuai batas aktor. Superadmin dan
     * DPMD tidak difilter; DPMD tetap read-only lewat Gate. Operator melihat semua
     * yang menyasar nagarinya, tetapi mutasi tetap dibatasi ke yang ia buat. Pengajar
     * melihat yang ia buat atau yang ditugaskan kepadanya.
     *
     * @param  Builder<Pelatihan>  $query
     * @return Builder<Pelatihan>
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isOperator() && $user->nagari_id === null) {
            return $query->whereKey([]);
        }

        return $query
            ->when(
                $user->isOperator(),
                fn (Builder $scope) => $scope->where(fn (Builder $pelatihans) => $pelatihans
                    ->where('created_by', $user->getKey())
                    ->orWhere('semua_nagari', true)
                    ->orWhereHas('nagaris', fn (Builder $nagaris) => $nagaris->whereKey($user->nagari_id))),
            )
            ->when(
                ! $user->isOperator() && $user->isPengajar(),
                fn (Builder $scope) => $scope->where(fn (Builder $pelatihans) => $pelatihans
                    ->where('created_by', $user->getKey())
                    ->orWhereHas('pengajars', fn (Builder $pengajars) => $pengajars->whereKey($user->getKey()))),
            );
    }

    /**
     * Pelaksanaan yang dapat diubah aktor. Dipakai ulang oleh pilihan form dan guard
     * server-side agar request hasil manipulasi tetap ditolak. Memakai TEMA yang sama
     * tidak pernah masuk hitungan di sini.
     *
     * @param  Builder<Pelatihan>  $query
     * @return Builder<Pelatihan>
     */
    public function scopeManageableBy(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        // Operator boleh mengelola pelatihan yang dia buat sendiri.
        if ($user->isPengajar() || $user->isOperator()) {
            return $query->where(fn (Builder $pelatihans) => $pelatihans
                ->where('created_by', $user->getKey())
                ->orWhereHas('pengajars', fn (Builder $pengajars) => $pengajars->whereKey($user->getKey())));
        }

        return $query->whereKey([]);
    }
}
