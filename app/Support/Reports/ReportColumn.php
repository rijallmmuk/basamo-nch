<?php

declare(strict_types=1);

namespace App\Support\Reports;

use Closure;

final readonly class ReportColumn
{
    public function __construct(
        public string $key,
        public string $label,
        public int $width = 18,
        public ?Closure $format = null,
    ) {}

    public function value(mixed $record): mixed
    {
        $value = data_get($record, $this->key);

        return $this->format ? ($this->format)($value, $record) : $value;
    }
}
