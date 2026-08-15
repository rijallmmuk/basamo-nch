<?php

namespace App\Models;

use App\Enums\ActiveStatus;
use App\Enums\JenisEvaluasi;
use App\Observers\ModuleObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * Modul SLC = satu "buku ajar" DI DALAM sebuah pelaksanaan pelatihan. Setiap modul
 * wajib punya induk; konsep "Modul Umum" yang berdiri sendiri sudah dihapus.
 *
 * SASARAN modul SELALU sama dengan audiens pelaksanaannya ({@see Pelatihan::nagaris()}
 * atau `semua_nagari`). Tidak ada penyempitan per modul: satu sumber kebenaran saja.
 *
 * SATU gerbang akses saja: status pelaksanaan ({@see StatusPelatihan}). Modul tanpa materi
 * otomatis tersembunyi lewat {@see scopeReady()}, jadi modul tidak punya saklar
 * status tersendiri.
 */
#[ObservedBy([ModuleObserver::class])]
class Module extends Model implements HasMedia
{
    use HasFactory;
    use HasSlug, InteractsWithMedia, LogsActivity;
    use SoftDeletes {
        forceDelete as private forceDeleteModel;
        restore as private restoreModel;
    }

    protected $attributes = [
        'urutan' => 0,
    ];

    protected $fillable = [
        'pelatihan_id', 'judul', 'slug', 'deskripsi',
        'urutan', 'prasyarat_module_id', 'created_by',
    ];

