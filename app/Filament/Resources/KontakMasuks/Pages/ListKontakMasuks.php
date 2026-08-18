<?php

namespace App\Filament\Resources\KontakMasuks\Pages;

use App\Filament\Concerns\ExportsTableReports;
use App\Filament\Concerns\HasListTitle;
use App\Filament\Resources\KontakMasuks\KontakMasukResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use App\Support\Reports\ReportColumn;

class ListKontakMasuks extends ListRecords
{
    protected static string $resource = KontakMasukResource::class;

    use ExportsTableReports;
    use HasListTitle;

    protected function getHeaderActions(): array
    {
        return [
            $this->reportActionGroup(),
            Action::make('markAllAsRead')
                ->label('Tandai Semua Telah Dibaca')
                ->icon('heroicon-o-check-badge')
                ->color('success')
                ->action(function (): void {
                    $user = auth()->user();

                    if (! $user) {
                        return;
                    }

                    $laporans = KontakMasukResource::getEloquentQuery()
                        ->whereDoesntHave('reads', fn ($query) => $query->where('user_id', $user->id))
                        ->get();

                    foreach ($laporans as $laporan) {
                        $laporan->markAsReadBy($user);
                    }

                    Notification::make()
                        ->title('Seluruh laporan ditandai telah dibaca')
                        ->success()
                        ->send();

                    $this->dispatch('refresh-sidebar');
                }),
        ];
    }

    protected function reportTitle(): string { return 'Pesan dan Laporan Masuk'; }

    protected function reportFormats(): array { return ['xlsx']; }

    protected function reportColumns(): array
    {
        return [
            new ReportColumn('created_at', 'Diajukan', 20, fn ($value): string => $value?->format('d/m/Y H:i') ?? '—'),
            new ReportColumn('kategori', 'Kategori', 18, fn ($value): string => $value instanceof \BackedEnum ? $value->value : (string) $value),
            new ReportColumn('nama', 'Nama Pengirim', 28),
            new ReportColumn('email', 'Email', 28),
            new ReportColumn('no_hp', 'Nomor HP', 20),
            new ReportColumn('nama_nagari', 'Nagari', 24),
            new ReportColumn('isi', 'Isi Pesan', 55),
            new ReportColumn('balasan', 'Balasan', 55, fn ($value): string => $value ?: 'Belum dibalas'),
            new ReportColumn('balasan', 'Status', 16, fn ($value): string => $value ? 'Dibalas' : 'Menunggu'),
        ];
    }
}
