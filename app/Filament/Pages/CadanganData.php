<?php

namespace App\Filament\Pages;

use App\Filament\Concerns\HasPanelBreadcrumbs;
use App\Services\BackupRestoreService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

/**
 * Pencadangan dan pemulihan basis data beserta seluruh berkas tersimpan
 * (materi SLC, media UMKM). SUPERADMIN SAJA: pemulihan menimpa seluruh isi
 * aplikasi dan tidak dapat dibatalkan.
 */
class CadanganData extends Page
{
    use HasPanelBreadcrumbs;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static ?string $navigationLabel = 'Cadangan Data';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.cadangan-data';

    public static function getNavigationGroup(): ?string
    {
        return 'Sistem';
    }

    public static function canAccess(): bool
    {
        return (bool) auth()->user()?->isSuperAdmin();
    }

    public function getTitle(): string
    {
        return 'Cadangan Data';
    }

    /** @return list<array{path: string, nama: string, ukuran: int, dibuat: Carbon}> */
    public function getCadanganProperty(): array
    {
        try {
            return app(BackupRestoreService::class)->daftar();
        } catch (Throwable $e) {
            return [];
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('buat')
                ->label('Buat Cadangan Sekarang')
                ->icon('heroicon-o-plus-circle')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Buat cadangan baru?')
                ->modalDescription('Seluruh basis data dan berkas tersimpan disalin ke satu arsip. Pada data yang besar prosesnya dapat berjalan beberapa menit; jangan tutup halaman ini.')
                ->modalSubmitActionLabel('Buat Cadangan')
                ->action(function (): void {
                    @set_time_limit(0);

                    try {
                        app(BackupRestoreService::class)->buat();
                    } catch (Throwable $e) {
                        $this->beriTahu('Cadangan gagal dibuat', $e->getMessage(), 'danger');

                        return;
                    }

                    $this->beriTahu('Cadangan berhasil dibuat', 'Arsip terbaru sudah tersedia di daftar.', 'success');
                }),

            // Tanpa ini, arsip yang sudah diunduh ke komputer tidak berguna saat
            // servernya hilang: tidak ada jalan memasukkannya kembali.
            Action::make('pulihkanUnggahan')
                ->label('Pulihkan dari Berkas')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('danger')
                ->modalHeading('Pulihkan dari berkas yang Anda unggah')
                ->modalDescription('Terima arsip cadangan (.zip) maupun dump basis data (.sql atau .sql.gz). Isi yang ada sekarang akan ditimpa dan seluruh pengguna keluar dari sesinya. Satu cadangan pengaman dibuat lebih dulu.')
                ->modalSubmitActionLabel('Pulihkan Sekarang')
                ->modalIcon('heroicon-o-exclamation-triangle')
                ->modalIconColor('danger')
                ->modalCloseButton(false)
                ->closeModalByClickingAway(false)
                ->closeModalByEscaping(false)
                ->schema([
                    FileUpload::make('berkas')
                        ->label('Berkas cadangan')
                        ->acceptedFileTypes([
                            'application/zip',
                            'application/x-zip-compressed',
                            'application/gzip',
                            'application/sql',
                            'text/plain',
                            'application/octet-stream',
                        ])
                        ->disk('local')
                        ->directory('restore-uploads')
                        ->visibility('private')
                        ->maxSize(1048576)
                        ->required()
                        ->helperText('Arsip .zip, atau dump .sql maupun .sql.gz.'),

                    TextInput::make('konfirmasi')
                        ->label('Ketik PULIHKAN untuk melanjutkan')
                        ->placeholder('PULIHKAN')
                        ->required()
                        ->rule('in:PULIHKAN')
                        ->validationMessages(['in' => 'Ketik PULIHKAN persis, dengan huruf kapital.']),
                ])
                ->action(function (array $data): void {
                    @set_time_limit(0);
                    @ini_set('memory_limit', '512M');

                    $relatif = $data['berkas'];
                    $lokal = Storage::disk('local')->path($relatif);

                    try {
                        $hasil = app(BackupRestoreService::class)->pulihkanDariUnggahan($lokal);
                    } catch (Throwable $e) {
                        $this->beriTahu('Pemulihan gagal', $e->getMessage(), 'danger');

                        return;
                    } finally {
                        Storage::disk('local')->delete($relatif);
                    }

                    $this->beriTahu('Pemulihan selesai', $this->ringkasan($hasil), 'success');
                }),
        ];
    }

