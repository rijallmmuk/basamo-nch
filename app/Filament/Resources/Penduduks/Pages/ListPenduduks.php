<?php

namespace App\Filament\Resources\Penduduks\Pages;

use App\Exports\WargaExport;
use App\Filament\Concerns\HasListTitle;
use App\Filament\Resources\Penduduks\PendudukResource;
use App\Imports\WargaImport;
use App\Models\Nagari;
use App\Services\WargaImportService;
use App\Services\WargaTemplateBuilder;
use App\Support\NagariContext;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListPenduduks extends ListRecords
{
    protected static string $resource = PendudukResource::class;

    use HasListTitle;

    public ?int $nagariId = null;

    /** Jumlah warga yang berhasil diimpor pada proses terakhir. */
    public int $imporBerhasil = 0;

    /** @var list<array{baris:int, nama:string, nik:string, pesan:string}> */
    public array $imporGagal = [];

    public function mount(): void
    {
        parent::mount();

        if (auth()->user()?->isSuperAdmin() ?? false) {
            NagariContext::ensureDefault(NagariContext::WARGA);
            $this->nagariId = NagariContext::id(NagariContext::WARGA);
        }
    }

    public function updatedNagariId(): void
    {
        if ((auth()->user()?->isSuperAdmin() ?? false) && $this->nagariId !== null) {
            NagariContext::set(NagariContext::WARGA, $this->nagariId);
        }
    }

    public function content(Schema $schema): Schema
    {
        $components = parent::content($schema)->getComponents();
        array_splice($components, 1, 0, [View::make('filament.components.nagari-picker-banner')]);

        return $schema->components($components);
    }

    protected function getHeaderActions(): array
    {
        $nagariId = auth()->user()->managedNagariId(NagariContext::WARGA);
        $nagari = $nagariId ? Nagari::find($nagariId) : null;
        $actions = [];

        if ($nagari) {
            $actions[] = ActionGroup::make([
                $this->unduhTemplateAction(),
                $this->imporExcelAction($nagari),
                $this->exportExcelAction($nagari),
            ])
                ->label('Impor / Ekspor')
                ->icon('heroicon-o-table-cells')
                ->button()
                ->color('gray');
        }

        $actions[] = CreateAction::make()->color('primary')
            ->label('Tambah Warga')
            ->url(fn (): string => PendudukResource::getUrl('create', $nagariId ? ['nagari_id' => $nagariId] : []));

        return $actions;
    }

    private function unduhTemplateAction(): Action
    {
        return Action::make('unduhTemplate')
            ->label('Unduh Template')
            ->icon('heroicon-o-arrow-down-tray')
            ->action(fn (): StreamedResponse => app(WargaTemplateBuilder::class)->download(auth()->user()));
    }

    private function exportExcelAction(Nagari $nagari): Action
    {
        return Action::make('exportExcel')
            ->label('Export')
            ->icon('heroicon-o-document-arrow-down')
            ->action(fn (): StreamedResponse => (new WargaExport($nagari))->download());
    }

    private function imporExcelAction(Nagari $nagari): Action
    {
        return Action::make('imporExcel')
            ->label('Impor dari Excel')
            ->icon('heroicon-o-arrow-up-tray')
            ->authorize(fn (): bool => auth()->user()?->can('create', PendudukResource::getModel()) ?? false)
            ->visible(fn (): bool => auth()->user()?->can('create', PendudukResource::getModel()) ?? false)
            ->modalHeading('Impor Warga dari Excel')
            ->modalDescription('Setiap baris membuat identitas dan satu akun login warga pada nagari ini. Berkas puluhan ribu baris membutuhkan beberapa menit; jangan menutup atau memuat ulang halaman selama proses berjalan.')
            ->modalSubmitActionLabel('Mulai Impor')
            ->modalCancelActionLabel('Batalkan')
            // Selama impor berjalan modal tidak boleh tertutup tanpa sengaja:
            // prosesnya sinkron, jadi menutup halaman di tengah jalan meninggalkan
            // sebagian baris sudah tersimpan tanpa laporan apa pun.
            ->modalCloseButton(false)
            ->closeModalByClickingAway(false)
            ->closeModalByEscaping(false)
            ->schema([
                FileUpload::make('file')
                    ->label('File Excel / CSV')
                    ->acceptedFileTypes([
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-excel',
                        'text/csv',
                    ])
                    ->maxSize(20480)
                    ->disk('local')
                    ->directory('imports/warga')
                    ->visibility('private')
                    ->required(),
            ])
            ->action(function (array $data) use ($nagari): void {
                // Berkas nagari kerap berisi puluhan ribu baris. Diukur pada berkas
                // OpenSID: sekitar 7,5 detik per 1.000 baris, jadi 20.000 baris butuh
                // lebih dari dua menit dan menembus batas bawaan PHP. Batas dinaikkan
                // khusus untuk jalur ini, bukan untuk seluruh aplikasi.
                @set_time_limit(0);
                @ini_set('memory_limit', '512M');

                $path = $data['file'];
                $import = new WargaImport($nagari, app(WargaImportService::class));

                try {
                    $import->import(Storage::disk('local')->path($path));
                } catch (\Throwable $exception) {
                    report($exception);
                    Notification::make()
                        ->title('Impor gagal diproses')
                        ->body('Berkas tidak dapat dibaca. Pastikan formatnya sesuai template lalu coba lagi.')
                        ->danger()
                        ->send();

                    return;
                } finally {
                    Storage::disk('local')->delete($path);
                }

                // SATU catatan ringkasan untuk seluruh berkas. Mencatat per warga
                // membuat log aktivitas membengkak puluhan ribu baris tanpa guna.
                if ($import->imported > 0 || $import->errors !== []) {
                    activity('warga')
                        ->causedBy(auth()->user())
                        ->performedOn($nagari)
                        ->withProperties([
                            'berhasil' => $import->imported,
                            'gagal' => count($import->errors),
                        ])
                        ->log("Impor warga: {$import->imported} berhasil, ".count($import->errors).' gagal');
                }

                if ($import->errors === []) {
                    Notification::make()
                        ->title('Impor selesai')
                        ->body("{$import->imported} warga beserta akun login berhasil ditambahkan.")
                        ->success()
                        ->send();

                    return;
                }

                // Daftar gagal ditampilkan UTUH lewat modal tersendiri, bukan
                // dipotong di dalam notifikasi: operator perlu tahu setiap baris
                // yang dilewati beserta alasannya untuk membetulkan berkasnya.
                $this->imporBerhasil = $import->imported;
                $this->imporGagal = $import->errors;

                $this->replaceMountedAction('laporanImpor');
            });
    }

    /**
     * Laporan lengkap hasil impor: seluruh baris yang gagal beserta nama, NIK,
     * dan alasannya. Dibuka otomatis setelah impor yang menyisakan kegagalan.
     */
    protected function laporanImporAction(): Action
    {
        return Action::make('laporanImpor')
            ->modalHeading('Hasil Impor Warga')
            ->modalIcon('heroicon-o-exclamation-triangle')
            ->modalIconColor('warning')
            ->modalContent(fn (): ViewContract => view('filament.warga.laporan-impor', [
                'berhasil' => $this->imporBerhasil,
                'gagal' => $this->imporGagal,
            ]))
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Tutup');
    }
}
