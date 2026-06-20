<?php

namespace App\Models;

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
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

#[Fillable(['name', 'username', 'email', 'phone', 'password', 'must_change_password', 'initial_otp', 'otp_expires_at', 'nagari_id', 'wilayah_id', 'role', 'umkm_access_granted_at', 'total_xp', 'status'])]
#[Hidden(['password', 'remember_token', 'initial_otp'])]
class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, LogsActivity, Notifiable, SoftDeletes;

    /** Audit akun: jangan pernah log password/OTP. */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'username', 'email', 'phone', 'role', 'umkm_access_granted_at', 'nagari_id', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('pengguna');
    }

    protected static function booted(): void
    {
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

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->status === 'active'
            && in_array($this->role, ['super_admin', 'nagari_admin'], true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    public function isNagariAdmin(): bool
    {
        return $this->role === 'nagari_admin';
    }

    /** Akun portal (warga) yang disediakan admin via NIK + OTP. */
    public function isPortalAccount(): bool
    {
        return $this->role === 'warga';
    }

    /**
     * Kapabilitas UMKM: warga yang diberi akses "Produk Saya" oleh Admin Nagari.
     * Ini kemampuan tambahan di atas peran warga, BUKAN peran terpisah.
     */
    public function hasUmkmAccess(): bool
    {
        return $this->umkm_access_granted_at !== null;
    }

    /** Masa berlaku OTP awal (hari) sebelum dianggap kedaluwarsa. */
    public const OTP_TTL_DAYS = 7;

    /** Kode OTP 6 digit (sandi sementara awal). */
    public static function generateOtp(): string
    {
        return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Terbitkan OTP baru sebagai sandi sementara; login berikutnya wajib ganti.
     * Mengembalikan kode plain agar admin bisa menyampaikannya ke warga.
     */
    public function issueOtp(): string
    {
        $otp = static::generateOtp();

        $this->forceFill([
            'password' => $otp,            // di-hash via cast saat save
            'initial_otp' => $otp,         // disimpan agar admin bisa relay
            'otp_expires_at' => now()->addDays(self::OTP_TTL_DAYS),
            'must_change_password' => true,
        ])->save();

        return $otp;
    }

    /** OTP awal sudah lewat masa berlaku (perlu di-reset admin). */
    public function otpExpired(): bool
    {
        return $this->must_change_password
            && $this->otp_expires_at !== null
            && $this->otp_expires_at->isPast();
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'otp_expires_at' => 'datetime',
            'umkm_access_granted_at' => 'datetime',
            'total_xp' => 'integer',
        ];
    }

    public function nagari(): BelongsTo
    {
        return $this->belongsTo(Nagari::class);
    }

    public function wilayah(): BelongsTo
    {
        return $this->belongsTo(Wilayah::class);
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
