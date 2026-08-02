<?php

namespace App\Filament\Resources\Pelatihans\RelationManagers;

use App\Filament\Resources\Modules\ModuleResource;
use App\Filament\Resources\Modules\Tables\ModulesTable;
use App\Models\Module;
use App\Models\Pelatihan;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ModulesRelationManager extends RelationManager
{
    protected static string $relationship = 'modules';

    protected static ?string $title = 'Daftar Modul';

    protected static ?string $recordTitleAttribute = 'judul';

    public function isReadOnly(): bool
    {
        /** @var Pelatihan $program */
        $program = $this->getOwnerRecord();

        return $program->trashed()
            || ! (auth()->user()?->can('kelolaKonten', $program) ?? false);
    }

    public function form(Schema $schema): Schema
    {
        return ModuleResource::form($schema);
    }

    public function table(Table $table): Table
    {
        return ModulesTable::configure($table, withinPelatihan: true)
            ->heading('Daftar Modul')
            ->description('Modul-modul yang berada di dalam pelatihan ini.')
            ->authorizeReorder(fn (): bool => auth()->user()?->can('kelolaKonten', $this->getOwnerRecord()) ?? false)
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Modul')
                    ->color('primary')
                    ->authorize(fn (): bool => ! $this->getOwnerRecord()->trashed()
                        && (auth()->user()?->can('create', Module::class) ?? false)
                        && (auth()->user()?->can('kelolaKonten', $this->getOwnerRecord()) ?? false))
                    ->url(fn (): string => ModuleResource::getUrl('create', ['pelatihan' => $this->getOwnerRecord()->id])),
            ]);
    }
}
