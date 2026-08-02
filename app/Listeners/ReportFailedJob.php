<?php

namespace App\Listeners;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Log;

class ReportFailedJob
{
    public function handle(JobFailed $event): void
    {
        Log::critical('Queue job gagal.', [
            'connection' => $event->connectionName,
            'queue' => $event->job->getQueue(),
            'job' => $event->job->resolveName(),
            'exception' => $event->exception,
        ]);

        report($event->exception);
    }
}
