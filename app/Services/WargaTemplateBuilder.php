<?php

namespace App\Services;

use App\Models\Agama;
use App\Models\Desa;
use App\Models\DesaUnit;
use App\Models\Pekerjaan;
use App\Models\StatusPerkawinan;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Membangun template Excel impor warga, sesuai konteks aktor:
 * - desa_admin: tanpa kolom "Desa"; dropdown "Wilayah" berisi sub-unit desanya.
 * - super_admin: ada kolom "Desa" (dropdown semua desa); "Wilayah" teks bebas
 *   (cascade antar-desa tak praktis di Excel) — divalidasi ulang saat impor.
 *
 * Tiga sheet: "Data Warga" (untuk diisi), "Petunjuk" (penjelasan tiap kolom + opsi
 * enum), dan "Referensi" (sumber daftar untuk dropdown).
 */
class WargaTemplateBuilder
{
    /**
     * Definisi kolom data: [judul, wajib, keterangan, kunci-referensi|null].
     *
     * @var list<array{0:string,1:bool,2:string,3:?string}>
     */
    private array $columns = [];

    /** @var array<string, list<string>> daftar nilai untuk tiap referensi/dropdown */
    private array $refs = [];

    public function download(User $actor): StreamedResponse
    {
        $spreadsheet = $this->build($actor);

        return response()->streamDownload(
            function () use ($spreadsheet): void {
                (new Xlsx($spreadsheet))->save('php://output');
            },
            'template-impor-warga.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }

    public function build(User $actor): Spreadsheet
    {
        // Desa konteks (desa_admin → desanya; super admin → desa yang dikelola). Sebutan
        // sub-unit & daftar dropdown wilayah khas desa tersebut.
        $desaId = $actor->managedDesaId();
        $sebutan = ($desaId ? Desa::find($desaId)?->jenisSubUnit?->nama : null) ?: 'Wilayah';

        $this->refs = [
            'jenis_kelamin' => ['Laki-laki', 'Perempuan'],
            'agama' => $this->aktifNama(Agama::class),
            'status_perkawinan' => $this->aktifNama(StatusPerkawinan::class),
            'pekerjaan' => $this->aktifNama(Pekerjaan::class),
            'status' => ['Aktif', 'Nonaktif'],
            'wilayah' => $desaId
                ? DesaUnit::where('desa_id', $desaId)->orderBy('nama')->pluck('nama')->all()
                : [],
        ];

        // Urutan kolom pada sheet Data. Elemen ke-4 = kunci-referensi → dipakai untuk dropdown.
        $this->columns = [
            ['Nama', true, 'Nama lengkap warga.', null],
            ['NIK', true, '16 digit angka. Unik — dipakai warga untuk login portal.', null],
            ['Jenis Kelamin', true, 'Pilih dari daftar.', 'jenis_kelamin'],
            ['Tempat Lahir', true, 'Kota/kabupaten kelahiran. Contoh: Padang.', null],
            ['Tanggal Lahir', true, 'Format d/m/yyyy (tanggal/bulan/tahun). Contoh: 17/05/1990. Ketik sebagai teks, bukan tanggal otomatis Excel.', null],
            ['Agama', true, 'Pilih dari dropdown.', 'agama'],
            ['Status Perkawinan', true, 'Pilih dari dropdown.', 'status_perkawinan'],
            ['Pekerjaan', true, 'Pilih dari dropdown.', 'pekerjaan'],
            [$sebutan, true, "Sub-unit ({$sebutan}) tempat tinggal — pilih dari dropdown.", 'wilayah'],
            ['Email', false, 'Opsional. Email valid & unik. Boleh dikosongkan.', null],
            ['No HP', false, 'Opsional. Tulis 08.../+62.../62... — otomatis disimpan sebagai 62...', null],
            ['Status', false, 'Opsional. Kosongkan = otomatis Aktif. Pilihan: Aktif / Nonaktif.', 'status'],
        ];

        $spreadsheet = new Spreadsheet;

        $dataSheet = $spreadsheet->getActiveSheet();
        $dataSheet->setTitle('Data Warga');
        $petunjukSheet = $spreadsheet->createSheet();
        $petunjukSheet->setTitle('Petunjuk');
        $referensiSheet = $spreadsheet->createSheet();
        $referensiSheet->setTitle('Referensi');

        $this->fillReferensi($referensiSheet);  // isi dulu: jadi sumber formula dropdown
        $this->fillData($dataSheet);
        $this->fillPetunjuk($petunjukSheet);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /** @param  class-string<Model>  $model */
    private function aktifNama(string $model): array
    {
        return $model::query()->where('aktif', true)->orderBy('urutan')->pluck('nama')->all();
    }

    /** Sheet "Referensi": satu kolom per daftar; sumber untuk dropdown sheet Data. */
    private function fillReferensi(Worksheet $sheet): void
    {
        $colIndex = 1;
        foreach ($this->refs as $key => $values) {
            $letter = Coordinate::stringFromColumnIndex($colIndex);
            $sheet->setCellValue("{$letter}1", ucwords(str_replace('_', ' ', $key)));
            $sheet->getStyle("{$letter}1")->getFont()->setBold(true);

            foreach ($values as $i => $value) {
                $sheet->setCellValueExplicit("{$letter}".($i + 2), $value, DataType::TYPE_STRING);
            }

            $sheet->getColumnDimension($letter)->setWidth(28);
            $colIndex++;
        }
    }

    /** Sheet "Data Warga": header + dropdown untuk kolom berdaftar. */
    private function fillData(Worksheet $sheet): void
    {
        $lastRow = 500; // baris terisi-dropdown yang siap pakai

        foreach ($this->columns as $index => [$title, $wajib, $keterangan, $refKey]) {
            $letter = Coordinate::stringFromColumnIndex($index + 1);

            $sheet->setCellValue("{$letter}1", $wajib ? "{$title} *" : $title);
            $sheet->getColumnDimension($letter)->setWidth(max(16, mb_strlen($title) + 6));

            // Tooltip (komentar sel) di header: status wajib + cara isi.
            $tip = ($wajib ? 'WAJIB DIISI. ' : 'Opsional. ').$keterangan;
            $comment = $sheet->getComment("{$letter}1");
            $comment->getText()->createText($tip);
            $comment->setWidth('260px')->setHeight('90px')->setMarginLeft('120px');

            if ($refKey !== null && ! empty($this->refs[$refKey])) {
                $this->applyDropdown($sheet, $letter, $refKey, $lastRow);
            }
        }

        // Kolom yang harus tetap teks apa adanya: NIK/No HP (angka panjang jangan jadi
        // notasi ilmiah / hilang nol depan) & Tanggal Lahir (hindari auto-konversi tanggal
        // sesuai locale Excel yang bikin salah-parse / "masa depan").
        foreach (['NIK', 'No HP', 'Tanggal Lahir'] as $textCol) {
            $idx = $this->columnIndex($textCol);
            if ($idx !== null) {
                $letter = Coordinate::stringFromColumnIndex($idx + 1);
                $sheet->getStyle("{$letter}2:{$letter}{$lastRow}")
                    ->getNumberFormat()->setFormatCode('@');
            }
        }

        // Header: tebal, latar, beku.
        $headerRange = 'A1:'.Coordinate::stringFromColumnIndex(count($this->columns)).'1';
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8EEF2');
        $sheet->getStyle($headerRange)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(22);
        $sheet->freezePane('A2');
    }

    private function applyDropdown(Worksheet $sheet, string $letter, string $refKey, int $lastRow): void
    {
        $refLetter = Coordinate::stringFromColumnIndex(array_search($refKey, array_keys($this->refs), true) + 1);
        $count = count($this->refs[$refKey]);

        $validation = new DataValidation;
        $validation->setType(DataValidation::TYPE_LIST)
            ->setErrorStyle(DataValidation::STYLE_STOP)
            ->setAllowBlank(true)
            ->setShowDropDown(true)
            ->setShowErrorMessage(true)
            ->setErrorTitle('Pilihan tidak valid')
            ->setError('Pilih nilai dari daftar dropdown.')
            ->setFormula1("Referensi!\${$refLetter}\$2:\${$refLetter}\$".($count + 1));

        $sheet->setDataValidation("{$letter}2:{$letter}{$lastRow}", $validation);
    }

    /** Sheet "Petunjuk": penjelasan tiap kolom + opsi enum. */
    private function fillPetunjuk(Worksheet $sheet): void
    {
        $sheet->setCellValue('A1', 'Petunjuk Pengisian — Impor Warga');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $notes = [
            'Isi data pada sheet "Data Warga". Jangan ubah/menghapus baris header (baris 1).',
            'Kolom bertanda * wajib diisi. Kolom dengan dropdown harus dipilih dari daftar.',
            'NIK harus 16 digit & unik. Baris dengan NIK yang sudah terdaftar akan dilewati.',
            'OTP/sandi TIDAK diatur di sini — terbitkan lewat aksi "Reset OTP" saat warga siap login.',
            'Daftar nilai lengkap (Agama, Status Perkawinan, Pekerjaan, dll) ada di sheet "Referensi".',
        ];

        $row = 3;
        foreach ($notes as $note) {
            $sheet->setCellValue("A{$row}", '•  '.$note);
            $sheet->mergeCells("A{$row}:D{$row}");
            $row++;
        }

        $row++;
        $headers = ['Kolom', 'Wajib', 'Keterangan', 'Pilihan'];
        foreach ($headers as $i => $h) {
            $letter = Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue("{$letter}{$row}", $h);
            $sheet->getStyle("{$letter}{$row}")->getFont()->setBold(true);
            $sheet->getStyle("{$letter}{$row}")->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8EEF2');
        }
        $row++;

        foreach ($this->columns as [$title, $wajib, $keterangan, $refKey]) {
            $pilihan = '';
            if ($refKey !== null && ! empty($this->refs[$refKey])) {
                $values = $this->refs[$refKey];
                $pilihan = count($values) <= 12
                    ? implode(', ', $values)
                    : 'Lihat sheet "Referensi" ('.count($values).' pilihan)';
            }

            $sheet->setCellValue("A{$row}", $title);
            $sheet->setCellValue("B{$row}", $wajib ? 'Wajib' : 'Opsional');
            $sheet->setCellValue("C{$row}", $keterangan);
            $sheet->setCellValue("D{$row}", $pilihan);
            $sheet->getStyle("A{$row}:D{$row}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
            $row++;
        }

        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(10);
        $sheet->getColumnDimension('C')->setWidth(52);
        $sheet->getColumnDimension('D')->setWidth(40);
    }

    private function columnIndex(string $title): ?int
    {
        foreach ($this->columns as $index => [$colTitle]) {
            if ($colTitle === $title) {
                return $index;
            }
        }

        return null;
    }
}
