<?php

namespace App\Jobs;

use App\Imports\WargaImport;
use App\Models\Desa;
use App\Models\User;
use App\Services\WargaImportService;
use Filament\Notifications\Notification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

/**
 * Memproses impor warga dari berkas Excel/CSV di latar belakang (queue) agar file
 * besar (ribuan baris) tak mem-block request HTTP / kena timeout. Hasilnya dilaporkan
 * ke admin pemicu lewat notifikasi database (lonceng panel).
 *
 * Desa SELALU diteruskan eksplisit (bukan diturunkan dari session/`managedDesaId`),
 * karena job berjalan tanpa konteks sesi web.
 */
class ImportWarga implements ShouldQueue
{
    use Queueable;

    /** Beri ruang untuk berkas besar; impor sinkron dalam satu proses job. */
    public int $timeout = 600;

    public function __construct(
        private string $path,
        private int $desaId,
        private int $actorId,
    ) {}

    public function handle(WargaImportService $service): void
    {
        $desa = Desa::find($this->desaId);
        $actor = User::find($this->actorId);

        if ($desa === null || $actor === null) {
            Storage::disk('local')->delete($this->path);

            return;
        }

        $import = new WargaImport($desa, $service);

        try {
            Excel::import($import, $this->path, 'local');
        } finally {
            // Berkas unggahan bersifat sementara — selalu bersihkan.
            Storage::disk('local')->delete($this->path);
        }

        $this->notifyResult($actor, $import);
    }

    /** Bersihkan berkas & beri tahu admin bila job gagal total (mis. berkas korup). */
    public function failed(?Throwable $e): void
    {
        Storage::disk('local')->delete($this->path);

        if ($actor = User::find($this->actorId)) {
            Notification::make()
                ->title('Impor warga gagal diproses')
                ->body('Berkas tidak dapat dibaca. Pastikan formatnya sesuai template lalu coba lagi.')
                ->danger()
                ->sendToDatabase($actor);
        }
    }

    private function notifyResult(User $actor, WargaImport $import): void
    {
        if ($import->errors === []) {
            Notification::make()
                ->title('Impor warga selesai')
                ->body("{$import->imported} warga berhasil ditambahkan.")
                ->success()
                ->sendToDatabase($actor);

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
            ->title('Impor warga selesai dengan sebagian gagal')
            ->body($body)
            ->warning()
            ->sendToDatabase($actor);
    }
}
