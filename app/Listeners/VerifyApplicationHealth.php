<?php

namespace App\Listeners;

use App\Services\ProductionHealthService;
use Illuminate\Foundation\Events\DiagnosingHealth;

class VerifyApplicationHealth
{
    /**
     * Create the event listener.
     */
    public function __construct(private readonly ProductionHealthService $health) {}

    /**
     * Handle the event.
     */
    public function handle(DiagnosingHealth $event): void
    {
        $this->health->check();
    }
}
