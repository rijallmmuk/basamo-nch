<?php

declare(strict_types=1);

namespace App\Support\Reports;

use Illuminate\Database\Eloquent\Builder;

final readonly class TabularReport
{
    /**
     * @param list<ReportColumn> $columns
     * @param array<string, string> $metadata
     */
    public function __construct(
        public string $title,
        public string $filename,
        public Builder $query,
        public array $columns,
        public array $metadata = [],
        public string $orientation = 'landscape',
        public string $sheetName = 'Data',
    ) {}
}
