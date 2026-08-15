<?php

namespace App\Filament\Resources\Modules\Pages;

use App\Enums\JenisEvaluasi;
use App\Filament\Resources\EvaluasiKegiatans\EvaluasiKegiatanResource;
use App\Filament\Resources\Modules\ModuleResource;
use App\Filament\Resources\Pretests\PretestResource;
use App\Models\Evaluasi;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;

/**
 * Detail modul baca-saja. Pintu supervisi DPMD (ditolak Edit oleh Gate::before);
 * operator/pengajar/superadmin tetap mendapat tombol Edit.
 */
class ViewModule extends ViewRecord
{
    protected static string $resource = ModuleResource::class;

    /** Halaman detail menyebut modulnya, bukan "Lihat Modul" seperti bawaan Filament. */
    public function getTitle(): string
    {
        return (string) $this->record->judul;
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Ubah Modul')
                ->color('warning')
                ->visible(fn (): bool => ! $this->record->trashed()),

            Action::make('previewWarga')
                ->label('Pratinjau Warga')
                ->icon('heroicon-o-eye')
                // Abu-abu seperti aksi sekunder lainnya. Hanya Ubah yang berwarna,
                // supaya baris tombol tidak menjadi deretan warna yang saling berebut.
                ->color('gray')
                ->openUrlInNewTab()
                ->visible(fn (): bool => ! $this->record->trashed())
                ->url(function (): string {
                    $firstPage = $this->record->materis()->orderBy('urutan')->first();

                    if ($firstPage) {
                        return route('admin.preview.modules.materi.show', [
                            'module' => $this->record->slug,
                            'materi' => $firstPage->id,
                        ]);
                    }

                    return route('admin.preview.modules.show', ['module' => $this->record->slug]);
                }),

            $this->evaluasiAction(JenisEvaluasi::Pretest),

            $this->evaluasiAction(JenisEvaluasi::Kegiatan),

            ActionGroup::make([
                DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
                ->label('Aksi Lainnya')
                ->icon('heroicon-o-squares-2x2')
                ->button()
                ->color('gray'),
        ];
    }

    /**
     * Satu tombol per jenis evaluasi, langsung dari halaman modul. Tujuannya agar
     * pengajar tidak pernah perlu membuka menu Evaluasi dan memilih modul/jenis
     * secara manual: jenis dan modulnya sudah terkunci lewat query string.
     */
    private function evaluasiAction(JenisEvaluasi $jenis): Action
    {
        $ambil = fn (): ?Evaluasi => $jenis === JenisEvaluasi::Pretest
            ? $this->record->pretest
            : $this->record->evaluasiKegiatan;

        return Action::make('kelola'.ucfirst($jenis->value))
            ->label(function () use ($ambil, $jenis): string {
                $evaluasi = $ambil();
                $nama = $jenis->getLabel();

                return match (true) {
                    $evaluasi === null => 'Tambah '.$nama,
                    auth()->user()?->can('update', $evaluasi) => 'Kelola '.$nama,
                    default => 'Lihat '.$nama,
                };
            })
            ->icon($jenis === JenisEvaluasi::Pretest
                ? 'heroicon-o-clipboard-document-list'
                : 'heroicon-o-clipboard-document-check')
            ->color('gray')
            // Tanpa badge "belum ada": Filament menggantungnya di atas baris tombol
            // sehingga tampak lepas, dan labelnya sendiri sudah membedakan "Tambah"
            // dari "Kelola".
            ->visible(function () use ($ambil): bool {
                if ($this->record->trashed()) {
                    return false;
                }

                $evaluasi = $ambil();

                return $evaluasi
                    ? (auth()->user()?->can('view', $evaluasi) ?? false)
                    : ((auth()->user()?->can('create', Evaluasi::class) ?? false)
                        && (auth()->user()?->can('update', $this->record) ?? false));
            })
            ->url(function () use ($ambil, $jenis): string {
                $evaluasi = $ambil();

                $resource = $jenis === JenisEvaluasi::Pretest
                    ? PretestResource::class
                    : EvaluasiKegiatanResource::class;

                return $evaluasi
                    ? $resource::getUrl('view', ['record' => $evaluasi])
                    : $resource::getUrl('create', ['module_id' => $this->record->id]);
            });
    }
}
