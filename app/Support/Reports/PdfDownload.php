<?php

declare(strict_types=1);

namespace App\Support\Reports;

use Barryvdh\DomPDF\PDF;
use Closure;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PdfDownload
{
    public static function make(PDF $pdf, string $filename, ?Closure $afterDownload = null): StreamedResponse
    {
        return response()->streamDownload(function () use ($pdf, $afterDownload): void {
            echo $pdf->output();
            $afterDownload?->__invoke();
        }, $filename, [
            'Content-Type' => 'application/pdf',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }
}
