<?php

namespace App\Filament\Resources\Quizzes\Pages;

use App\Filament\Resources\Concerns\RedirectsToIndex;
use App\Filament\Resources\Quizzes\QuizResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQuiz extends CreateRecord
{
    use RedirectsToIndex;

    protected static string $resource = QuizResource::class;
}
