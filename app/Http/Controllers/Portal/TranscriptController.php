<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Support\Reports\SlcTranscriptExporter;
use Symfony\Component\HttpFoundation\Response;

final class TranscriptController extends Controller
{
    public function __invoke(SlcTranscriptExporter $exporter): Response
    {
        $warga = auth()->user();
        abort_unless($warga?->hasRole('warga'), 403);

        return $exporter->download($warga, $warga, $warga->nagari_id);
    }
}
