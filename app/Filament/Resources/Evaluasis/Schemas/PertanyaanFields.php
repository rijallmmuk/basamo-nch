<?php

namespace App\Filament\Resources\Evaluasis\Schemas;

use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;

class PertanyaanFields
{
    /** @return array<int, Component> */
    public static function make(bool $withRelationships = false, bool $withIds = false): array
    {
        $opsis = Repeater::make('opsis')
            ->label('Pilihan Jawaban')
            ->helperText('Isi 2 sampai 5 pilihan, lalu tandai minimal satu jawaban benar dan satu jawaban salah.')
            ->orderColumn('urutan')
            ->schema([
                ...($withIds ? [Hidden::make('id')] : []),

                TextInput::make('teks_opsi')
                    ->label('Pilihan')
                    ->placeholder('Isi pilihan jawaban')
                    ->required()
                    ->columnSpan(5),

                Toggle::make('is_correct')
                    ->label('Benar')
                    ->inline(false)
                    ->columnSpan(1),
            ])
            ->columns(6)
            ->defaultItems(2)
            ->minItems(2)
            ->maxItems(5)
            ->addActionLabel('Tambah Pilihan')
            ->cloneable(false)
            ->rule(fn () => function (string $attribute, $value, \Closure $fail): void {
                $options = collect($value);
                $correct = $options->filter(fn ($option) => ! empty($option['is_correct']))->count();

                if ($correct < 1) {
                    $fail('Tandai minimal satu pilihan sebagai jawaban benar.');
                } elseif ($correct >= $options->count()) {
                    $fail('Minimal satu pilihan harus salah.');
                }
            })
            ->columnSpanFull();

        if ($withRelationships) {
            $opsis->relationship('opsis', fn ($query) => $query->orderBy('urutan'));
        }

        return [
            Textarea::make('pertanyaan')
                ->label('Soal')
                ->placeholder('Isi soal')
                ->required()
                ->rows(3)
                ->columnSpanFull(),

            $opsis,
        ];
    }
}
