<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Desas\DesaResource;
use App\Filament\Resources\Users\UserResource;
use App\Jobs\ImportWarga;
use App\Models\DesaUnit;
use App\Services\WargaTemplateBuilder;
use App\Support\DesaContext;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
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
            ->modalDescription('Unggah file sesuai template. Tiap baris dibuat sebagai warga baru di desa terkait. Diproses di latar belakang; hasilnya muncul di lonceng notifikasi. OTP diterbitkan terpisah lewat aksi "Reset OTP".')
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
                $actor = auth()->user();
                $path = $data['file'];

                // Tanpa sub-unit, semua baris pasti gagal — hentikan lebih awal & jelaskan.
                if ($pesan = $this->wilayahBelumSiap()) {
                    Storage::disk('local')->delete($path);
                    $this->notifyWilayahBelumSiap($pesan);

                    return;
                }

                // Proses di latar belakang (queue) → file besar tak mem-block / timeout.
                // Desa diteruskan eksplisit; job tak punya konteks sesi.
                ImportWarga::dispatch($path, (int) $actor->managedDesaId(), $actor->getKey());

                Notification::make()
                    ->title('Impor sedang diproses')
                    ->body('Warga ditambahkan di latar belakang. Hasilnya akan muncul di lonceng notifikasi saat selesai.')
                    ->info()
                    ->send();
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
}
