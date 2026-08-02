<?php

namespace App\Models\Concerns;

use Illuminate\Support\Collection;
use Spatie\Activitylog\Models\Activity;

trait RedactsSensitiveActivityProperties
{
    public function beforeActivityLogged(Activity $activity, string $eventName): void
    {
        $changes = $activity->attribute_changes;

        foreach (['attributes', 'old'] as $propertyGroup) {
            $values = $changes->get($propertyGroup);

            if (! is_array($values)) {
                continue;
            }

            foreach ($this->sensitiveActivityAttributes() as $attribute) {
                if (array_key_exists($attribute, $values)) {
                    $values[$attribute] = '[DISEMBUNYIKAN]';
                }
            }

            $changes->put($propertyGroup, $values);
        }

        $activity->attribute_changes = new Collection($changes);
    }

    /** @return list<string> */
    abstract protected function sensitiveActivityAttributes(): array;
}
