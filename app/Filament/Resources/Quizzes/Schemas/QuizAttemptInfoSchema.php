<?php

namespace App\Filament\Resources\Quizzes\Schemas;

use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Schema;

class QuizAttemptInfoSchema
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Placeholder::make('warga')
                    ->label('Warga')
                    ->content(fn ($record) => $record?->user?->name ?? '-'),

                Placeholder::make('kuis')
                    ->label('Kuis')
                    ->content(fn ($record) => $record?->quiz?->title ?? '-'),

                Placeholder::make('modul')
                    ->label('Modul')
                    ->content(fn ($record) => $record?->quiz?->module?->title ?? '-'),

                Placeholder::make('submitted_at')
                    ->label('Dikirim pada')
                    ->content(fn ($record) => $record?->submitted_at?->isoFormat('D MMMM YYYY, HH:mm') ?? '-'),

                Placeholder::make('status_ujian')
                    ->label('Status')
                    ->content(fn ($record) => match ($record?->status) {
                        'pending_review' => '⏳ Menunggu Review',
                        'passed' => '✅ Lulus',
                        'failed' => '❌ Tidak Lulus',
                        'in_progress' => '🔄 Sedang Dikerjakan',
                        default => '-',
                    }),

                Placeholder::make('nilai_akhir')
                    ->label('Nilai Akhir')
                    ->content(fn ($record) => $record?->score !== null ? $record->score.'%' : '-'),
            ])
            ->columns(3);
    }
}
