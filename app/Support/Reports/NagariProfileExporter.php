<?php

declare(strict_types=1);

namespace App\Support\Reports;

use App\Models\Module;
use App\Models\Nagari;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class NagariProfileExporter
{
    public function download(Nagari $nagari, User $actor): StreamedResponse
    {
        $nagari->loadMissing(['operator', 'latestIdmStatus']);
        $metrics = [
            'warga' => $nagari->warga()->count(),
            'umkm' => $nagari->umkmProfiles()->count(),
            'modul' => Module::query()->whereIn('pelatihan_id', $nagari->pelatihans()->select('pelatihans.id'))->count(),
            'sdgs' => $nagari->sdgAchievements()->avg('persentase'),
        ];

        $pdf = Pdf::loadView('reports.nagari-profile', [
            'nagari' => $nagari,
            'metrics' => $metrics,
            'actor' => $actor,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        return PdfDownload::make(
            $pdf,
            'profil-nagari-'.Str::slug($nagari->nama).'-'.now()->format('Y-m-d').'.pdf',
            function () use ($actor, $nagari): void {
                activity('ekspor')->causedBy($actor)->performedOn($nagari)->event('exported')
                    ->withProperties(['laporan' => 'Profil Nagari', 'format' => 'pdf', 'nagari_id' => $nagari->getKey()])
                    ->log('Ekspor profil nagari (pdf)');
            },
        );
    }
}
