<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\ScopedToNagari;
use App\Models\UserModuleProgress;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class OperatorWargaActivityWidget extends BaseWidget
{
    protected ?string $pollingInterval = null;

    use ScopedToNagari;

    protected static ?int $sort = 5;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $heading = 'Aktivitas Belajar Terkini Warga';

    public function table(Table $table): Table
    {
        $nagariId = $this->nagariId();

        return $table
            ->description('Warga '.$this->namaNagari().'.')
            ->query(
                UserModuleProgress::query()
                    ->whereHas('user', fn ($q) => $q->where('nagari_id', $nagariId))
                    ->with(['user', 'module'])
                    ->latest('updated_at')
            )
            ->columns([
                TextColumn::make('no')
                    ->label('No.')
                    ->rowIndex()
                    ->alignCenter(),

                TextColumn::make('user.name')
                    ->label('Nama Warga')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('module.judul')
                    ->label('Modul')
                    ->wrap(),

                TextColumn::make('status')
                    ->label('Status Progres')
                    ->badge()
                    ->alignCenter(),

                TextColumn::make('updated_at')
                    ->label('Waktu Akses Terakhir')
                    ->dateTime('d M Y, H:i')
                    ->sortable(),
            ])
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5);
    }
}
