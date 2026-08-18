<?php

namespace App\Filament\Resources\UmkmProfiles\Pages;

use App\Enums\ActiveStatus;
use App\Filament\Resources\UmkmProfiles\Support\UmkmProfileActions;
use App\Filament\Resources\UmkmProfiles\UmkmProfileResource;
use App\Models\UmkmProfile;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use App\Models\UmkmProduct;
use App\Models\UmkmView;
use App\Support\Reports\ReportActionGroup;
use App\Support\Reports\ReportColumn;
use App\Support\Reports\TabularReport;

/**
 * Detail profil UMKM. Pintu supervisi DPMD (ditolak Edit oleh Gate::before);
 * operator/superadmin/pemilik tetap mendapat tombol Edit.
 *
 * Aksi lapak memakai definisi yang sama dengan tabel UMKM ({@see UmkmProfileActions})
 * supaya pengelola tidak menemukan pilihan atau kata-kata berbeda di dua layar.
 */
class ViewUmkmProfile extends ViewRecord
{
    protected static string $resource = UmkmProfileResource::class;

    public function getTitle(): string
    {
        return UmkmProfileResource::isSelfService()
            ? 'Usaha Saya'
            : $this->record->nama_usaha;
    }

    protected function getHeaderActions(): array
    {
        return [
            ReportActionGroup::make(fn (): TabularReport => $this->analyticsReport()),
            EditAction::make()->label('Edit Profil UMKM')->color('warning'),

            // Hasil akhir yang dilihat pembeli. Memakai rute cadangan `/n/{nagari}`
            // yang selalu bekerja, termasuk saat subdomain wildcard belum disiapkan.
            Action::make('lihatPublik')
                ->label('Lihat Halaman Publik')
                ->icon('heroicon-o-arrow-top-right-on-square')
                ->color('gray')
                ->openUrlInNewTab()
                ->visible(fn (UmkmProfile $record): bool => ! $record->trashed()
                    && $record->status === ActiveStatus::Active
                    && $record->nagari !== null)
                ->url(fn (UmkmProfile $record): string => route(
                    'public.nagari.umkm.etalase.fallback',
                    [$record->nagari, $record],
                )),
            UmkmProfileActions::toggleStatus(),
            ActionGroup::make([
                UmkmProfileActions::archive(),
                UmkmProfileActions::restore(),
                UmkmProfileActions::forceDelete(),
            ])
                ->label('Aksi UMKM')
                ->icon('heroicon-o-ellipsis-vertical')
                ->button()
                ->color('gray'),
        ];
    }

    private function analyticsReport(): TabularReport
    {
        /** @var UmkmProfile $profile */
        $profile = $this->record;
        $productIds = $profile->products()->pluck('id');
        $mulai = now()->subDays(29)->startOfDay();

        return new TabularReport(
            title: 'Analitik Kunjungan UMKM',
            filename: 'analitik-umkm-'.$profile->nama_usaha,
            query: UmkmView::query()
                ->where(function ($query) use ($profile, $productIds): void {
                    $query->where(fn ($lapak) => $lapak
                        ->where('viewable_type', UmkmProfile::class)
                        ->where('viewable_id', $profile->getKey()))
                        ->orWhere(fn ($produk) => $produk
                            ->where('viewable_type', UmkmProduct::class)
                            ->whereIn('viewable_id', $productIds));
                })
                ->whereBetween('tanggal', [$mulai->toDateString(), now()->toDateString()])
                ->with('viewable')
                ->orderByDesc('tanggal'),
            columns: [
                new ReportColumn('tanggal', 'Tanggal', 15, fn ($value): string => $value?->format('d/m/Y') ?? '—'),
                new ReportColumn('viewable_type', 'Jenis', 15, fn ($value): string => $value === UmkmProfile::class ? 'Etalase Usaha' : 'Produk'),
                new ReportColumn('viewable', 'Nama', 30, fn ($value, $record): string => $record->viewable instanceof UmkmProfile ? $record->viewable->nama_usaha : ($record->viewable?->nama_produk ?? 'Konten dihapus')),
                new ReportColumn('jumlah', 'Kunjungan', 14),
            ],
            metadata: ['Cakupan' => $profile->nama_usaha, 'Periode' => $mulai->format('d/m/Y').' – '.now()->format('d/m/Y')],
            orientation: 'portrait',
        );
    }
}
