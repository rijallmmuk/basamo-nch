<?php

declare(strict_types=1);

namespace App\Support\Reports;

use App\Models\User;
use App\Services\SlcRekapService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class SlcTranscriptExporter
{
    public function download(User $warga, User $actor, ?int $nagariId): StreamedResponse
    {
        $rekap = app(SlcRekapService::class)->detailFor($warga, $actor, $nagariId);
        $filename = 'transkrip-belajar-'.Str::slug($warga->name).'-'.now()->format('Y-m-d').'.pdf';

        $pdf = Pdf::loadView('reports.slc-transcript', [
            'rekap' => $rekap,
            'actor' => $actor,
            'generatedAt' => now(),
        ])->setPaper('a4', 'landscape');

        return PdfDownload::make($pdf, $filename, function () use ($actor, $warga): void {
            activity('ekspor')
                ->causedBy($actor)
                ->performedOn($warga)
                ->event('exported')
                ->withProperties(['laporan' => 'Transkrip Belajar Warga', 'format' => 'pdf', 'warga_id' => $warga->getKey()])
                ->log('Ekspor transkrip belajar warga (pdf)');
        });
    }
}
