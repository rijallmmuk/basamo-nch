<?php

namespace App\Filament\Resources\Pretests;

use App\Enums\JenisEvaluasi;
use App\Filament\Resources\Evaluasis\EvaluasiResource;
use App\Filament\Resources\Pretests\Pages\CreatePertanyaans;
use App\Filament\Resources\Pretests\Pages\CreatePretest;
use App\Filament\Resources\Pretests\Pages\EditPretest;
use App\Filament\Resources\Pretests\Pages\ListPretests;
use App\Filament\Resources\Pretests\Pages\ViewPretest;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

/** Menu Pre-test: gerbang sebelum materi, terpisah dari Evaluasi Kegiatan. */
class PretestResource extends EvaluasiResource
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'pre-test';

    public static function jenis(): JenisEvaluasi
    {
        return JenisEvaluasi::Pretest;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPretests::route('/'),
            'create' => CreatePretest::route('/create'),
            'create-questions' => CreatePertanyaans::route('/{record}/soal/create'),
            'view' => ViewPretest::route('/{record}'),
            'edit' => EditPretest::route('/{record}/edit'),
        ];
    }
}
