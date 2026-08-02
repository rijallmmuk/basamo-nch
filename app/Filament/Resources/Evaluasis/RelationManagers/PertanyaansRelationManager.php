<?php

namespace App\Filament\Resources\Evaluasis\RelationManagers;

use App\Filament\Resources\Evaluasis\Schemas\PertanyaanFields;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PertanyaansRelationManager extends RelationManager
{
    protected static string $relationship = 'pertanyaans';

    protected static ?string $title = 'Daftar Soal';

    public function isReadOnly(): bool
    {
        return ! $this->canMutateQuestions();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components(PertanyaanFields::make(withRelationships: true))
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('pertanyaan')
            ->reorderable('urutan', $this->canMutateQuestions())
            ->defaultSort('urutan', 'asc')
            ->columns([
                TextColumn::make('urutan')
                    ->label('#')
                    ->width('40px')
                    ->alignCenter(),

                TextColumn::make('pertanyaan')
                    ->label('Soal')
                    ->limit(80)
                    ->wrap()
                    ->searchable(),

                TextColumn::make('opsis_count')
                    ->counts('opsis')
                    ->label('Pilihan')
                    ->badge()
                    ->color('info')
                    ->alignCenter(),
            ])
            ->filters([])
            ->headerActions([
                Action::make('create')
                    ->color('primary')
                    ->label('Tambah Soal')
                    ->authorize(fn (): bool => $this->canMutateQuestions())
                    ->url(function (): string {
                        $pageClass = $this->getPageClass();
                        $resource = $pageClass::getResource();

                        return $resource::getUrl('create-questions', [
                            'record' => $this->getOwnerRecord(),
                        ]);
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->authorize(fn (): bool => $this->canMutateQuestions())
                        ->color('warning'),
                    DeleteAction::make()
                        ->authorize(fn (): bool => $this->canMutateQuestions()),
                ])
                    ->label('Aksi')
                    ->icon('heroicon-o-squares-2x2')
                    ->button()
                    ->color('gray'),
            ]);
    }

    private function canMutateQuestions(): bool
    {
        return ! $this->getOwnerRecord()->trashed()
            && (auth()->user()?->can('update', $this->getOwnerRecord()) ?? false);
    }
}
