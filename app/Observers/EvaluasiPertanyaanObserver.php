<?php

namespace App\Observers;

use App\Models\EvaluasiPertanyaan;
use App\Services\SlcEvaluasiNotificationService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class EvaluasiPertanyaanObserver implements ShouldHandleEventsAfterCommit
{
    public function created(EvaluasiPertanyaan $pertanyaan): void
    {
        $this->notify($pertanyaan);
    }

    public function updated(EvaluasiPertanyaan $pertanyaan): void
    {
        $this->notify($pertanyaan);
    }

    public function deleted(EvaluasiPertanyaan $pertanyaan): void
    {
        $this->notify($pertanyaan);
    }

    private function notify(EvaluasiPertanyaan $pertanyaan): void
    {
        if ($pertanyaan->evaluasi) {
            app(SlcEvaluasiNotificationService::class)->notifyWhenReady($pertanyaan->evaluasi);
        }
    }
}
