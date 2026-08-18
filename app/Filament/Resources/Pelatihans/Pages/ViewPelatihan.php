<?php

namespace App\Filament\Resources\Pelatihans\Pages;

use App\Enums\StatusPelatihan;
use App\Filament\Resources\Pelatihans\PelatihanResource;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use App\Enums\ModuleProgressStatus;
use App\Enums\StatusPercobaan;
use App\Models\Evaluasi;
use App\Models\User;
use App\Support\Reports\ReportActionGroup;
use App\Support\Reports\ReportColumn;
use App\Support\Reports\TabularReport;
use Illuminate\Database\Eloquent\Builder;

/**
 * Detail pelatihan dan pintu utama supervisi DPMD.
 */
class ViewPelatihan extends ViewRecord
{
    protected static string $resource = PelatihanResource::class;

    public function getTitle(): string
    {
        return $this->record->namaTampil();
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            ReportActionGroup::make(fn (): TabularReport => $this->participantReport()),
            EditAction::make()
                ->label('Ubah Pelatihan')
                ->color('warning')
                ->visible(fn (): bool => ! $this->record->trashed()),

            ...$this->statusActions(),

            ActionGroup::make([
                DeleteAction::make()
                    ->modalDescription('Menghapus pelatihan ini SEKALIGUS menyampah seluruh isinya: semua modul, materi, evaluasi, dan diskusi di dalamnya. Semua bisa dipulihkan bersama lewat Pulihkan.'),
                RestoreAction::make(),
                ForceDeleteAction::make()
                    ->modalDescription('Menghapus PERMANEN pelatihan beserta seluruh modul, materi, evaluasi, dan diskusinya. Tindakan ini tidak dapat dibatalkan.'),
            ])
                ->label('Aksi Lainnya')
                ->icon('heroicon-o-squares-2x2')
                ->button()
                ->color('gray'),
        ];
    }

    private function participantReport(): TabularReport
    {
        $pelatihan = $this->record;
        $moduleIds = $pelatihan->modules()->pluck('modules.id');
        $evaluasiIds = Evaluasi::query()->whereIn('module_id', $moduleIds)->pluck('id');
        $actor = auth()->user();

        $query = User::query()
            ->role('warga')
            ->when($actor?->isOperator(), fn (Builder $users) => $users->where('users.nagari_id', $actor->nagari_id))
            ->where(function (Builder $participation) use ($pelatihan, $moduleIds, $evaluasiIds): void {
                $participation
                    ->whereHas('moduleProgress', fn (Builder $q) => $q->whereIn('module_id', $moduleIds))
                    ->orWhereHas('evaluasiPercobaans', fn (Builder $q) => $q->whereIn('evaluasi_id', $evaluasiIds))
                    ->orWhereHas('discussions', fn (Builder $q) => $q->whereIn('module_id', $moduleIds))
                    ->orWhereHas('pelatihanAttendances', fn (Builder $q) => $q->where('pelatihan_id', $pelatihan->getKey()));
            })
            ->with([
                'nagari',
                'moduleProgress' => fn ($q) => $q->whereIn('module_id', $moduleIds),
                'evaluasiPercobaans' => fn ($q) => $q->whereIn('evaluasi_id', $evaluasiIds),
                'discussions' => fn ($q) => $q->whereIn('module_id', $moduleIds),
                'pelatihanAttendances' => fn ($q) => $q->where('pelatihan_id', $pelatihan->getKey()),
                'certificates' => fn ($q) => $q->where('pelatihan_id', $pelatihan->getKey()),
            ])
            ->orderBy('name');

        return new TabularReport(
            title: 'Rekap Peserta Pelatihan',
            filename: 'rekap-peserta-'.$pelatihan->namaTampil(),
            query: $query,
            columns: [
                new ReportColumn('name', 'Nama Warga', 30),
                new ReportColumn('nagari.nama', 'Nagari', 25),
                new ReportColumn('moduleProgress', 'Modul Selesai', 15, fn ($value, User $record): int => $record->moduleProgress->where('status', ModuleProgressStatus::Completed)->count()),
                new ReportColumn('moduleProgress', 'Modul Berjalan', 15, fn ($value, User $record): int => $record->moduleProgress->where('status', ModuleProgressStatus::InProgress)->count()),
                new ReportColumn('id', 'Total Modul', 13, fn (): int => $moduleIds->count()),
                new ReportColumn('evaluasiPercobaans', 'Nilai Terbaik', 14, fn ($value, User $record): string => ($score = $record->evaluasiPercobaans->max('nilai')) === null ? '—' : (string) $score),
                new ReportColumn('evaluasiPercobaans', 'Evaluasi Lulus', 15, fn ($value, User $record): int => $record->evaluasiPercobaans->where('status', StatusPercobaan::Passed)->pluck('evaluasi_id')->unique()->count()),
                new ReportColumn('pelatihanAttendances', 'Kehadiran Webinar', 18, fn ($value, User $record): string => $record->pelatihanAttendances->isNotEmpty() ? 'Hadir' : '—'),
                new ReportColumn('discussions', 'Partisipasi Diskusi', 18, fn ($value, User $record): int => $record->discussions->count()),
                new ReportColumn('certificates', 'Sertifikat', 15, fn ($value, User $record): string => $record->certificates->isNotEmpty() ? 'Terbit' : 'Belum'),
                new ReportColumn('certificates.0.nomor_seri', 'Nomor Sertifikat', 24, fn ($value): string => $value ?: '—'),
            ],
            metadata: ['Cakupan' => $pelatihan->namaTampil()],
        );
    }

    /** @return list<Action> */
    private function statusActions(): array
    {
        return [
            Action::make('buka')
                ->label('Buka untuk Warga')
                ->icon(Heroicon::OutlinedLockOpen)
                ->color('success')
                ->visible(fn (): bool => ! $this->record->trashed()
                    && $this->record->status !== StatusPelatihan::Terbuka)
                ->authorize(fn (): bool => auth()->user()?->can('kelolaStatus', $this->record) ?? false)
                ->requiresConfirmation()
                ->modalDescription('Warga di seluruh nagari sasaran dapat mengakses modulnya dan akan diberi notifikasi.')
                ->action(fn () => $this->ubahStatus(StatusPelatihan::Terbuka)),

            Action::make('kunci')
                ->label('Kunci')
                ->icon(Heroicon::OutlinedLockClosed)
                ->color('warning')
                ->visible(fn (): bool => ! $this->record->trashed()
                    && $this->record->status !== StatusPelatihan::Terkunci)
                ->authorize(fn (): bool => auth()->user()?->can('kelolaStatus', $this->record) ?? false)
                ->requiresConfirmation()
                ->modalDescription('Warga tetap melihat pelatihan sebagai "Belum dibuka", tetapi tidak dapat mengakses isinya.')
                ->action(fn () => $this->ubahStatus(StatusPelatihan::Terkunci)),
        ];
    }

    private function ubahStatus(StatusPelatihan $status): void
    {
        try {
            PelatihanResource::setStatus($this->record, $status);
        } catch (\DomainException $e) {
            Notification::make()
                ->title('Status tidak dapat diubah')
                ->body($e->getMessage().' Tambahkan sasaran nagari dan minimal satu materi terlebih dahulu.')
                ->danger()
                ->send();

            return;
        }

        Notification::make()
            ->title('Status pelatihan diperbarui: '.$status->getLabel())
            ->success()
            ->send();
    }
}
