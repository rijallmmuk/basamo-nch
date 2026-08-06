<?php

namespace App\Models;

use App\Enums\ActiveStatus;
use App\Models\Concerns\BelongsToNagari;
use App\Models\Concerns\RedactsSensitiveActivityProperties;
use App\Providers\AppServiceProvider;
use App\Support\NagariContext;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasRoles;

// Demografi (tempat/tanggal lahir, jenis_kelamin, agama/status/pekerjaan) kini di `penduduk`.
#[Fillable(['name', 'nik', 'penduduk_id', 'username', 'email', 'phone', 'lembaga', 'password', 'must_change_password', 'nagari_id', 'umkm_access_granted_at', 'status'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasMedia
{
    use BelongsToNagari, HasRoles, InteractsWithMedia, LogsActivity, Notifiable, RedactsSensitiveActivityProperties, SoftDeletes;
    use HasFactory;

    /** Audit akun: jangan pernah log password. */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'nik', 'username', 'email', 'phone', 'umkm_access_granted_at', 'nagari_id', 'status'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('pengguna');
    }

    protected function sensitiveActivityAttributes(): array
    {
        return ['name', 'nik', 'username', 'email', 'phone'];
    }

    protected static function booted(): void
    {
        // User mengganti sandi sendiri → lepas flag wajib-ganti. Reset ke password
        // awal mengubah flag secara eksplisit, sehingga tetap terkunci setelah login.
        static::saving(function (self $user): void {
            if (
                $user->exists
                && $user->isDirty('password')
                && ! $user->isDirty('must_change_password')
            ) {
                $user->must_change_password = false;
            }
        });

        // Hapus permanen akun → hapus lapak UMKM-nya lewat Eloquent. Cascade DB pada
        // umkm_profiles.user_id melewati event model, sehingga foto produk bisa yatim.
        static::forceDeleting(function (self $user): void {
            $user->umkmProfile()->withTrashed()->first()?->forceDelete();
        });

        // Arsipkan warga pemilik UMKM → lapaknya ikut nonaktif (keluar dari katalog
        // publik — jangan ada konten publik tanpa pengelola); pulihkan → lapak aktif
        // lagi HANYA bila akses UMKM-nya masih ada (akses yang dicabut tetap dicabut).
        static::deleted(function (self $user): void {
            if (! $user->isForceDeleting()) {
                $user->umkmProfile()->first()?->update(['status' => ActiveStatus::Inactive]);
            }
        });

        static::restored(function (self $user): void {
            if ($user->hasUmkmAccess()) {
                $user->umkmProfile()->first()?->update(['status' => ActiveStatus::Active]);
            }
        });

        // Sinkronkan status lapak dengan kelayakan tayang pemiliknya: saat status akun
        // berubah (nonaktif = tak ada yang melayani) ATAU akses UMKM dicabut/diberi.
        // Jangan ada konten publik tanpa pengelola.
        static::saved(function (self $user): void {
            // (wasRecentlyCreated TIDAK dipakai: nilainya tetap true seumur instance,
            // jadi salah men-skip update pada instance yang baru dibuat lalu diubah di
            // request yang sama. Saat create, lapak belum ada → null-check di bawah.)
            if (! $user->wasChanged('status') && ! $user->wasChanged('umkm_access_granted_at')) {
                return;
            }

            $lapak = $user->umkmProfile()->first();

            if ($lapak === null) {
                return;
            }

            $lapak->update(['status' => $user->status === ActiveStatus::Active && $user->hasUmkmAccess()
                ? ActiveStatus::Active
                : ActiveStatus::Inactive,
            ]);
        });
    }

    /** Foto profil (opsional, diunggah warga sendiri di portal). */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->useDisk(config('media-library.disk_name'))
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // 160px = 2× tampilan avatar terbesar (80px di profil/podium) → tajam di layar
        // retina HP dengan ukuran minimal.
        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 160, 160)
            ->format('webp')
            ;
    }

    /** URL foto profil (konversi thumb) atau null bila belum ada → fallback inisial. */
    public function avatarUrl(): ?string
    {
        $media = $this->getFirstMedia('avatar');

        if (! $media) {
            return null;
        }

        $url = $media->getAvailableUrl(['thumb']);

        return ! empty($url) ? $url : $media->getUrl();
    }

    /**
     * Panel Filament `/panel` = satu-satunya back-office (pivot all-in Filament).
     * Peran back-office masuk; tiap Resource menjaga scoping-nya sendiri
     * (operator: per-nagari via getEloquentQuery; pengajar: program ditugaskan;
     * dpmd: read-only lintas-nagari via {@see AppServiceProvider}
     * Gate::before). Warga berakses UMKM hanya mendapat self-service lapaknya sendiri.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->status !== ActiveStatus::Active) {
            return false;
        }

        // Operator tanpa tenant tidak boleh jatuh ke query tanpa cakupan nagari.
        // Form normal selalu mewajibkan relasi ini, tetapi pemeriksaan panel tetap
        // harus aman bila data diimpor atau disunting langsung di basis data.
        if ($this->isOperator() && ! $this->isLintasNagari() && $this->nagari_id === null) {
            return false;
        }

        // Nagari nonaktif = seluruh operator nagarinya dibekukan.
        if ($this->nagari_id && $this->nagari?->status !== ActiveStatus::Active) {
            return false;
        }

        return $this->hasAnyRole(['superadmin', 'operator', 'pengajar', 'dpmd'])
            || $this->hasUmkmAccess();
    }

    /**
     * Peran yang wewenangnya melampaui satu nagari, sehingga berhak membuka situs
     * nagari mana pun.
     *
     * Operator SENGAJA TIDAK termasuk: meski ia peran back-office, cakupannya satu
     * nagari (lihat ScopedToNagari yang memulangkan `nagari_id` untuk operator).
     * Memasukkannya ke sini akan membocorkan batas antar situs nagari.
     */
    public function isLintasNagari(): bool
    {
        return $this->hasAnyRole(['superadmin', 'dpmd', 'pengajar']);
    }

    /**
     * Boleh membuka area terautentikasi di situs nagari tertentu?
     *
     * Akun boleh multi-peran, jadi satu peran lintas nagari saja sudah cukup untuk
     * membuka semuanya.
     */
    public function bolehMasukSitusNagari(Nagari $nagari): bool
    {
        return $this->isLintasNagari() || $this->nagari_id === $nagari->getKey();
    }

    /**
     * Prioritas peran untuk kebutuhan "satu label/tujuan" (redirect login, label
     * tampilan) — akun boleh multi-role, tapi beberapa alur butuh satu peran utama.
     * Urutan: paling menentukan area kerja lebih dulu. Akses UMKM TIDAK ada di sini
     * karena ia kapabilitas, bukan peran (lihat {@see hasUmkmAccess()}).
     *
     * @var list<string>
     */
    public const ROLE_PRIORITY = ['superadmin', 'operator', 'dpmd', 'pengajar', 'warga'];

    /**
     * Nama role yang DIPENSIUNKAN dan haram dipakai ulang, ditegakkan di
     * {@see AppServiceProvider} lewat hook Role::creating/updating.
     *
     * `umkm` dulunya role, sejak 2026-07-29 menjadi kapabilitas yang dibaca dari
     * `umkm_access_granted_at` (lihat {@see hasUmkmAccess()}). Membuatnya kembali
     * berbahaya karena menyesatkan: namanya menjanjikan akses UMKM, padahal tak
     * ada satu pun kode yang membacanya, dan justru diperlakukan sebagai role
     * custom yang MEMBATASI akses lewat allow-list permission di Gate::before.
     *
     * @var list<string>
     */
    public const RETIRED_ROLES = ['umkm'];

    /** Peran utama akun (lihat ROLE_PRIORITY); null bila belum diberi peran apa pun. */
    public function primaryRole(): ?string
    {
        $names = $this->getRoleNames();

        foreach (self::ROLE_PRIORITY as $role) {
            if ($names->contains($role)) {
                return $role;
            }
        }

        return null;
    }

    /**
     * Override scope `role()` Spatie: semantik sama (filter pemegang peran via
     * model_has_roles) tapi TIDAK melempar RoleDoesNotExist saat record perannya
     * belum ada — dasbor/daftar/relasi (mis. Nagari::warga()) tak boleh 500
     * hanya karena belum ada satu pun pemegang peran tersebut.
     *
     * @param  Builder<User>  $query
     * @param  string|list<string>  $roles
     * @return Builder<User>
     */
    public function scopeRole(Builder $query, string|array $roles, ?string $guard = null): Builder
    {
        return $query->whereHas('roles', function ($q) use ($roles, $guard): void {
            $q->whereIn('name', (array) $roles)->where('guard_name', $guard ?? 'web');
        });
    }

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('superadmin');
    }

    public function isOperator(): bool
    {
        return $this->hasRole('operator');
    }

    public function isPengajar(): bool
    {
        return $this->hasRole('pengajar');
    }

    public function isDpmd(): bool
    {
        return $this->hasRole('dpmd');
    }

    /** Pemilik UMKM yang memakai Filament hanya untuk lapak miliknya sendiri. */
    public function usesUmkmSelfService(): bool
    {
        return $this->hasUmkmAccess()
            // Pengajar hanya menangani SLC dan tidak mempunyai jalur UMKM, meski
            // data lama pada akunnya masih memuat kapabilitas UMKM.
            && ! $this->hasAnyRole(['superadmin', 'operator', 'pengajar', 'dpmd']);
    }

    /**
     * Nagari yang sedang dikelola admin ini: operator → SELALU nagarinya sendiri
     * (namespace diabaikan). super admin → nagari konteks MENU tsb (lihat {@see
     * NagariContext} — satu key per menu, saling independen sejak 2026-07-14).
     * $namespace null (dipakai SDGs/Cuaca/widget dashboard, yang punya
     * pemilihan nagari sendiri-sendiri di luar NagariContext) → selalu null utk
     * super admin, TIDAK pernah membaca konteks menu manapun.
     */
    public function managedNagariId(?string $namespace = null): ?int
    {
        if ($this->isOperator()) {
            return $this->nagari_id;
        }

        if ($namespace === NagariContext::LMS_REKAP
            && $this->hasAnyRole(['superadmin', 'dpmd', 'pengajar'])) {
            return NagariContext::id($namespace);
        }

        if (in_array($namespace, [NagariContext::UMKM_PROFIL, NagariContext::UMKM_PRODUK], true)
            && $this->hasAnyRole(['superadmin', 'dpmd'])) {
            return NagariContext::id($namespace);
        }

        if ($this->isSuperAdmin() && $namespace !== null) {
            return NagariContext::id($namespace);
        }

        return null;
    }

    /** Pelaksanaan pelatihan yang ditugaskan ke pengajar ini (banyak-ke-banyak). */
    public function pelatihansDiajar(): BelongsToMany
    {
        return $this->belongsToMany(Pelatihan::class, 'pelatihan_pengajar')->withTimestamps();
    }

    /** Akun portal warga yang disediakan admin dan login memakai NIK. */
    public function isPortalAccount(): bool
    {
        return $this->hasRole('warga');
    }

    /**
     * Warga (termasuk terarsip) yang boleh dikelola $admin: superadmin lintas-nagari,
     * operator hanya nagarinya sendiri. SATU-SATUNYA sumber lookup warga untuk
     * aksi admin (dipakai WargaController & Livewire WargaTable) — jangan tulis ulang
     * scoping ini inline; divergensi di sini = bocor data lintas-nagari.
     *
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeWargaDikelola(Builder $query, User $admin): Builder
    {
        return $query->withTrashed()
            ->role('warga')
            ->unless($admin->isSuperAdmin(), fn (Builder $q) => $q->forNagari($admin->nagari_id));
    }

    /**
     * Kapabilitas UMKM: warga yang diberi akses mengelola lapak oleh Operator Nagari.
     * Ini kemampuan tambahan di atas peran warga, BUKAN peran terpisah — karena itu
     * dibaca LANGSUNG dari `umkm_access_granted_at`, satu-satunya sumber kebenaran.
     * Jangan pernah mencerminkannya ke sebuah role: salinan itu selalu punya jendela
     * basi terhadap kolomnya.
     */
    public function hasUmkmAccess(): bool
    {
        return $this->umkm_access_granted_at !== null;
    }

    protected function casts(): array
    {
        return [
            'status' => ActiveStatus::class,
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'umkm_access_granted_at' => 'datetime',
            'kontak_masuk_seen_at' => 'datetime',
        ];
    }

    /** Identitas kependudukan pemilik akun (NIK, demografi). */
    public function penduduk(): BelongsTo
    {
        return $this->belongsTo(Penduduk::class)->withTrashed();
    }

    public function umkmProfile(): HasOne
    {
        return $this->hasOne(UmkmProfile::class);
    }

    public function moduleProgress(): HasMany
    {
        return $this->hasMany(UserModuleProgress::class);
    }

    public function evaluasiPercobaans(): HasMany
    {
        return $this->hasMany(EvaluasiPercobaan::class);
    }

    public function discussions(): HasMany
    {
        return $this->hasMany(Discussion::class);
    }
}
