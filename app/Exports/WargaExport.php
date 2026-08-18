<?php

namespace App\Exports;

use App\Models\Nagari;
use App\Models\Penduduk;
use Illuminate\Database\Eloquent\Builder;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Ekspor warga satu nagari, kolom PERSIS sama dengan {@see WargaTemplateBuilder}/
 * {@see WargaImportService} — supaya file hasil ekspor ini bisa diimpor ulang
 * apa adanya (round-trip) maupun disandingkan dengan ekspor mentah OpenSID nagari.
 */
class WargaExport
{
    public function __construct(private Nagari $nagari) {}

    public function query(): Builder
    {
        return Penduduk::query()
            ->forNagari($this->nagari->id)
            ->with('user')
            ->orderBy('nama');
    }

    /** @return list<string> */
    public function headings(): array
    {
        return ['nama', 'nik', 'sex', 'tempatlahir', 'tanggallahir', 'agama_id', 'pendidikan_id', 'pekerjaan_id', 'status_kawin_id'];
    }

    /** @return list<Cell> */
    public function map(mixed $warga): array
    {
        $sex = match ($warga->jenis_kelamin?->value) {
            'L' => 1,
            'P' => 2,
            default => null,
        };

        return [
            Cell::fromValue($warga->nama),
            // Explicitly cast to string so OpenSpout makes it a StringCell
            Cell::fromValue((string) $warga->nik),
            Cell::fromValue($sex),
            Cell::fromValue($warga->tempat_lahir),
            // Explicitly cast to string so Date doesn't get messed up by Excel auto-formatting
            Cell::fromValue((string) $warga->tanggal_lahir?->toDateString()),
            Cell::fromValue($warga->agama_id),
            Cell::fromValue($warga->pendidikan_id),
            Cell::fromValue($warga->pekerjaan_id),
            Cell::fromValue($warga->status_perkawinan_id),
        ];
    }

    public function download(string $filename = 'data-warga.xlsx'): StreamedResponse
    {
        return response()->streamDownload(function (): void {
            $writer = new Writer();
            $writer->openToFile('php://output');

            $headerStyle = (new Style())->setFontBold();
            $headerRow = Row::fromValues($this->headings(), $headerStyle);
            $writer->addRow($headerRow);

            // Use lazy collection (chunked) to prevent memory exhaustion
            foreach ($this->query()->lazy(500) as $warga) {
                $writer->addRow(new Row($this->map($warga)));
            }

            $writer->close();

            if ($actor = auth()->user()) {
                activity('ekspor')
                    ->causedBy($actor)
                    ->performedOn($this->nagari)
                    ->event('exported')
                    ->withProperties([
                        'laporan' => 'Data Warga untuk Impor Ulang',
                        'format' => 'xlsx',
                        'nagari_id' => $this->nagari->getKey(),
                    ])
                    ->log('Ekspor data warga untuk impor ulang (xlsx)');
            }
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
