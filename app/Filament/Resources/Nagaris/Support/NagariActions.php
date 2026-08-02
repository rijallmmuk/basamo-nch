<?php

namespace App\Filament\Resources\Nagaris\Support;

use App\Enums\ActiveStatus;
use App\Filament\Pages\CuacaNagari;
use App\Filament\Pages\StatistikIdm;
use App\Filament\Resources\Nagaris\NagariResource;
use App\Filament\Resources\Penduduks\PendudukResource;
use App\Filament\Resources\SdgAchievements\SdgAchievementResource;
use App\Filament\Resources\UmkmProfiles\UmkmProfileResource;
use App\Models\Nagari;
use App\Services\NagariManagementService;
use App\Services\NagariProvisioningService;
use App\Support\NagariContext;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;

/**
 * Definisi aksi Nagari — SATU sumber kebenaran, dipakai bersama oleh header halaman View
 * dan dropdown "Aksi" per baris di index. Aksi berbasis `$record` (portabel antar konteks
 * tabel/halaman, Filament v5). Peran diatur di tiap aksi: navigasi utk super & dpmd
 * (label "Kelola"/"Lihat"); lifecycle hanya superadmin (dpmd read-only otomatis tersembunyi).
 */
class NagariActions
{
    /**
     * Navigasi kelola/lihat sub-domain, ter-scope ke nagari baris/record itu.
     *
     * @return array<int, Action>
     */
    public static function navigation(): array
    {
        return [
            Action::make('kelolaWarga')
                ->label(fn (): string => self::navLabel('Warga'))
                ->icon('heroicon-o-users')
                ->color('info')
                ->authorize('view')
                ->action(function (Nagari $record) {
                    NagariContext::set(NagariContext::WARGA, $record->getKey());

                    return redirect(PendudukResource::getUrl('index'));
                }),

            Action::make('kelolaUmkm')
                ->label(fn (): string => self::navLabel('UMKM'))
                ->icon('heroicon-o-building-storefront')
                ->color('info')
                ->authorize('view')
                ->action(function (Nagari $record) {
                    NagariContext::set(NagariContext::UMKM_PROFIL, $record->getKey());

                    return redirect(UmkmProfileResource::getUrl('index'));
                }),

            Action::make('kelolaSdgs')
                ->label(fn (): string => self::navLabel('SDGs'))
                ->icon('heroicon-o-globe-asia-australia')
                ->color('info')
                ->authorize('view')
                ->action(fn (Nagari $record) => redirect(SdgAchievementResource::getUrl('index', ['nagari' => $record->getKey()]))),

            Action::make('statistikIdm')
                ->label('Statistik IDM')
                ->icon('heroicon-o-building-library')
                ->color('info')
                ->authorize('view')
                ->action(fn (Nagari $record) => redirect(StatistikIdm::getUrl(['nagari' => $record->getKey()]))),

            Action::make('kelolaCuaca')
                ->label(fn (): string => self::navLabel('Cuaca'))
                ->icon('heroicon-o-cloud')
                ->color('info')
                ->authorize('view')
                ->action(fn (Nagari $record) => redirect(CuacaNagari::getUrl(['nagari' => $record->getKey()]))),
        ];
    }

    /**
     * Aksi lifecycle nagari — SUPERADMIN saja (dpmd read-only: semua tersembunyi).
     *
     * @return array<int, Action>
     */
    public static function lifecycle(): array
    {
        return [
            EditAction::make()->color('warning')
                ->label('Edit Nagari')
                ->icon('heroicon-o-pencil-square'),

            Action::make('nonAktifkan')
                ->label('Non-aktifkan Nagari')
                ->icon('heroicon-o-no-symbol')
                ->color('danger')
                ->authorize('update')
                ->visible(fn (Nagari $record): bool => self::isSuper()
                    && ! $record->trashed()
                    && $record->status === ActiveStatus::Active)
                ->requiresConfirmation()
                ->modalHeading('Non-aktifkan Nagari? (suspend sementara)')
                ->modalDescription('Warga dan operator diblokir login, tetapi nagari TETAP ADA di daftar dan tidak diarsipkan. Balikkan kapan saja lewat "Aktifkan Nagari". Untuk mengarsipkan/mempensiunkan nagari, gunakan Hapus.')
                ->modalIcon('heroicon-o-exclamation-triangle')
                ->modalSubmitActionLabel('Ya, Non-aktifkan')
                ->action(function (Nagari $record): void {
                    app(NagariManagementService::class)->setStatus($record, ActiveStatus::Inactive);
                    Notification::make()->title('Nagari dinonaktifkan')->success()->send();
                }),

            Action::make('aktifkan')
                ->label('Aktifkan Nagari')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->authorize('update')
                ->visible(fn (Nagari $record): bool => self::isSuper()
                    && ! $record->trashed()
                    && $record->status === ActiveStatus::Inactive)
                ->requiresConfirmation()
                ->modalHeading('Aktifkan kembali Nagari?')
                ->modalDescription('Warga dan operator nagari ini akan dapat login dan mengakses sistem kembali.')
                ->modalSubmitActionLabel('Ya, Aktifkan')
                ->action(function (Nagari $record): void {
                    app(NagariManagementService::class)->setStatus($record, ActiveStatus::Active);
                    Notification::make()->title('Nagari kembali aktif')->success()->send();
                }),

            Action::make('resetInitialPasswordOperator')
                ->label('Reset password operator')
                ->icon('heroicon-o-key')
                ->color('danger')
                ->authorize('update')
                ->visible(fn (Nagari $record): bool => self::isSuper() && $record->operator()->exists())
                ->requiresConfirmation()
                ->modalHeading('Reset password operator?')
                ->modalDescription('Password lama tidak berlaku lagi. Operator login memakai password awal bersama untuk operator nagari dan wajib menggantinya.')
                ->modalIcon('heroicon-o-key')
                ->modalSubmitActionLabel('Reset password')
                ->action(fn (Nagari $record) => NagariResource::resetOperatorInitialPassword($record)),

            DeleteAction::make()
                ->modalHeading('Arsipkan Nagari? (pensiun)')
                ->modalDescription('Nagari diarsipkan (soft-delete): keluar dari daftar aktif dan akun operatornya ikut diarsipkan. Warga & operator terblokir login. Dapat dipulihkan lewat Pulihkan. Untuk sekadar suspend sementara tanpa mengarsip, pakai "Non-aktifkan Nagari".')
                ->before(fn (Action $action, Nagari $record) => NagariResource::guardAgainstDependents($record, $action))
                ->after(fn (Nagari $record) => NagariResource::archiveOperator($record)),

            RestoreAction::make()
                ->before(function (Action $action, Nagari $record) {
                    if (app(NagariProvisioningService::class)->hasActiveKodeConflict($record)) {
                        Notification::make()
                            ->title('Gagal memulihkan nagari')
                            ->body('Kode wilayah tersebut sudah digunakan oleh nagari aktif lain.')
                            ->danger()
                            ->send();
                        $action->halt();
                    }
                })
                ->after(fn (Nagari $record) => NagariResource::restoreOperator($record)),

            ForceDeleteAction::make()
                ->before(function (Action $action, Nagari $record) {
                    NagariResource::guardAgainstDependents($record, $action, true);
                    NagariResource::forceDeleteOperator($record);
                }),
        ];
    }

    private static function navLabel(string $sub): string
    {
        return (auth()->user()?->isDpmd() ?? false) ? "Lihat {$sub}" : "Kelola {$sub}";
    }

    private static function isSuper(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }
}
