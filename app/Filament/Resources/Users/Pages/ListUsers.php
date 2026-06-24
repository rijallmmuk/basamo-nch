<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Desas\DesaResource;
use App\Filament\Resources\Users\UserResource;
use App\Imports\WargaImport;
use App\Models\DesaUnit;
use App\Models\User;
use App\Services\WargaImportService;
use App\Services\WargaTemplateBuilder;
use App\Support\DesaContext;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    /** Saat super admin mengelola desa tertentu, perjelas konteksnya di subjudul. */
    public function getSubheading(): ?string
    {
        $desa = (auth()->user()?->isSuperAdmin() ?? false) ? DesaContext::desa() : null;

        return $desa ? 'Mengelola warga — '.$desa->nama_lengkap : null;
    }

    protected function getHeaderActions(): array
    {
        $actions = [];

        // Super admin dalam konteks desa → tombol keluar (kembali ke daftar Desa).
        if ((auth()->user()?->isSuperAdmin() ?? false) && DesaContext::id() !== null) {
            $actions[] = Action::make('kembaliKeDesa')
                ->label('Kembali ke Desa')
                ->icon('heroicon-o-arrow-left')
                ->color('gray')
                ->action(function () {
                    DesaContext::clear();

                    return redirect(DesaResource::getUrl('index'));
                });
        }

        // Impor Excel saat ter-scope ke satu desa (admin desa, atau super admin yang
        // sedang mengelola sebuah desa). 1 desa per impor.
        if (auth()->user()?->managedDesaId() !== null) {
            $actions[] = ActionGroup::make([
                $this->unduhTemplateAction(),
                $this->imporExcelAction(),
            ])
                ->label('Impor')
                ->icon('heroicon-o-table-cells')
                ->button()
                ->color('gray');
        }

        $actions[] = CreateAction::make();

        return $actions;
    }

    /** Unduh template Excel sesuai konteks aktor (desa_admin vs super_admin). */
    private function unduhTemplateAction(): Action
    {
        return Action::make('unduhTemplate')
            ->label('Unduh Template')
            ->icon('heroicon-o-arrow-down-tray')
            ->color('gray')
            ->action(function (): ?StreamedResponse {
                // Cegah template dengan kolom sub-unit kosong/tanpa dropdown.
                if ($pesan = $this->wilayahBelumSiap()) {
                    $this->notifyWilayahBelumSiap($pesan);

                    return null;
                }

                return app(WargaTemplateBuilder::class)->download(auth()->user());
            });
    }

    /** Impor banyak warga dari file Excel/CSV sesuai template. */
    private function imporExcelAction(): Action
    {
        return Action::make('imporExcel')
            ->label('Impor dari Excel')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('gray')
            ->modalHeading('Impor Warga dari Excel')
            ->modalDescription('Unggah file sesuai template. Tiap baris dibuat sebagai warga baru di desa terkait. OTP diterbitkan terpisah lewat aksi "Reset OTP".')
            ->modalSubmitActionLabel('Impor')
            ->schema([
                FileUpload::make('file')
                    ->label('File Excel / CSV')
                    ->acceptedFileTypes([
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-excel',
                        'text/csv',
                    ])
                    ->maxSize(5120) // 5 MB
                    ->disk('local')
                    ->directory('imports/warga')
                    ->visibility('private')
                    ->required(),
            ])
            ->action(function (array $data): void {
                /** @var User $actor */
                $actor = auth()->user();
                $path = $data['file'];

                // Tanpa sub-unit, semua baris pasti gagal — hentikan lebih awal & jelaskan.
                if ($pesan = $this->wilayahBelumSiap()) {
                    Storage::disk('local')->delete($path);
                    $this->notifyWilayahBelumSiap($pesan);

                    return;
                }

                $import = new WargaImport($actor, app(WargaImportService::class));

                try {
                    Excel::import($import, $path, 'local');
                } finally {
                    // Berkas unggahan bersifat sementara — selalu bersihkan.
                    Storage::disk('local')->delete($path);
                }

                $this->notifyResult($import);
            });
    }

    /**
     * Pastikan data wilayah desa siap untuk impor: sebutan sub-unit (Jorong/Korong/…)
     * sudah diatur DAN minimal ada satu sub-unit. Kembalikan pesan masalah, atau null bila siap.
     */
    private function wilayahBelumSiap(): ?string
    {
        $desa = DesaContext::desa() ?? auth()->user()?->desa;

        $kurang = [];
        if ($desa?->jenisSubUnit === null) {
            $kurang[] = 'sebutan sub-unit (mis. Jorong/Korong) belum diatur';
        }
        if ($desa === null || ! DesaUnit::where('desa_id', $desa->id)->exists()) {
            $kurang[] = 'daftar sub-unit masih kosong';
        }

        if ($kurang === []) {
            return null;
        }

        return 'Data wilayah belum lengkap — '.implode(' & ', $kurang)
            .'. Lengkapi dulu lewat menu Wilayah / Pengaturan Desa agar template tidak kosong dan impor bisa jalan.';
    }

    private function notifyWilayahBelumSiap(string $pesan): void
    {
        Notification::make()
            ->title('Lengkapi data Wilayah dulu')
            ->body($pesan)
            ->warning()
            ->persistent()
            ->send();
    }

    private function notifyResult(WargaImport $import): void
    {
        if ($import->errors === []) {
            Notification::make()
                ->title('Impor selesai')
                ->body("{$import->imported} warga berhasil ditambahkan.")
                ->success()
                ->send();

            return;
        }

        $preview = collect($import->errors)
            ->take(10)
            ->map(fn (array $e): string => "Baris {$e['baris']}: {$e['pesan']}")
            ->implode("\n");

        $sisa = count($import->errors) - 10;
        $body = "{$import->imported} berhasil, ".count($import->errors)." gagal.\n".$preview
            .($sisa > 0 ? "\n…dan {$sisa} baris lain." : '');

        Notification::make()
            ->title('Impor selesai dengan sebagian gagal')
            ->body($body)
            ->warning()
            ->persistent()
            ->send();
    }
}
