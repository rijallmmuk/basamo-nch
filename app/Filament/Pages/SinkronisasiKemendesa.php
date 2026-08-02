<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasPanelBreadcrumbs;
use App\Services\KemendesaSyncService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Storage;

class SinkronisasiKemendesa extends Page
{
    use HasPanelBreadcrumbs;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationLabel = 'Ekspor/Impor Kemendesa';

    protected static ?int $navigationSort = 4;

    protected string $view = 'filament.pages.sinkronisasi-kemendesa';

    public static function getNavigationGroup(): ?string
    {
        return 'Status Desa';
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isSuperAdmin();
    }

    public function getTitle(): string
    {
        return 'Sinkronisasi Data Kemendesa';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('ekspor')
                ->label('Ekspor Data (Localhost)')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('primary')
                ->action(function () {
                    $data = app(KemendesaSyncService::class)->exportData();
                    $json = json_encode($data, JSON_PRETTY_PRINT);
                    
                    return response()->streamDownload(function () use ($json) {
                        echo $json;
                    }, 'kemendesa_sync_' . date('Y-m-d_His') . '.json');
                }),

            Action::make('impor')
                ->label('Impor Data (Hostinger)')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Impor Data Kemendesa')
                ->modalDescription('Unggah file JSON hasil ekspor dari localhost. Ini akan menimpa data IDM dan SDGs yang ada dengan data dari file JSON tersebut tanpa menghapus artikel atau pengguna di Hostinger.')
                ->modalSubmitActionLabel('Mulai Impor')
                ->schema([
                    FileUpload::make('berkas')
                        ->label('File kemendesa_sync.json')
                        ->acceptedFileTypes(['application/json'])
                        ->disk('local')
                        ->directory('kemendesa-sync')
                        ->visibility('private')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    @set_time_limit(0);
                    
                    $path = Storage::disk('local')->path($data['berkas']);
                    $json = file_get_contents($path);
                    $parsed = json_decode($json, true);
                    
                    Storage::disk('local')->delete($data['berkas']);

                    if (!is_array($parsed) || (!empty($parsed) && !isset($parsed[0]['wilayah_kode']))) {
                        Notification::make()
                            ->title('Gagal mengimpor')
                            ->body('Format file JSON tidak valid. Pastikan Anda melakukan Ekspor ulang dari Localhost setelah pembaruan terakhir.')
                            ->danger()
                            ->send();
                        return;
                    }

                    try {
                        app(KemendesaSyncService::class)->importData($parsed);
                    } catch (\Throwable $e) {
                        Notification::make()
                            ->title('Terjadi kesalahan saat impor')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                        return;
                    }

                    Notification::make()
                        ->title('Impor Berhasil')
                        ->body('Data IDM dan SDGs berhasil diperbarui.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
