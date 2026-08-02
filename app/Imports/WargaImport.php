<?php

namespace App\Imports;

use App\Models\Nagari;
use App\Services\WargaImportService;
use App\Services\WargaProvisioningService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use OpenSpout\Reader\CSV\Reader as CsvReader;
use OpenSpout\Reader\XLSX\Reader as XlsxReader;
use RuntimeException;
use Throwable;

/**
 * Membaca file Excel/CSV warga dan membuat tiap baris lewat {@see WargaImportService}.
 * Baris gagal tidak menggagalkan keseluruhan — dicatat di {@see $errors} per nomor baris.
 *
 * Menggunakan OpenSpout agar pembacaan streaming sangat cepat dan hemat memori,
 * cocok untuk data puluhan ribu baris.
 */
class WargaImport
{
    private const UKURAN_BONGKAHAN = 500;

    public int $imported = 0;

    /** @var list<array{baris:int, nama:string, nik:string, pesan:string}> */
    public array $errors = [];

    /** @var array<string, true> NIK yang sudah diproses (deteksi duplikat dalam file). */
    private array $seenNik = [];

    private int $rowNumber = 0;

    public function __construct(
        private Nagari $nagari,
        private WargaImportService $service,
    ) {}

    public function import(string $path): void
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($ext === 'csv') {
            $reader = new CsvReader();
        } else {
            $reader = new XlsxReader();
        }

        $reader->open($path);

        $sheet = null;
        foreach ($reader->getSheetIterator() as $currentSheet) {
            // Selalu ambil sheet pertama
            $sheet = $currentSheet;
            break;
        }

        if ($sheet === null) {
            throw new RuntimeException('Berkas tidak memiliki sheet yang dapat dibaca.');
        }

        $headings = [];
        $rowsBuffer = [];

        foreach ($sheet->getRowIterator() as $rowNumber => $row) {
            $cells = $row->toArray();

            // Baris 1: Header
            if ($rowNumber === 1) {
                foreach ($cells as $cell) {
                    $headings[] = $this->normalizeHeading($cell);
                }
                continue;
            }

            // Gabungkan header dan sel menjadi associative array
            $data = [];
            foreach ($headings as $index => $heading) {
                if ($heading !== '') {
                    $data[$heading] = $cells[$index] ?? null;
                }
            }

            // Skip jika baris benar-benar kosong
            if (collect($data)->every(fn ($val) => blank($val))) {
                continue;
            }

            $data['__row_number'] = $rowNumber;
            $rowsBuffer[] = collect($data);

            if (count($rowsBuffer) >= self::UKURAN_BONGKAHAN) {
                $this->collection(collect($rowsBuffer));
                $rowsBuffer = []; // Reset buffer
            }
        }

        // Proses sisa buffer
        if (count($rowsBuffer) > 0) {
            $this->collection(collect($rowsBuffer));
        }

        $reader->close();
    }

    public function collection(Collection $rows): void
    {
        $this->rowNumber = $this->rowNumber ?: 1;

        $baris = $rows->all();

        // Satu kueri untuk seluruh bongkahan
        $nikTerdaftar = $this->service->nikTerdaftar(
            collect($baris)
                ->map(fn ($row): string => preg_replace('/\D/', '', (string) ($row['nik'] ?? '')) ?? '')
                ->filter(fn (string $nik): bool => $nik !== '')
                ->unique()
                ->values()
                ->all(),
        );

        $idAkunBaru = activity()->withoutLogging(function () use ($baris, $nikTerdaftar): array {
            $batchIdentities = [];
            $batchAccounts = [];

            foreach ($baris as $row) {
                $data = $row->toArray();
                $this->rowNumber = (int) ($data['__row_number'] ?? ($this->rowNumber + 1));
                unset($data['__row_number']);

                try {
                    $prepared = $this->service->prepareRow($data, $this->nagari, $this->seenNik, $nikTerdaftar);
                    $batchIdentities[] = $prepared['identity'];
                    $batchAccounts[] = $prepared['account'];
                } catch (Throwable $e) {
                    $this->errors[] = [
                        'baris' => $this->rowNumber,
                        'nama' => trim((string) ($data['nama'] ?? '')),
                        'nik' => trim((string) ($data['nik'] ?? '')),
                        'pesan' => $e->getMessage(),
                    ];
                }
            }

            $insertedUserIds = app(WargaProvisioningService::class)->bulkInsert($batchIdentities, $batchAccounts);
            $this->imported += count($insertedUserIds);

            return $insertedUserIds;
        });

        $this->service->pasangPeranWarga($idAkunBaru);
    }

    private function normalizeHeading(mixed $heading): string
    {
        // Spout Cell->getValue() gives DateTime for dates, string for string.
        // If it's an object (like DateTime), it shouldn't be a heading anyway.
        $val = is_object($heading) ? (method_exists($heading, 'format') ? $heading->format('Y-m-d') : '') : (string) $heading;
        return Str::slug(trim($val), '_');
    }
}
