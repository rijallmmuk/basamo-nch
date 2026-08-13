<?php

namespace App\Filament\Widgets;

use App\Enums\ActiveStatus;
use App\Enums\ModuleProgressStatus;
use App\Enums\StatusIdm;
use App\Models\IdmStatus;
use App\Models\Nagari;
use App\Models\Penduduk;
use App\Models\SdgAchievement;
use App\Models\UmkmProduct;
use App\Models\UserModuleProgress;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class NagariPerformanceTableWidget extends BaseWidget
{
    protected ?string $pollingInterval = null;

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Rekap Performa Antar Nagari';

    public static function canView(): bool
    {
        return (bool) auth()->user()?->hasAnyRole(['superadmin', 'dpmd'])
            && auth()->user()?->managedNagariId() === null;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Nagari::query()
                    ->withCount([
                        'users as total_warga' => fn ($q) => $q->role('warga'),
                        'umkmProfiles as total_umkm',
                    ])
                    // Seluruh angka dirakit sebagai subquery pada query utama, bukan
                    // query per baris, supaya kolomnya dapat diurutkan.
                    ->addSelect([
                        'total_penduduk' => Penduduk::selectRaw('COUNT(*)')
                            ->whereColumn('penduduk.nagari_id', 'nagaris.id'),
                        'total_produk' => UmkmProduct::selectRaw('COUNT(*)')
                            ->join('umkm_profiles', 'umkm_profiles.id', '=', 'umkm_products.umkm_profile_id')
                            ->whereColumn('umkm_profiles.nagari_id', 'nagaris.id'),
                        'skor_sdgs' => SdgAchievement::selectRaw('AVG(persentase)')
                            ->whereColumn('sdg_achievements.nagari_id', 'nagaris.id'),
                        'modul_selesai' => UserModuleProgress::selectRaw('COUNT(*)')
                            ->join('users', 'users.id', '=', 'user_module_progress.user_id')
                            ->whereColumn('users.nagari_id', 'nagaris.id')
                            ->where('user_module_progress.status', ModuleProgressStatus::Completed->value),
                        'status_idm' => IdmStatus::select('status')
                            ->whereColumn('idm_statuses.nagari_id', 'nagaris.id')
                            ->orderByDesc('tahun')
                            ->limit(1),
                    ])
                    ->orderBy('nama')
            )
            ->columns([
                TextColumn::make('no')
                    ->label('No.')
                    ->rowIndex()
                    ->alignCenter(),

                TextColumn::make('nama')
                    ->label('Nama Nagari')
                    ->description(fn (Nagari $record): ?string => $record->kabupaten)
                    ->searchable()
                    ->sortable()
                    ->wrap(),

                // Kartu ringkasan hanya menghitung nagari AKTIF. Tanpa kolom ini,
                // tabel yang juga memuat nagari nonaktif terbaca tidak cocok dengannya.
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (Nagari $record): string => $record->status === ActiveStatus::Active ? 'success' : 'gray')
                    ->alignCenter(),

                TextColumn::make('total_penduduk')
                    ->label('Penduduk')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('total_warga')
                    ->label('Akun Portal')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('total_umkm')
                    ->label('Lapak UMKM')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('total_produk')
                    ->label('Produk')
                    ->numeric()
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('modul_selesai')
                    ->label('Modul Selesai')
                    ->numeric()
                    ->badge()
                    ->color('warning')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('skor_sdgs')
                    ->label('Skor SDGs')
                    ->formatStateUsing(fn ($state): string => $state === null
                        ? '—'
                        : number_format((float) $state, 1, ',', '.').'%')
                    ->badge()
                    ->color('primary')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('status_idm')
                    ->label('Status IDM')
                    ->formatStateUsing(fn ($state): string => StatusIdm::tryFrom((string) $state)?->label()
                        ?: ($state ?: 'Belum ada data'))
                    ->badge()
                    // Warna diambil dari enumnya sendiri supaya tabel ini, halaman
                    // Statistik IDM, dan grafik sebaran memakai kode warna yang sama.
                    ->color(fn ($state): string => StatusIdm::tryFrom((string) $state)?->color() ?? 'gray')
                    ->sortable()
                    ->alignCenter(),
            ])
            ->paginated([5, 10, 25])
            ->defaultPaginationPageOption(5);
    }
}
