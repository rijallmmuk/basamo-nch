<?php

namespace App\Listeners;

use Illuminate\Queue\Events\QueueBusy;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class ReportBusyQueue
{
    public function handle(QueueBusy $event): void
    {
        $exception = new RuntimeException(
            "Queue {$event->connectionName}:{$event->queue} memiliki {$event->size} job tertunda.",
        );

        Log::critical($exception->getMessage(), [
            'connection' => $event->connectionName,
            'queue' => $event->queue,
            'size' => $event->size,
        ]);

        report($exception);
    }
}
