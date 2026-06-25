<?php

namespace App\Imports;

use App\Models\Desa;
use App\Services\WargaImportService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Throwable;

/**
 * Membaca file Excel/CSV warga dan membuat tiap baris lewat {@see WargaImportService}.
 * Baris gagal tidak menggagalkan keseluruhan — dicatat di {@see $errors} per nomor baris.
 *
 * Sengaja TIDAK chunk-reading: file dibatasi ukuran di sisi unggah (FileUpload),
 * dan memproses sekaligus menjaga nomor baris tetap akurat untuk laporan error.
 *
 * WithMultipleSheets membatasi pembacaan ke sheet pertama ("Data Warga") saja —
 * tanpa ini maatwebsite membaca SEMUA sheet (Petunjuk & Referensi ikut diproses
 * sebagai data dan gagal massal).
 */
class WargaImport implements ToCollection, WithHeadingRow, WithMultipleSheets
{
    public int $imported = 0;

    /** @var list<array{baris:int, pesan:string}> */
    public array $errors = [];

    /** @var array<string, true> NIK yang sudah diproses (deteksi duplikat dalam file). */
    private array $seenNik = [];

    public function __construct(
        private Desa $desa,
        private WargaImportService $service,
    ) {}

    public function collection(Collection $rows): void
    {
        $rowNumber = $this->headingRow();

        foreach ($rows as $row) {
            $rowNumber++;
            $data = $row->toArray();

            // Lewati baris benar-benar kosong tanpa menambah error.
            if (collect($data)->every(fn ($value): bool => blank($value))) {
                continue;
            }

            try {
                $this->service->createFromRow($data, $this->desa, $this->seenNik);
                $this->imported++;
            } catch (Throwable $e) {
                $this->errors[] = ['baris' => $rowNumber, 'pesan' => $e->getMessage()];
            }
        }
    }

    public function headingRow(): int
    {
        return 1;
    }

    /**
     * Hanya proses sheet pertama (Data Warga). Sheet Petunjuk & Referensi diabaikan.
     *
     * @return array<int, $this>
     */
    public function sheets(): array
    {
        return [0 => $this];
    }
}
