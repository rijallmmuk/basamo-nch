<?php

declare(strict_types=1);

namespace App\Support\Reports;

use App\Models\Nagari;
use App\Models\Penduduk;
use App\Models\User;
use App\Support\Dashboard\DemografiData;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\AutoFilter;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class DemographicReportExporter
{
    public function xlsx(Nagari $nagari, User $actor): StreamedResponse
    {
        return response()->streamDownload(function () use ($nagari, $actor): void {
            $writer = new Writer();
            $writer->setCreator(config('app.name'));
            $writer->openToFile('php://output');
            $datasets = $this->datasets($nagari);
            $first = true;

            foreach ($datasets as $name => $rows) {
                $sheet = $first ? $writer->getCurrentSheet() : $writer->addNewSheetAndMakeItCurrent();
                $first = false;
                $sheet->setName(Str::limit($name, 31, ''));
                $sheet->setSheetView((new SheetView())->setFreezeRow(5));
                $sheet->setColumnWidth(34, 1);
                $sheet->setColumnWidth(16, 2);
                $writer->addRow(Row::fromValues(['Laporan Demografi · '.$nagari->nama], (new Style())->setFontBold()->setFontSize(15)->setFontColor(Color::WHITE)->setBackgroundColor('003857')));
                $writer->addRow(Row::fromValues(['Kategori', $name]));
                $writer->addRow(Row::fromValues(['Dibuat', now()->format('d/m/Y H:i').' WIB']));
                $writer->addRow(Row::fromValues(['Kelompok', 'Jumlah'], (new Style())->setFontBold()->setFontColor(Color::WHITE)->setBackgroundColor('0F766E')));
                foreach ($rows as $label => $jumlah) {
                    $writer->addRow(Row::fromValues([(string) $label, (int) $jumlah]));
                }
                $sheet->setAutoFilter(new AutoFilter(0, 4, 1, max(4, 4 + $rows->count())));
            }

            $writer->close();
            $this->audit($nagari, $actor, 'xlsx');
        }, 'laporan-demografi-'.Str::slug($nagari->nama).'-'.now()->format('Y-m-d').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    public function pdf(Nagari $nagari, User $actor): StreamedResponse
    {
        $pdf = Pdf::loadView('reports.demographics', [
            'nagari' => $nagari,
            'datasets' => $this->datasets($nagari),
            'generatedAt' => now(),
            'actor' => $actor,
        ])->setPaper('a4', 'portrait');

        return PdfDownload::make(
            $pdf,
            'laporan-demografi-'.Str::slug($nagari->nama).'-'.now()->format('Y-m-d').'.pdf',
            fn () => $this->audit($nagari, $actor, 'pdf'),
        );
    }

    /** @return array<string, Collection<string, int>> */
    private function datasets(Nagari $nagari): array
    {
        $id = $nagari->getKey();
        $summary = DemografiData::ringkasan($id);

        return [
            'Ringkasan' => collect([
                'Penduduk terdata' => $summary['penduduk'],
                'Akun portal' => $summary['akun_portal'],
                'Laki-laki' => $summary['laki_laki'],
                'Perempuan' => $summary['perempuan'],
                'Jenis kelamin belum terdata' => $summary['belum_terdata'],
                'Tanggal lahir belum terdata' => DemografiData::tanpaTanggalLahir($id),
            ]),
            'Kelompok Umur' => DemografiData::kelompokUmur($id),
            'Pendidikan' => DemografiData::distribusiPendidikan($id),
            'Pekerjaan' => DemografiData::distribusiPekerjaan($id),
            'Agama' => $this->referenceCounts($id, 'agama', 'agama_id'),
            'Status Perkawinan' => $this->referenceCounts($id, 'status_perkawinan', 'status_perkawinan_id'),
        ];
    }

    private function referenceCounts(int $nagariId, string $relation, string $foreignKey): Collection
    {
        return Penduduk::query()
            ->where('penduduk.nagari_id', $nagariId)
            ->leftJoin($relation, "penduduk.{$foreignKey}", '=', "{$relation}.id")
            ->selectRaw("COALESCE({$relation}.nama, 'Belum terdata') as label, COUNT(*) as jumlah")
            ->groupBy('label')
            ->orderByDesc('jumlah')
            ->pluck('jumlah', 'label')
            ->map(fn ($value): int => (int) $value);
    }

    private function audit(Nagari $nagari, User $actor, string $format): void
    {
        activity('ekspor')->causedBy($actor)->performedOn($nagari)->event('exported')
            ->withProperties(['laporan' => 'Demografi', 'format' => $format, 'nagari_id' => $nagari->getKey()])
            ->log("Ekspor laporan demografi ({$format})");
    }
}
