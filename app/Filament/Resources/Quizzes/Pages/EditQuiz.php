<?php

namespace App\Filament\Resources\Quizzes\Pages;

use App\Filament\Resources\Concerns\RedirectsToIndex;
use App\Filament\Resources\Quizzes\QuizResource;
use Filament\Resources\Pages\EditRecord;

class EditQuiz extends EditRecord
{
    use RedirectsToIndex;

    protected static string $resource = QuizResource::class;
}