    protected static function booted(): void
    {
        // Hapus permanen modul → bersihkan file tiap materi lewat model (cascade DB
        // tak memicu event Materi::deleted).
        static::forceDeleting(function (self $module): void {
            $module->materis()->eachById(fn (Materi $materi) => $materi->delete());
            $module->evaluasis()
                ->withTrashed()
                ->eachById(fn (Evaluasi $evaluasi) => $evaluasi->forceDelete());
            $module->discussions()
                ->withTrashed()
                ->eachById(fn (Discussion $discussion) => $discussion->forceDelete());
        });

        // Hapus (soft) modul → evaluasi & diskusi modulnya ikut disampah (dapat dipulihkan
        // bersama saat modul di-restore). Materi tanpa soft-delete: tetap ada tapi
        // tersembunyi bersama modulnya, dihapus permanen saat modul di-force-delete.
        static::deleting(function (self $module): void {
            if ($module->isForceDeleting()) {
                return;
            }

            $cascadeAt = now();

            $module->evaluasis()->eachById(function (Evaluasi $evaluasi) use ($cascadeAt): void {
                $evaluasi->forceFill(['cascade_deleted_at' => $cascadeAt])->saveQuietly();
                $evaluasi->delete();
            });
            $module->discussions()->eachById(function (Discussion $discussion) use ($cascadeAt): void {
                $discussion->forceFill(['cascade_deleted_at' => $cascadeAt])->saveQuietly();
                $discussion->delete();
            });
        });

        static::restoring(function (self $module): void {
            $module->evaluasis()
                ->onlyTrashed()
                ->whereNotNull('cascade_deleted_at')
                ->eachById(function (Evaluasi $evaluasi): void {
                    $evaluasi->restore();
                    $evaluasi->forceFill(['cascade_deleted_at' => null])->saveQuietly();
                });
            $module->discussions()
                ->onlyTrashed()
                ->whereNotNull('cascade_deleted_at')
                ->eachById(function (Discussion $discussion): void {
                    $discussion->restore();
                    $discussion->forceFill(['cascade_deleted_at' => null])->saveQuietly();
                });
        });
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['judul', 'pelatihan_id', 'urutan', 'prasyarat_module_id'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('modul');
    }

    protected function casts(): array
    {
        return [
            'urutan' => 'integer',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')
            ->useDisk(config('media-library.disk_name'))
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // Optimasi berat halaman: crop 4:3 + format webp. nonQueued = langsung jadi.
        $this->addMediaConversion('card')
            ->fit(Fit::Crop, 800, 600)
            ->format('webp')
            ;
    }

    /** Ada cover unggahan? Bila tidak, tampilan memakai {@see components/slc/module-cover}. */
    public function punyaCover(): bool
    {
        return $this->getFirstMedia('cover') !== null;
    }

    /** URL cover unggahan (konversi 'card'); null bila belum ada. */
    public function coverUrl(): ?string
    {
        $media = $this->getFirstMedia('cover');

        if (! $media) {
            return null;
        }

        $url = $media->getAvailableUrl(['card']);

        return ! empty($url) ? $url : $media->getUrl();
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('judul')
            ->saveSlugsTo('slug')
            // Slug stabil (tak berubah saat judul diedit) & unik GLOBAL — satu pelaksanaan
            // bisa multi-nagari sehingga ruang slug tak bisa di-scope per nagari.
            ->doNotGenerateSlugsOnUpdate();
    }

    /**
     * Resolusi route binding portal `{module:slug}`. Slug unik global → satu modul.
     * Batasi ke modul yang menyasar nagari warga (mencegah akses lintas-nagari lewat
     * URL langsung).
     */
    public function resolveRouteBinding($value, $field = null): ?Model
    {
        if ($field !== 'slug') {
            return parent::resolveRouteBinding($value, $field);
        }

        $user = auth()->user();

        // Akun back-office dapat membuka/mempratinjau modul apa pun tanpa scope nagari warga.
        if ($user && $user->hasAnyRole(['superadmin', 'operator', 'pengajar', 'dpmd'])) {
            return $this->newQuery()->where($field, $value)->first();
        }

        return $this->newQuery()
            ->where($field, $value)
            ->when(
                $user,
                fn (Builder $q) => $q->visibleToWarga($user),
                fn (Builder $q) => $q->dariPelatihanAktif(),
            )
            ->first();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function prerequisite(): BelongsTo
    {
        return $this->belongsTo(Module::class, 'prasyarat_module_id');
    }

    /** Pelaksanaan pelatihan induk (sumber tema, sasaran nagari, dan gerbang akses). */
    public function pelatihan(): BelongsTo
    {
        return $this->belongsTo(Pelatihan::class, 'pelatihan_id');
    }

    public function materis(): HasMany
    {
        return $this->hasMany(Materi::class)->orderBy('urutan')->orderBy('id');
    }

    public function evaluasis(): HasMany
    {
        return $this->hasMany(Evaluasi::class);
    }

    /** Pre-test opsional: gerbang sebelum materi terbuka. */
    public function pretest(): HasOne
    {
        return $this->hasOne(Evaluasi::class)->where('jenis', JenisEvaluasi::Pretest);
    }

    /** Evaluasi Kegiatan: penutup modul (dulu bernama "kuis"). */
    public function evaluasiKegiatan(): HasOne
    {
        return $this->hasOne(Evaluasi::class)->where('jenis', JenisEvaluasi::Kegiatan);
    }

    public function discussions(): HasMany
    {
        return $this->hasMany(Discussion::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(UserModuleProgress::class);
    }

    /** Modul mewajibkan pre-test dikerjakan sebelum materi dibuka. */
    public function memakaiPretest(): bool
    {
        return $this->pretest()->exists();
    }

    /**
     * Id nagari audiens modul = audiens pelaksanaannya. null berarti seluruh nagari
     * (dinamis); array kosong berarti modul tanpa induk, jadi tanpa audiens.
     */
    public function sasaranNagariIdsEfektif(): ?array
    {
        $this->loadMissing('pelatihan');

        // Bedakan dua hal: pelaksanaan yang menyasar SELURUH nagari mengembalikan
        // null, sedangkan modul tanpa induk berarti tanpa audiens sama sekali.
        if (! $this->pelatihan) {
            return [];
        }

        return $this->pelatihan->sasaranNagariIds();
    }

    /**
     * Prasyarat wajib berada di pelaksanaan yang sama. Karena audiens modul selalu
     * sama dengan audiens pelaksanaannya, syarat itu sekaligus menjamin prasyarat
     * pasti tampil di layar warga yang sama.
     */
    public function validatePrasyarat(?int $prasyaratId): void
    {
        if ($prasyaratId === null) {
            return;
        }

        if ($this->exists && $prasyaratId === $this->getKey()) {
            throw ValidationException::withMessages([
                'prasyarat_module_id' => 'Modul tidak boleh menjadi prasyarat bagi dirinya sendiri.',
            ]);
        }

        $prasyarat = static::query()->find($prasyaratId);

        if (! $prasyarat || $prasyarat->pelatihan_id !== $this->pelatihan_id) {
            throw ValidationException::withMessages([
                'prasyarat_module_id' => 'Prasyarat harus modul lain dalam pelatihan yang sama.',
            ]);
        }
    }

    /**
     * Batasi query ke modul yang menyasar satu nagari, lewat audiens pelaksanaan
     * induknya.
     *
     * @param  Builder<Module>  $query
     */
    public function scopeForNagari(Builder $query, int $nagariId): void
    {
        $query->whereHas('pelatihan', fn (Builder $pelatihan) => $pelatihan->forNagari($nagariId));
    }

    /**
     * Modul berisi materi yang audiens pelaksanaannya nyata (nagari aktif).
     * Untuk katalog publik global.
     *
     * @param  Builder<Module>  $query
     * @return Builder<Module>
     */
    public function scopePubliclyVisible(Builder $query): Builder
    {
        return $query
            ->ready()
            ->whereHas('pelatihan', fn (Builder $pelatihan) => $pelatihan->withPublicAudience());
    }

    /**
     * Hanya modul yang pelaksanaannya masih ada (tidak dihapus/terarsip). Menghapus
     * pelaksanaan = soft-delete → relasi mengecualikannya → seluruh modulnya lenyap
     * dari portal.
     *
     * @param  Builder<Module>  $query
     */
    public function scopeDariPelatihanAktif(Builder $query): void
    {
        $query->whereHas('pelatihan');
    }

    /**
     * Modul yang sah DIAKSES warga: tampil DAN pelaksanaannya berstatus terbuka.
     * Prasyarat modul dan gerbang pre-test dicek terpisah di service.
     *
     * @param  Builder<Module>  $query
     * @return Builder<Module>
     */
    public function scopeAccessibleToWarga(Builder $query, User $user): Builder
    {
        return $query
            ->ready()
            ->forNagari((int) $user->nagari_id)
            ->whereHas('pelatihan', fn (Builder $pelatihan) => $pelatihan->terbuka());
    }

    /**
     * Modul yang TAMPIL di portal warga: berisi materi dan menyasar nagarinya,
     * TERMASUK pelaksanaan yang masih terkunci (ditampilkan sebagai "Belum dibuka").
     * Gerbang membuka isinya tetap {@see accessibleToWarga}.
     *
     * @param  Builder<Module>  $query
     * @return Builder<Module>
     */
    public function scopeVisibleToWarga(Builder $query, User $user): Builder
    {
        return $query
            ->ready()
            ->forNagari((int) $user->nagari_id);
    }

    /**
     * GERBANG AKSES: bolehkah warga MEMBUKA isi modul ini? Butuh modul tampil DAN
     * pelaksanaan terbuka. Bandingkan {@see isVisibleToWarga} yang hanya soal TAMPIL.
     */
    public function isAccessibleToWarga(User $user): bool
    {
        if ($user->hasAnyRole(['superadmin', 'operator', 'pengajar', 'dpmd'])) {
            return true;
        }

        return static::query()->accessibleToWarga($user)->whereKey($this->getKey())->exists();
    }

    /**
     * KETAMPAKAN saja. Untuk memutuskan boleh-buka isi, pakai {@see isAccessibleToWarga}.
     */
    public function isVisibleToWarga(User $user): bool
    {
        return static::query()->visibleToWarga($user)->whereKey($this->getKey())->exists();
    }

    /**
     * Modul siap tayang: memiliki minimal 1 materi.
     *
     * @param  Builder<Module>  $query
     */
    public function scopeReady(Builder $query): void
    {
        $query->whereHas('materis');
    }

    public function isReady(): bool
    {
        if (! $this->exists) {
            return false;
        }

        if ($this->relationLoaded('materis')) {
            return $this->materis->isNotEmpty();
        }

        return $this->materis()->exists();
    }

    /** @param Builder<Module> $query @return Builder<Module> */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin() || $user->isDpmd()) {
            return $query;
        }

        if ($user->isOperator() && $user->nagari_id === null) {
            return $query->whereKey([]);
        }

        if ($user->isOperator() || $user->isPengajar()) {
            return $query->whereHas('pelatihan', fn (Builder $pelatihan) => $pelatihan->visibleTo($user));
        }

        return $query->whereKey([]);
    }

    /** @param Builder<Module> $query @return Builder<Module> */
    public function scopeManageableBy(Builder $query, User $user): Builder
    {
        if ($user->isSuperAdmin()) {
            return $query;
        }

        if ($user->isPengajar() || $user->isOperator()) {
            return $query->whereHas('pelatihan', fn (Builder $pelatihan) => $pelatihan->manageableBy($user));
        }

        return $query->whereKey([]);
    }

    /** Modul sudah mempunyai aktivitas warga yang melarang hapus permanen. */
    public function hasLearningActivity(): bool
    {
        return $this->progress()->exists()
            || $this->evaluasis()->withTrashed()->whereHas('percobaans')->exists()
            || $this->discussions()->exists();
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

    /** Query warga aktif yang menjadi sasaran EFEKTIF modul ini. */
    public function wargaSasaran(): Builder
    {
        $nagariIds = $this->sasaranNagariIdsEfektif();

        $query = User::query()
            ->role('warga')
            ->where('status', ActiveStatus::Active);

        if ($nagariIds === null) {
            return $query;
        }

        return $query->whereIn('nagari_id', $nagariIds ?: [0]);
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Materi selalu dicari dengan ID (route key-nya memang ID). Jangan pernah ikut
     * mencocokkan `urutan`: nomor urut berubah saat materi diurut ulang sehingga
     * satu URL bisa membuka materi lain.
     */
    public function resolveChildRouteBinding($childType, $value, $field)
    {
        if (in_array($childType, ['materi', 'materis', 'page', 'pages'], true)) {
            return is_numeric($value)
                ? $this->materis()->whereKey((int) $value)->first()
                : null;
        }

        return parent::resolveChildRouteBinding($childType, $value, $field);
    }
}
