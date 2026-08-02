<?php

namespace App\Filament\Resources\Nagaris;

use App\Filament\Resources\Nagaris\Pages\CreateNagari;
use App\Filament\Resources\Nagaris\Pages\EditNagari;
use App\Filament\Resources\Nagaris\Pages\ListNagaris;
use App\Filament\Resources\Nagaris\Pages\ViewNagari;
use App\Filament\Resources\Nagaris\Schemas\NagariForm;
use App\Filament\Resources\Nagaris\Schemas\NagariInfolist;
use App\Filament\Resources\Nagaris\Tables\NagarisTable;
use App\Models\Module;
use App\Models\Nagari;
use App\Services\Idm\IdmRefreshService;
use App\Services\NagariProvisioningService;
use App\Services\Sdg\SdgRefreshService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class NagariResource extends Resource
{
    protected static ?string $model = Nagari::class;

    // BuildingLibrary (gedung berpilar) — lebih pas utk kantor nagari/nagari drpd
    // BuildingOffice2 (gedung perkantoran modern generik).
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    // Entitas inti grup Nagari; ditempatkan sebelum menu Warga.
    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return 'Nagari';
    }

    public static function getModelLabel(): string
    {
        return 'Nagari';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Nagari';
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        // Operator diizinkan melihat menu Nagari (akan di-redirect ke halaman View nagarinya)
        if ($user?->isOperator()) {
            return true;
        }

        // Halaman lintas-nagari: hanya superadmin (penuh) & dpmd (read-only).
        return (bool) $user?->hasAnyRole(['superadmin', 'dpmd'])
            && parent::canAccess();
    }

    public static function getNavigationUrl(): string
    {
        $user = auth()->user();

        // Operator bypass index tabel, langsung diarahkan ke halaman View nagarinya sendiri
        if ($user?->isOperator() && $user->nagari_id) {
            return static::getUrl('view', ['record' => $user->nagari_id]);
        }

        return parent::getNavigationUrl();
    }

    public static function infolist(Schema $schema): Schema
    {
        return NagariInfolist::configure($schema);
    }

    public static function form(Schema $schema): Schema
    {
        return NagariForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return NagarisTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['operator'])
            ->withCount([
                'warga',
                'umkmProfiles as umkm_count',
                'sdgAchievements as sdg_count',
            ])
            // Modul menyasar nagari lewat pivot program (bukan kolom) → subkueri korelasi.
            ->addSelect(['modul_count' => Module::query()
                ->selectRaw('count(*)')
                ->whereIn('modules.pelatihan_id', fn ($sub) => $sub
                    ->select('pelatihan_id')
                    ->from('pelatihan_nagari')
                    ->whereColumn('pelatihan_nagari.nagari_id', 'nagaris.id'))])
            ->withAvg('sdgAchievements as sdgs_skor', 'persentase')
            ->withoutGlobalScopes([SoftDeletingScope::class]);

        $user = auth()->user();

        return $user?->isOperator()
            ? $query->whereKey($user->nagari_id)
            : $query;
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        $query = parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);

        $user = auth()->user();

        return $user?->isOperator()
            ? $query->whereKey($user->nagari_id)
            : $query;
    }

    /**
     * Cegah orphan: nagari yang masih memiliki pengguna/modul tidak boleh dihapus.
     * `$includeTrashed` dipakai untuk force delete (hard) yang akan men-null-kan FK
     * bahkan untuk pengguna/modul yang sudah di-soft-delete.
     */
    public static function guardAgainstDependents(Nagari $record, Action $action, bool $includeTrashed = false): void
    {
        if (app(NagariProvisioningService::class)->hasBlockingDependents($record, $includeTrashed)) {
            Notification::make()
                ->title('Nagari tidak bisa dihapus')
                ->body('Masih ada warga atau modul yang terhubung. Pindahkan/hapus dulu warganya, atau cukup ubah status nagari menjadi Nonaktif. Akun operator nagari akan ikut diarsipkan jika nagari diarsipkan.')
                ->danger()
                ->send();

            $action->halt();
        }
    }

    /** Arsipkan (soft delete) akun operator saat nagarinya diarsipkan. */
    public static function archiveOperator(Nagari $record): void
    {
        app(NagariProvisioningService::class)->archiveOperator($record);
    }

    /** Pulihkan akun operator saat nagarinya dipulihkan. */
    public static function restoreOperator(Nagari $record): void
    {
        app(NagariProvisioningService::class)->restoreOperator($record);
    }

    /** Hapus permanen akun operator saat nagarinya dihapus permanen. */
    public static function forceDeleteOperator(Nagari $record): void
    {
        app(NagariProvisioningService::class)->forceDeleteOperator($record);
    }

    /**
     * Provisioning akun operator nagari (lihat NagariProvisioningService::syncOperator()).
     *
     * @param  array<string, mixed>  $formState  field `admin_*` (tak dehidrasi)
     * @return array{created: bool}
     */
    public static function syncOperator(Nagari $nagari, array $formState): array
    {
        return app(NagariProvisioningService::class)->syncOperator($nagari, $formState);
    }

    /** Reset operator nagari ke password awal bersama dan wajibkan penggantian. */
    public static function resetOperatorInitialPassword(Nagari $nagari): void
    {
        $admin = app(NagariProvisioningService::class)->resetOperatorInitialPassword($nagari);

        if (! $admin) {
            return;
        }

        Notification::make()
            ->title('Password operator direset')
            ->body("Username: {$admin->username}. Gunakan password awal bersama untuk operator nagari; wajib diganti setelah login.")
            ->success()
            ->persistent()
            ->send();
    }

    /**
     * Beri tahu hasil provisioning akun operator (dipakai Create & Edit Nagari) — selaras
     * dengan notifikasi pembuatan warga di CreatePenduduk.
     *
     * @param  array{created: bool}  $result
     */
    public static function notifyOperatorProvisioned(array $result, Nagari $nagari): void
    {
        if (! $result['created']) {
            return;
        }

        $admin = $nagari->operator()->first();

        Notification::make()
            ->title('Nagari & akun operator dibuat')
            ->body("Username: {$admin->username}. Gunakan password awal bersama untuk operator nagari; wajib diganti setelah login.")
            ->success()
            ->persistent()
            ->send();
    }

    /**
     * Ambil skor SDGs dari Kemendesa segera setelah nagari dibuat (best-effort).
     * BEST-EFFORT & TERISOLASI: nagari sudah tersimpan (transaksi create commit sebelum
     * hook ini), jadi endpoint Kemendesa yang lambat/tak resmi TAK PERNAH membatalkan
     * pembuatan nagari. Kegagalan hanya menghasilkan notifikasi + fallback tombol Perbarui.
     */
    public static function fetchSdgsOnCreate(Nagari $nagari): void
    {
        if (!in_array(request()->getHost(), ['localhost', '127.0.0.1', '::1'])) {
            $hasil = ['status' => 'hostinger_blocked'];
        } else {
            try {
                $hasil = app(SdgRefreshService::class)->refreshNagari($nagari);
            } catch (\Throwable $e) {
                report($e);
                $hasil = ['status' => 'gagal'];
            }
        }

        match ($hasil['status']) {
            'ok' => Notification::make()
                ->title('Skor SDGs diambil')
                ->body('Capaian SDGs 18 poin berhasil ditarik dari Kemendesa untuk '.$nagari->nama.'.')
                ->success()
                ->send(),
            'tanpa_bps' => Notification::make()
                ->title('Skor SDGs belum bisa diambil')
                ->body('Kode BPS wilayah nagari ini belum tersedia, jadi skor SDGs tak dapat ditarik otomatis.')
                ->warning()
                ->persistent()
                ->send(),
            'hostinger_blocked' => Notification::make()
                ->title('Skor SDGs ditunda')
                ->body('Penarikan otomatis SDGs tidak bisa dilakukan di Hostinger. Gunakan menu Ekspor/Impor Kemendesa.')
                ->warning()
                ->send(),
            default => Notification::make()
                ->title('Skor SDGs belum bisa diambil')
                ->body('Server Kemendesa tak merespons. Nagari tetap dibuat, silakan gunakan menu Ekspor/Impor Kemendesa.')
                ->warning()
                ->persistent()
                ->send(),
        };
    }

    /**
     * Ambil status IDM dari Kemendesa segera setelah nagari dibuat (best-effort, terisolasi
     * seperti {@see fetchSdgsOnCreate}). Tahun ditentukan otomatis (terbaru yang tersedia).
     */
    public static function fetchIdmOnCreate(Nagari $nagari): void
    {
        if (!in_array(request()->getHost(), ['localhost', '127.0.0.1', '::1'])) {
            $hasil = ['status' => 'hostinger_blocked'];
        } else {
            try {
                $hasil = app(IdmRefreshService::class)->refreshNagari($nagari);
            } catch (\Throwable $e) {
                report($e);
                $hasil = ['status' => 'gagal'];
            }
        }

        if ($hasil['status'] === 'ok') {
            Notification::make()
                ->title('Status IDM diambil')
                ->body('Status IDM tahun '.$hasil['tahun'].' berhasil ditarik dari Kemendesa untuk '.$nagari->nama.'.')
                ->success()
                ->send();
        } elseif ($hasil['status'] === 'hostinger_blocked') {
            Notification::make()
                ->title('Status IDM ditunda')
                ->body('Penarikan otomatis IDM tidak bisa dilakukan di Hostinger. Gunakan menu Ekspor/Impor Kemendesa.')
                ->warning()
                ->send();
        } elseif ($hasil['status'] === 'gagal') {
            Notification::make()
                ->title('Status IDM belum bisa diambil')
                ->body('Server Kemendesa tak merespons. Nagari tetap dibuat, silakan gunakan menu Ekspor/Impor Kemendesa.')
                ->warning()
                ->persistent()
                ->send();
        }
        // 'tanpa_kode' tak diberitahu: wilayah_kode wajib ada saat create, praktis tak terjadi.
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListNagaris::route('/'),
            'create' => CreateNagari::route('/create'),
            'view' => ViewNagari::route('/{record}'),
            'edit' => EditNagari::route('/{record}/edit'),
        ];
    }
}
