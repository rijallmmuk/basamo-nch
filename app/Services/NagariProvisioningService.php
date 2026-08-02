<?php

namespace App\Services;

use App\Models\Module;
use App\Models\Nagari;
use App\Models\Pelatihan;
use App\Models\Penduduk;
use App\Models\SdgAchievement;
use App\Models\UmkmProfile;
use App\Models\User;
use App\Support\PhoneNumber;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * Provisioning akun operator nagari + guard dependensi Nagari — diekstrak dari
 * App\Filament\Resources\Nagaris\NagariResource (masih dipakai Filament sbg wrapper
 * tipis) supaya panel /kelola (migrasi superadmin, Fase 1) bisa memakai logika yang
 * sama tanpa bergantung ke namespace Filament.
 */
class NagariProvisioningService
{
    public function __construct(private readonly InitialPasswordService $initialPassword) {}

    /**
     * Tiap nagari otomatis punya satu akun operator (username = kode wilayah). Akun baru
     * memakai password awal operator nagari yang sama dan wajib menggantinya saat login
     * pertama.
     *
     * @param  array<string, mixed>  $formState  field operator_email/operator_kontak mentah
     * @return array{created: bool}
     */
    public function syncOperator(Nagari $nagari, array $formState): array
    {
        $username = $nagari->defaultOperatorUsername();
        $operator = $nagari->operator()->first();

        // Tanpa kode wilayah, akun baru tidak dapat diprovision. Operator lama pada
        // data legacy tetap boleh memperbarui kontak tanpa kehilangan username-nya.
        if ($username === null && ! $operator) {
            return ['created' => false];
        }

        // Nama operator FIX (tak bisa diubah): selalu "Operator {nama nagari}".
        $name = 'Operator '.$nagari->nama_lengkap;
        // Normalisasi ke 62xxx — konsisten dgn No. HP warga (kolom users.phone sama).
        $phone = PhoneNumber::normalize($formState['operator_kontak'] ?? null);
        // Email opsional, selalu huruf kecil — konsisten dgn email warga.
        $email = filled($formState['operator_email'] ?? null) ? Str::lower(trim($formState['operator_email'])) : null;

        if (! $operator) {
            $nagari->users()->create([
                'name' => $name,
                'username' => $username,
                'email' => $email,
                'phone' => $phone,
                'status' => 'active',
                'password' => $this->initialPassword->forRole('operator'),
                'must_change_password' => true,
            ])->assignRole(Role::findOrCreate('operator', 'web'));

            return ['created' => true];
        }

        // Operator sudah ada → perbarui identitas + selaraskan username ke kode terkini.
        // Password tidak disentuh. Invariant: username = kode wilayah.
        $operator->forceFill([
            'name' => $name,
            'username' => $username ?? $operator->username,
            'email' => $email,
            'phone' => $phone,
        ])->save();

        return ['created' => false];
    }

    /** Reset operator nagari ke password awal bersama dan wajibkan penggantian. Null bila belum ada operator. */
    public function resetOperatorInitialPassword(Nagari $nagari): ?User
    {
        $operator = $nagari->operator()->first();

        if (! $operator) {
            return null;
        }

        $this->initialPassword->apply($operator);

        return $operator;
    }

    /** Arsipkan (soft delete) akun operator saat nagarinya diarsipkan. */
    public function archiveOperator(Nagari $nagari): void
    {
        $nagari->operator()->first()?->delete();
    }

    /** Pulihkan akun operator saat nagarinya dipulihkan. */
    public function restoreOperator(Nagari $nagari): void
    {
        $nagari->operator()->onlyTrashed()->first()?->restore();
    }

    /** Hapus permanen akun operator saat nagarinya dihapus permanen. */
    public function forceDeleteOperator(Nagari $nagari): void
    {
        $nagari->operator()->withTrashed()->first()?->forceDelete();
    }

    /**
     * Cegah orphan: nagari yang masih memiliki warga/modul tidak boleh dihapus. Akun
     * operator nagari DIKECUALIKAN — boleh diarsipkan bila hanya operator yang tersisa (admin
     * ikut diarsipkan otomatis via archiveOperator()).
     */
    public function hasBlockingDependents(Nagari $nagari, bool $includeTrashed = false): bool
    {
        // Dependent = akun apa pun selain akun operator nagari (multi-role Spatie).
        $dependents = $nagari->users()->whereDoesntHave('roles', fn ($q) => $q->where('name', 'operator'));
        // Modul yang menyasar nagari ini (via pivot program) — modul tak lagi berkolom nagari_id.
        $modules = Module::query()->forNagari($nagari->id);
        $penduduk = Penduduk::whereBelongsTo($nagari);
        $umkmProfiles = UmkmProfile::whereBelongsTo($nagari);
        $sdgAchievements = SdgAchievement::whereBelongsTo($nagari);
        $pelatihanDiselenggarakan = Pelatihan::whereBelongsTo($nagari);

        // SdgAchievement tanpa soft delete (cermin API) — tak ikut cabang trashed.
        if ($includeTrashed) {
            $dependents->withTrashed();
            $modules->withTrashed();
            $penduduk->withTrashed();
            $umkmProfiles->withTrashed();
            $pelatihanDiselenggarakan->withTrashed();
        }

        return $dependents->exists()
            || $modules->exists()
            || $penduduk->exists()
            || $umkmProfiles->exists()
            || $sdgAchievements->exists()
            || $pelatihanDiselenggarakan->exists();
    }

    /**
     * Kode wilayah nagari terarsip boleh dipakai ulang nagari baru — maka saat
     * dipulihkan, pastikan kodenya belum dipakai nagari AKTIF lain (unik komposit
     * (kode, deleted_at) tidak menahan duplikat aktif di MariaDB; tanpa guard ini bisa
     * lahir 2 nagari aktif berkode sama dengan 2 operator ber-username sama → login ambigu).
     */
    public function hasActiveKodeConflict(Nagari $nagari): bool
    {
        return Nagari::where('wilayah_kode', $nagari->wilayah_kode)
            ->whereKeyNot($nagari->getKey())
            ->exists();
    }
}
