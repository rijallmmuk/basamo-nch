<?php

namespace App\Filament\Resources\Nagaris\Pages;

use App\Filament\Concerns\ExportsTableReports;
use App\Filament\Concerns\HasListTitle;
use App\Filament\Resources\Nagaris\NagariResource;
use App\Support\NagariContext;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use App\Support\Reports\ReportColumn;

class ListNagaris extends ListRecords
{
    protected static string $resource = NagariResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        $user = auth()->user();

        // Blokir operator dari halaman ini secara eksplisit (hanya superadmin & dpmd yang boleh).
        // Ini perlu karena NagariResource::canAccess() di-bypass agar menu muncul untuk operator.
        return (bool) $user?->hasAnyRole(['superadmin', 'dpmd']);
    }

    use ExportsTableReports;
    use HasListTitle;

    public function mount(): void
    {
        parent::mount();

        // Kembali ke daftar Nagari → keluar dari SEMUA konteks menu (Warga/Wilayah/
        // UMKM), titik awal segar sebelum memilih "Kelola X" utk nagari lain.
        NagariContext::clearAll();
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->reportActionGroup(),
            CreateAction::make()->color('primary'),
        ];
    }

    protected function reportTitle(): string { return 'Ringkasan Nagari'; }

    protected function reportFormats(): array { return ['xlsx']; }

    protected function reportColumns(): array
    {
        return [
            new ReportColumn('nama', 'Nama Nagari', 28),
            new ReportColumn('wilayah_kode', 'Kode Wilayah', 18),
            new ReportColumn('kecamatan', 'Kecamatan', 24),
            new ReportColumn('kabupaten', 'Kabupaten/Kota', 24),
            new ReportColumn('operator.name', 'Operator', 28),
            new ReportColumn('warga_count', 'Jumlah Warga', 14),
            new ReportColumn('umkm_count', 'Jumlah UMKM', 14),
            new ReportColumn('modul_count', 'Jumlah Modul', 14),
            new ReportColumn('sdgs_skor', 'Rata-rata SDGs (%)', 18, fn ($value): string => $value === null ? 'Belum tersedia' : number_format((float) $value, 2, ',', '.')),
            new ReportColumn('status', 'Status', 14, fn ($value): string => $value instanceof \BackedEnum ? $value->value : (string) $value),
            new ReportColumn('updated_at', 'Diperbarui', 20, fn ($value): string => $value?->format('d/m/Y H:i') ?? '—'),
        ];
    }
}
