<?php

namespace App\Models;

use App\Enums\ActiveStatus;
use App\Models\Concerns\BelongsToDesa;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'username', 'email', 'phone', 'password', 'must_change_password', 'initial_otp', 'desa_id', 'desa_unit_id', 'role', 'umkm_access_granted_at', 'total_xp', 'status'])]
#[Hidden(['password', 'remember_token', 'initial_otp'])]
class User extends Authenticatable implements FilamentUser, HasMedia
{
    /** @use HasFactory<UserFactory> */
    use BelongsToDesa, HasFactory, HasRoles, InteractsWithMedia, LogsActivity, Notifiable, SoftDeletes;

    /** Audit akun: jangan pernah log password/OTP. */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'username', 'email', 'phone', 'role', 'umkm_access_granted_at', 'desa_id', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('pengguna');
    }

    protected static function booted(): void
    {
        // User mengganti sandi sendiri → hapus OTP awal & lepas flag wajib-ganti.
        // Dikecualikan: pembuatan akun baru, penerbitan OTP (set `initial_otp`),
        // dan set eksplisit `must_change_password` (hormati niat pemanggil).
        static::saving(function (self $user): void {
            if (
                $user->exists
                && $user->isDirty('password')
                && ! $user->isDirty('initial_otp')
                && ! $user->isDirty('must_change_password')
            ) {
                $user->initial_otp = null;
                $user->must_change_password = false;
            }
        });

        // Kolom `role` adalah sumber kebenaran. Saat role berubah, samakan Spatie role
        // agar Shield & cek hasRole() tetap konsisten (tak ada "admin hantu").
        static::saved(function (self $user): void {
            if (! ($user->wasRecentlyCreated || $user->wasChanged('role'))) {
                return;
            }

            if (blank($user->role)) {
                $user->syncRoles([]);

                return;
            }

            if (Role::where('name', $user->role)->where('guard_name', 'web')->exists()) {
                $user->syncRoles([$user->role]);
            }
        });
    }

    /** Foto profil (opsional, diunggah warga sendiri di portal). */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 256, 256)
            ->format('webp')
            ->nonQueued();
    }

    /** URL foto profil (konversi thumb) atau null bila belum ada → fallback inisial. */
    public function avatarUrl(): ?string
    {
        $media = $this->getFirstMedia('avatar');

        return $media ? $media->getUrl('thumb') : null;
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->status === ActiveStatus::Active
            && in_array($this->role, ['super_admin', 'desa_admin'], true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isDesaAdmin(): bool
    {
        return $this->role === 'desa_admin';
    }

    /** Akun portal (warga) yang disediakan admin via NIK + OTP. */
    public function isPortalAccount(): bool
    {
        return $this->role === 'warga';
    }

    /**
     * Kapabilitas UMKM: warga yang diberi akses "Produk Saya" oleh Admin Desa.
     * Ini kemampuan tambahan di atas peran warga, BUKAN peran terpisah.
     */
    public function hasUmkmAccess(): bool
    {
        return $this->umkm_access_granted_at !== null;
    }

    /** Kode OTP 6 digit (sandi sementara awal). */
    public static function generateOtp(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Terbitkan OTP sebagai sandi sementara; login berikutnya wajib ganti. OTP
     * tersimpan (plain) & terlihat tanpa kedaluwarsa hingga sandi diganti — saat
     * itu dihapus otomatis (lihat hook `saving`). Tanpa `$code` → OTP otomatis.
     */
    public function issueOtp(?string $code = null): string
    {
        $otp = filled($code) ? $code : static::generateOtp();

        $this->forceFill([
            'password' => $otp,            // di-hash via cast saat save
            'initial_otp' => $otp,         // disimpan agar admin bisa relay
            'must_change_password' => true,
        ])->save();

        return $otp;
    }

    protected function casts(): array
    {
        return [
            'status' => ActiveStatus::class,
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'umkm_access_granted_at' => 'datetime',
            'total_xp' => 'integer',
        ];
    }

    public function desaUnit(): BelongsTo
    {
        return $this->belongsTo(DesaUnit::class);
    }

    public function umkmProfile(): HasOne
    {
        return $this->hasOne(UmkmProfile::class);
    }

    public function moduleProgress(): HasMany
    {
        return $this->hasMany(UserModuleProgress::class);
    }

    public function quizAttempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function discussions(): HasMany
    {
        return $this->hasMany(Discussion::class);
    }
}
