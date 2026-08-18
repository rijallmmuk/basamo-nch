<?php

declare(strict_types=1);

namespace App\Support\Reports;

use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\AutoFilter;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Options;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ReportExporter
{
    public function xlsx(TabularReport $report, User $actor): StreamedResponse
    {
        $filename = $this->filename($report->filename, 'xlsx');

        return response()->streamDownload(function () use ($report, $actor): void {
            $options = new Options();
            $options->mergeCells(0, 1, max(0, count($report->columns) - 1), 1);
            $writer = new Writer($options);
            $writer->setCreator(config('app.name'));
            $writer->openToFile('php://output');

            $sheet = $writer->getCurrentSheet();
            $sheet->setName(Str::limit($report->sheetName, 31, ''));
            $sheet->setSheetView((new SheetView())->setFreezeRow(8));

            foreach ($report->columns as $index => $column) {
                $sheet->setColumnWidth((float) max(10, min($column->width, 55)), $index + 1);
            }

            $titleStyle = (new Style())
                ->setFontBold()
                ->setFontSize(16)
                ->setFontColor(Color::WHITE)
                ->setBackgroundColor('003857');
            $metaStyle = (new Style())->setFontColor('475569');
            $headerStyle = (new Style())
                ->setFontBold()
                ->setFontColor(Color::WHITE)
                ->setBackgroundColor('0F766E')
                ->setShouldWrapText();

            $writer->addRow(Row::fromValues([$report->title], $titleStyle));
            $writer->addRow(Row::fromValues(['Dibuat', now()->timezone(config('app.timezone'))->format('d/m/Y H:i').' WIB'], $metaStyle));
            $writer->addRow(Row::fromValues(['Oleh', $actor->name], $metaStyle));

            $metadata = collect($report->metadata)
                ->map(fn ($value, $label): string => "{$label}: {$value}")
                ->join(' · ');
            $writer->addRow(Row::fromValues(['Cakupan', $metadata ?: 'Sesuai hak akses dan filter aktif'], $metaStyle));
            $writer->addRow(Row::fromValues(['Catatan', 'Data mengikuti hak akses, pencarian, filter, dan urutan tabel saat diekspor.'], $metaStyle));
            $writer->addRow(new Row([], $metaStyle));
            $writer->addRow(Row::fromValues(array_map(fn (ReportColumn $column): string => $column->label, $report->columns), $headerStyle));

            $count = 0;

            foreach ((clone $report->query)->lazy(500) as $record) {
                $cells = array_map(
                    fn (ReportColumn $column): Cell => Cell::fromValue($this->spreadsheetValue($column->value($record))),
                    $report->columns,
                );
                $writer->addRow(new Row($cells));
                $count++;
            }

            $lastRow = max(7, 7 + $count);
            $sheet->setAutoFilter(new AutoFilter(0, 7, count($report->columns) - 1, $lastRow));
            $sheet->setPrintTitleRows('1:7');
            $writer->close();

            $this->audit($report, $actor, 'xlsx', $count);
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'private, no-store, max-age=0',
        ]);
    }

    public function pdf(TabularReport $report, User $actor): StreamedResponse
    {
        $records = (clone $report->query)->get();
        $rows = $records->map(fn ($record): array => array_map(
            fn (ReportColumn $column): mixed => $column->value($record),
            $report->columns,
        ));

        $pdf = Pdf::loadView('reports.tabular', [
            'report' => $report,
            'rows' => $rows,
            'actor' => $actor,
            'generatedAt' => now(),
        ])->setPaper('a4', $report->orientation);

        return PdfDownload::make(
            $pdf,
            $this->filename($report->filename, 'pdf'),
            fn () => $this->audit($report, $actor, 'pdf', $rows->count()),
        );
    }

    private function spreadsheetValue(mixed $value): string|int|float|bool|null
    {
        if ($value instanceof \BackedEnum) {
            return (string) $value->value;
        }

        if ($value instanceof \Stringable) {
            return (string) $value;
        }

        if (is_array($value)) {
            return implode(', ', array_filter(array_map('strval', $value)));
        }

        return is_scalar($value) || $value === null ? $value : (string) $value;
    }

    private function filename(string $base, string $extension): string
    {
        $safe = Str::of($base)->lower()->ascii()->replaceMatches('/[^a-z0-9]+/', '-')->trim('-');

        return ($safe->isEmpty() ? 'laporan' : $safe).'-'.now()->format('Y-m-d').'.'.$extension;
    }

    private function audit(TabularReport $report, User $actor, string $format, int $count): void
    {
        activity('ekspor')
            ->causedBy($actor)
            ->event('exported')
            ->withProperties([
                'laporan' => $report->title,
                'format' => $format,
                'jumlah_baris' => $count,
                'cakupan' => $report->metadata['Cakupan'] ?? null,
            ])
            ->log("Ekspor {$report->title} ({$format}, {$count} baris)");
    }
}
