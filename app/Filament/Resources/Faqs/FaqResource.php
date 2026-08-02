<?php

namespace App\Filament\Resources\Faqs;

use App\Filament\Resources\Faqs\Pages\ManageFaqs;
use App\Models\Faq;
use BackedEnum;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * FAQ beranda publik (base URL) — hanya superadmin, konten platform (bukan
 * per-nagari). Sinkron langsung: HomeController membaca tabel ini apa adanya.
 */
class FaqResource extends Resource
{
    protected static ?string $model = Faq::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedQuestionMarkCircle;

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return 'Situs Publik';
    }

    public static function canAccess(): bool
    {
        return (auth()->user()?->isSuperAdmin() ?? false) && parent::canAccess();
    }

    public static function getModelLabel(): string
    {
        return 'FAQ';
    }

    public static function getPluralModelLabel(): string
    {
        return 'FAQ';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Pertanyaan & Jawaban')
                ->icon(Heroicon::OutlinedQuestionMarkCircle)
                ->columnSpanFull()
                ->schema([
                    Textarea::make('pertanyaan')
                        ->label('Pertanyaan')
                        ->required()
                        ->maxLength(255)
                        ->rows(2)
                        ->columnSpanFull(),

                    Textarea::make('jawaban')
                        ->label('Jawaban')
                        ->required()
                        ->rows(4)
                        ->columnSpanFull(),

                    Toggle::make('aktif')
                        ->label('Tampilkan di beranda')
                        ->default(true)
                        ->helperText('Nonaktifkan untuk menyembunyikan sementara tanpa menghapus.'),

                    // Urutan TIDAK diisi lewat form: FAQ baru otomatis di urutan
                    // terakhir; mengubah urutan = seret baris di tabel.
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no')
                    ->label('No.')
                    ->rowIndex()
                    ->alignCenter(),

                TextColumn::make('pertanyaan')
                    ->label('Pertanyaan')
                    ->searchable()
                    ->wrap()
                    ->limit(80),

                IconColumn::make('aktif')
                    ->label('Tampil')
                    ->boolean()
                    ->alignCenter(),
            ])
            ->defaultSort('urutan')
            ->reorderable('urutan')
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(10)
            ->recordActions([
                ActionGroup::make([
                    EditAction::make()
                        ->color('warning'),
                    DeleteAction::make(),
                ])
                    ->label('Aksi')
                    ->icon('heroicon-o-ellipsis-vertical')
                    ->button()
                    ->color('gray'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageFaqs::route('/'),
        ];
    }
}
