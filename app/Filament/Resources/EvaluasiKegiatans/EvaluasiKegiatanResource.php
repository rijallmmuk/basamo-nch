<?php

namespace App\Filament\Resources\EvaluasiKegiatans;

use App\Enums\JenisEvaluasi;
use App\Filament\Resources\EvaluasiKegiatans\Pages\CreateEvaluasiKegiatan;
use App\Filament\Resources\EvaluasiKegiatans\Pages\CreatePertanyaans;
use App\Filament\Resources\EvaluasiKegiatans\Pages\EditEvaluasiKegiatan;
use App\Filament\Resources\EvaluasiKegiatans\Pages\ListEvaluasiKegiatans;
use App\Filament\Resources\EvaluasiKegiatans\Pages\ViewEvaluasiKegiatan;
use App\Filament\Resources\Evaluasis\EvaluasiResource;
use BackedEnum;
use Filament\Support\Icons\Heroicon;

/** Menu Evaluasi Kegiatan: penutup modul, terpisah dari Pre-test. */
class EvaluasiKegiatanResource extends EvaluasiResource
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'evaluasi-kegiatan';

    public static function jenis(): JenisEvaluasi
    {
        return JenisEvaluasi::Kegiatan;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListEvaluasiKegiatans::route('/'),
            'create' => CreateEvaluasiKegiatan::route('/create'),
            'create-questions' => CreatePertanyaans::route('/{record}/soal/create'),
            'view' => ViewEvaluasiKegiatan::route('/{record}'),
            'edit' => EditEvaluasiKegiatan::route('/{record}/edit'),
        ];
    }
}