    /** @param array{basisData: bool, berkas: int, pengaman: bool} $hasil */
    private function ringkasan(array $hasil): string
    {
        $bagian = [];

        if ($hasil['basisData']) {
            $bagian[] = 'basis data dipulihkan';
        }

        if ($hasil['berkas'] > 0) {
            $bagian[] = "{$hasil['berkas']} berkas dikembalikan";
        }

        return ucfirst(implode(' dan ', $bagian)).'. Cadangan pengaman sebelum pemulihan sudah dibuat.';
    }

    /** Arsip lengkap: basis data beserta seluruh berkas. */
    public function unduhAction(): Action
    {
        return Action::make('unduh')
            ->label('Unduh Semua')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->action(function (array $arguments): StreamedResponse {
                $arsip = app(BackupRestoreService::class)->alirkanArsip($arguments['path']);

                return response()->streamDownload(function () use ($arsip): void {
                    fpassthru($arsip['aliran']);
                    fclose($arsip['aliran']);
                }, $arsip['nama']);
            });
    }

    /**
     * Dump basis data saja. Ukurannya jauh lebih kecil daripada arsip lengkap,
     * jadi masih masuk akal diunduh berkala lewat sambungan lambat.
     */
    public function unduhDatabaseAction(): Action
    {
        return Action::make('unduhDatabase')
            ->label('Unduh Database')
            ->icon('heroicon-o-circle-stack')
            ->color('gray')
            ->action(function (array $arguments): StreamedResponse {
                $dump = app(BackupRestoreService::class)->ambilDump($arguments['path']);

                return response()->streamDownload(
                    function () use ($dump) {
                        readfile($dump['path']);
                        @unlink($dump['path']);
                    },
                    $dump['nama'],
                );
            });
    }

    /** Berkas tersimpan saja, tanpa dump basis data. */
    public function unduhBerkasAction(): Action
    {
        return Action::make('unduhBerkas')
            ->label('Unduh Berkas')
            ->icon('heroicon-o-folder-arrow-down')
            ->color('gray')
            ->action(function (array $arguments): StreamedResponse {
                @set_time_limit(0);

                $arsip = app(BackupRestoreService::class)->ambilArsipBerkas($arguments['path']);

                return response()->download($arsip['path'], $arsip['nama'])->deleteFileAfterSend();
            });
    }

    public function pulihkanAction(): Action
    {
        return Action::make('pulihkan')
            ->label('Pulihkan')
            ->icon('heroicon-o-arrow-uturn-left')
            ->color('danger')
            ->modalHeading('Pulihkan seluruh data dari cadangan ini?')
            ->modalDescription('Seluruh isi basis data dan berkas tersimpan akan ditimpa oleh isi cadangan. Data yang masuk setelah cadangan ini dibuat akan HILANG, dan seluruh pengguna akan keluar dari sesinya. Satu cadangan pengaman dibuat lebih dulu secara otomatis.')
            ->modalSubmitActionLabel('Pulihkan Sekarang')
            ->modalIcon('heroicon-o-exclamation-triangle')
            ->modalIconColor('danger')
            // Modal tidak boleh tertutup tanpa sengaja di tengah pemulihan.
            ->modalCloseButton(false)
            ->closeModalByClickingAway(false)
            ->closeModalByEscaping(false)
            ->schema([
                TextInput::make('konfirmasi')
                    ->label('Ketik PULIHKAN untuk melanjutkan')
                    ->placeholder('PULIHKAN')
                    ->required()
                    ->rule('in:PULIHKAN')
                    ->validationMessages(['in' => 'Ketik PULIHKAN persis, dengan huruf kapital.']),
            ])
            ->action(function (array $arguments): void {
                @set_time_limit(0);

                try {
                    $hasil = app(BackupRestoreService::class)->pulihkan($arguments['path']);
                } catch (Throwable $e) {
                    $this->beriTahu('Pemulihan gagal', $e->getMessage(), 'danger');

                    return;
                }

                $this->beriTahu('Pemulihan selesai', $this->ringkasan($hasil), 'success');
            });
    }

    public function hapusAction(): Action
    {
        return Action::make('hapus')
            ->label('Hapus')
            ->icon('heroicon-o-trash')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('Hapus cadangan ini?')
            ->modalDescription('Arsip dihapus permanen dari server.')
            ->modalSubmitActionLabel('Hapus')
            ->action(function (array $arguments): void {
                try {
                    app(BackupRestoreService::class)->hapus($arguments['path']);
                } catch (Throwable $e) {
                    $this->beriTahu('Gagal menghapus', $e->getMessage(), 'danger');

                    return;
                }

                $this->beriTahu('Cadangan dihapus', 'Arsip sudah dihapus dari server.', 'success');
            });
    }

    private function beriTahu(string $judul, string $isi, string $warna): void
    {
        Notification::make()
            ->title($judul)
            ->body($isi)
            ->{$warna}()
            ->persistent()
            ->send();
    }
}
