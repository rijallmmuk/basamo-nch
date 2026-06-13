<?php

namespace App\Filament\Resources\Quizzes\Pages;

use App\Filament\Resources\Quizzes\QuizAttemptResource;
use App\Filament\Resources\Quizzes\RelationManagers\AnswersRelationManager;
use App\Filament\Resources\Quizzes\Schemas\QuizAttemptInfoSchema;
use App\Services\LmsEssayGradingService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class ReviewQuizAttempt extends EditRecord
{
    protected static string $resource = QuizAttemptResource::class;

    public function form(Schema $schema): Schema
    {
        return QuizAttemptInfoSchema::configure($schema);
    }

    protected function getFormActions(): array
    {
        return [];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('finalize_review')
                ->label('Selesai Review')
                ->icon(Heroicon::OutlinedCheckCircle)
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Selesaikan Review?')
                ->modalDescription('Nilai akhir akan dihitung dari semua jawaban. Pastikan semua soal essay sudah dinilai.')
                ->modalSubmitActionLabel('Ya, Selesaikan')
                ->action(function (): void {
                    $service = app(LmsEssayGradingService::class);

                    if ($service->hasUngradedEssays($this->record)) {
                        Notification::make()
                            ->warning()
                            ->title('Masih ada jawaban essay yang belum dinilai')
                            ->send();

                        return;
                    }

                    $result = $service->finalize($this->record, auth()->user());

                    $label = $result->status === 'passed' ? '✅ Lulus' : '❌ Tidak Lulus';

                    Notification::make()
                        ->success()
                        ->title("Review selesai — {$label} ({$result->score}%)")
                        ->send();

                    $this->redirect(static::getResource()::getUrl('index'));
                })
                ->visible(fn (): bool => $this->record->status === 'pending_review'),
        ];
    }

    public function getRelationManagers(): array
    {
        return [
            AnswersRelationManager::class,
        ];
    }
}
