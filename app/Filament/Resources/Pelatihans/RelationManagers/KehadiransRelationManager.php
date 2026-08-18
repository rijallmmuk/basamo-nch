<?php

namespace App\Filament\Resources\Pelatihans\RelationManagers;

use App\Models\Pelatihan;
use App\Models\PelatihanAttendance;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Support\Reports\ReportActionGroup;
use App\Support\Reports\ReportColumn;
use App\Support\Reports\TabularReport;

/**
 * Warga yang menandai dirinya mengikuti pertemuan daring.
 *
 * Hanya daftar bacaan. Penandaan adalah pengakuan warga sendiri dari portal, dan
 * operator tidak menambah atau mencabutnya dari sini; yang dipegang operator adalah
 * tombol Kunci/Buka, yang menentukan kapan penandaan itu masih mungkin dilakukan.
 */
class KehadiransRelationManager extends RelationManager
{
    protected static string $relationship = 'kehadirans';

    protected static ?string $title = 'Kehadiran Pertemuan Daring';

    public static function canViewForRecord($ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof Pelatihan && $ownerRecord->punyaPertemuan();
    }

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Kehadiran Pertemuan Daring')
            ->description('Warga yang menandai dirinya mengikuti pertemuan ini dari portal.')
            ->emptyStateHeading('Belum ada yang menandai hadir')
            ->emptyStateDescription('Daftar terisi setelah warga menekan "Saya Sudah Mengikuti" di portal.')
            ->columns([
                TextColumn::make('no')
                    ->label('No.')
                    ->rowIndex()
                    ->color('gray')
                    ->alignCenter(),

                TextColumn::make('user.name')
                    ->label('Warga')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('user.nagari.nama')
                    ->label('Nagari')
                    ->placeholder('Tidak diketahui')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),

                TextColumn::make('hadir_pada')
                    ->label('Ditandai')
                    ->dateTime('d M Y, H:i')
                    ->sortable()
                    ->alignCenter(),
            ])
            ->defaultSort('hadir_pada', 'desc')
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->modifyQueryUsing(fn ($query) => $query->with('user.nagari'))
            ->headerActions([
                ReportActionGroup::make(fn (): TabularReport => new TabularReport(
                    title: 'Daftar Kehadiran Pelatihan',
                    filename: 'kehadiran-'.$this->getOwnerRecord()->namaTampil(),
                    query: PelatihanAttendance::query()
                        ->where('pelatihan_id', $this->getOwnerRecord()->getKey())
                        ->when(auth()->user()?->isOperator(), fn ($query) => $query->whereHas('user', fn ($users) => $users->where('nagari_id', auth()->user()?->nagari_id)))
                        ->with('user.nagari'),
                    columns: [
                        new ReportColumn('user.name', 'Nama Warga', 30),
                        new ReportColumn('user.nagari.nama', 'Nagari', 25),
                        new ReportColumn('hadir_pada', 'Ditandai Hadir', 22, fn ($value): string => $value?->format('d/m/Y H:i') ?? '—'),
                    ],
                    metadata: ['Cakupan' => $this->getOwnerRecord()->namaTampil()],
                    orientation: 'portrait',
                )),
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }

    protected function getTableQuery(): ?\Illuminate\Database\Eloquent\Builder
    {
        return PelatihanAttendance::query()->where('pelatihan_id', $this->getOwnerRecord()->getKey());
    }
}
