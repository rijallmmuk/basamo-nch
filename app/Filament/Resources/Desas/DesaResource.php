<?php

namespace App\Filament\Resources\Desas;

use App\Filament\Resources\Desas\Pages\CreateDesa;
use App\Filament\Resources\Desas\Pages\EditDesa;
use App\Filament\Resources\Desas\Pages\ListDesas;
use App\Filament\Resources\Desas\Schemas\DesaForm;
use App\Filament\Resources\Desas\Tables\DesasTable;
use App\Models\Desa;
use App\Support\PhoneNumber;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

class DesaResource extends Resource
{
    protected static ?string $model = Desa::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    // Desa = entitas inti super_admin → tampil di tingkat atas navigasi (hero),
    // pintu masuk untuk memantau & mengelola tiap desa.
    protected static ?int $navigationSort = 2;

    public static function getNavigationGroup(): ?string
    {
        return null;
    }

    public static function getModelLabel(): string
    {
        return 'Desa';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Desa';
    }

    public static function form(Schema $schema): Schema
    {
        return DesaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DesasTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['jenisDesa', 'desaAdmin'])
            ->withCount(['warga'])
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    /**
     * Cegah orphan: desa yang masih memiliki pengguna/modul tidak boleh dihapus.
     * `$includeTrashed` dipakai untuk force delete (hard) yang akan men-null-kan FK
     * bahkan untuk pengguna/modul yang sudah di-soft-delete.
     */
    public static function guardAgainstDependents(Desa $record, Action $action, bool $includeTrashed = false): void
    {
        // Akun admin desa dikecualikan — boleh diarsipkan bila hanya admin yang tersisa
        // (admin ikut diarsipkan otomatis). Yang memblokir: warga atau modul.
        $dependents = $record->users()->where('role', '!=', 'desa_admin');
        $modules = $record->modules();

        if ($includeTrashed) {
            $dependents->withTrashed();
            $modules->withTrashed();
        }

        if ($dependents->exists() || $modules->exists()) {
            Notification::make()
                ->title('Desa tidak bisa dihapus')
                ->body('Masih ada warga atau modul yang terhubung. Pindahkan/hapus dulu, atau cukup nonaktifkan status desa. Akun admin desa akan ikut terhapus otomatis.')
                ->danger()
                ->send();

            $action->halt();
        }
    }

    /** Arsipkan (soft delete) akun admin saat desanya diarsipkan. */
    public static function archiveAdmin(Desa $record): void
    {
        $record->desaAdmin()->first()?->delete();
    }

    /** Pulihkan akun admin saat desanya dipulihkan. */
    public static function restoreAdmin(Desa $record): void
    {
        $record->desaAdmin()->onlyTrashed()->first()?->restore();
    }

    /** Hapus permanen akun admin saat desanya dihapus permanen. */
    public static function forceDeleteAdmin(Desa $record): void
    {
        $record->desaAdmin()->withTrashed()->first()?->forceDelete();
    }

    /**
     * Provisioning akun admin desa — paralel 100% dengan akun warga: tiap desa OTOMATIS
     * punya satu akun admin (username = kode nagari, seperti warga ber-NIK). OTP mengikuti
     * model warga: blank → DITUNDA (sandi acak, tanpa OTP), isi → OTP awal. Reset OTP
     * dilakukan lewat aksi "Reset OTP Admin" di tabel Desa (bukan form), persis warga.
     *
     * @param  array<string, mixed>  $formState  field `admin_*` (tak dehidrasi)
     * @return array{created: bool, otp: ?string} created=akun admin baru dibuat;
     *                                            otp=OTP awal yang ditetapkan (null = ditunda).
     */
    public static function syncAdmin(Desa $desa, array $formState): array
    {
        $username = $desa->defaultAdminUsername();

        // Tanpa kode wilayah, username tak bisa diturunkan (mis. data uji) → lewati.
        if ($username === null) {
            return ['created' => false, 'otp' => null];
        }

        // Nama admin FIX (tak bisa diubah): selalu "Admin {nama desa}".
        $name = 'Admin '.$desa->nama_lengkap;
        // Normalisasi ke 62xxx — konsisten dgn No. HP warga (kolom users.phone sama).
        $phone = PhoneNumber::normalize($formState['admin_kontak'] ?? null);
        // Email opsional, selalu huruf kecil — konsisten dgn email warga.
        $email = filled($formState['admin_email'] ?? null) ? Str::lower(trim($formState['admin_email'])) : null;

        $admin = $desa->desaAdmin()->first();

        if (! $admin) {
            // Konsisten dgn WargaProvisioningService: blank → ditunda (sandi acak tak
            // terpakai sampai OTP diterbitkan), isi → OTP awal. TIDAK auto-generate.
            $otp = filled($formState['admin_otp'] ?? null) ? $formState['admin_otp'] : null;

            $desa->users()->create([
                'name' => $name,
                'username' => $username,
                'email' => $email,
                'phone' => $phone,
                'role' => 'desa_admin',
                'status' => 'active',
                'password' => filled($otp) ? $otp : Str::random(40),
                'initial_otp' => $otp,
                'must_change_password' => true,
            ]);

            return ['created' => true, 'otp' => $otp];
        }

        // Admin sudah ada → perbarui identitas + selaraskan username ke kode terkini.
        // OTP via aksi "Reset OTP Admin". Invariant: username = kode nagari.
        $admin->forceFill(['name' => $name, 'username' => $username, 'email' => $email, 'phone' => $phone])->save();

        return ['created' => false, 'otp' => null];
    }

    /**
     * Reset/terbitkan OTP admin desa — dipakai aksi "Reset OTP Admin" di tabel Desa.
     * Blank → 6 digit otomatis; isi → kustom. Identik perilaku aksi "Reset OTP" warga.
     */
    public static function resetAdminOtp(Desa $desa, ?string $code): void
    {
        $admin = $desa->desaAdmin()->first();

        if (! $admin) {
            return;
        }

        $otp = $admin->issueOtp($code);

        Notification::make()
            ->title('OTP baru diterbitkan')
            ->body("Username: {$admin->username} · OTP: {$otp}. Sampaikan ke admin desa.")
            ->success()
            ->persistent()
            ->send();
    }

    /**
     * Beri tahu hasil provisioning akun admin (dipakai Create & Edit Desa) — selaras
     * dengan notifikasi pembuatan warga di CreateUser.
     *
     * @param  array{created: bool, otp: ?string}  $result
     */
    public static function notifyAdminProvisioned(array $result, Desa $desa): void
    {
        if (! $result['created']) {
            return;
        }

        $admin = $desa->desaAdmin()->first();

        if (filled($result['otp'])) {
            Notification::make()
                ->title('Desa & akun admin dibuat — OTP awal')
                ->body("Username: {$admin->username} · OTP: {$result['otp']}. Sampaikan ke admin desa; wajib diganti saat login pertama.")
                ->success()
                ->persistent()
                ->send();

            return;
        }

        Notification::make()
            ->title('Akun admin dibuat — OTP belum diterbitkan')
            ->body("Username: {$admin->username}. Terbitkan OTP lewat aksi \"Reset OTP Admin\" saat admin siap login.")
            ->info()
            ->persistent()
            ->send();
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDesas::route('/'),
            'create' => CreateDesa::route('/create'),
            'edit' => EditDesa::route('/{record}/edit'),
        ];
    }
}
