<?php

namespace App\Observers;

use App\Models\EvaluasiOpsi;
use App\Services\SlcEvaluasiNotificationService;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class EvaluasiOpsiObserver implements ShouldHandleEventsAfterCommit
{
    public function created(EvaluasiOpsi $opsi): void
    {
        $this->notify($opsi);
    }

    public function updated(EvaluasiOpsi $opsi): void
    {
        $this->notify($opsi);
    }

    public function deleted(EvaluasiOpsi $opsi): void
    {
        $this->notify($opsi);
    }

    private function notify(EvaluasiOpsi $opsi): void
    {
        $evaluasi = $opsi->pertanyaan?->evaluasi;

        if ($evaluasi) {
            app(SlcEvaluasiNotificationService::class)->notifyWhenReady($evaluasi);
        }
    }
}
