<?php

namespace App\Listeners;

use App\Models\User;
use App\Services\SharedAccountSessionService;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\Request;

class RecordSharedAccountLogout
{
    public function __construct(
        private readonly SharedAccountSessionService $sharedSession,
        private readonly Request $request,
    ) {}

    /**
     * Handle the event.
     */
    public function handle(Logout $event): void
    {
        if (
            $event->user instanceof User
            && $event->user->hasAnyRole(['superadmin', 'operator'])
        ) {
            $this->sharedSession->finish($event->user, $this->request);
        }
    }
}
