<?php

namespace App\Observers;

use App\Services\SharedAccountSessionService;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityObserver
{
    public function __construct(
        private readonly SharedAccountSessionService $sharedSession,
        private readonly Request $request,
    ) {}

    public function creating(Activity $activity): void
    {
        $context = $this->sharedSession->auditContext($this->request);

        if ($context !== []) {
            $activity->properties = $activity->properties->put('session', $context);
        }
    }
}
