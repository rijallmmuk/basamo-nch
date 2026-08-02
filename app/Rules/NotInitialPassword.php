<?php

namespace App\Rules;

use App\Services\InitialPasswordService;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class NotInitialPassword implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $initialPasswords = app(InitialPasswordService::class)->allInitialPasswords();

        if (is_string($value)) {
            foreach ($initialPasswords as $initial) {
                if (hash_equals($initial, $value)) {
                    $fail('Kata sandi baru tidak boleh menggunakan kata sandi awal default.');

                    return;
                }
            }
        }
    }
}
