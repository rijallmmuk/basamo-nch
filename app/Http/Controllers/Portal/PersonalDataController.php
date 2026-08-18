<?php

declare(strict_types=1);

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Support\Reports\PdfDownload;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class PersonalDataController extends Controller
{
    public function __invoke(): StreamedResponse
    {
        $user = auth()->user()?->load([
            'nagari', 'penduduk.agama', 'penduduk.pendidikan',
            'penduduk.statusPerkawinan', 'penduduk.pekerjaan',
        ]);
        abort_unless($user?->hasRole('warga'), 403);

        $pdf = Pdf::loadView('reports.personal-data', [
            'user' => $user,
            'generatedAt' => now(),
        ])->setPaper('a4', 'portrait');

        return PdfDownload::make(
            $pdf,
            'data-saya-'.Str::slug($user->name).'-'.now()->format('Y-m-d').'.pdf',
            function () use ($user): void {
                activity('ekspor')->causedBy($user)->performedOn($user)->event('exported')
                    ->withProperties(['laporan' => 'Data Pribadi', 'format' => 'pdf'])
                    ->log('Warga mengunduh data pribadinya');
            },
        );
    }
}
