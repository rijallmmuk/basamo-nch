<?php

namespace App\Services;

use App\Models\Agama;
use App\Models\Pekerjaan;
use App\Models\Pendidikan;
use App\Models\StatusPerkawinan;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Membangun template Excel impor warga. Kolom data berupa ID mentah (agama_id,
 * pendidikan_id, pekerjaan_id, status_kawin_id) — SENGAJA disamakan dengan skema
 * ekspor penduduk OpenSID milik nagari (lihat {@see WargaImportService}), supaya
 * template ini & file ekspor mentah nagari saling kompatibel.
 *
 * Tiga sheet: "Data Warga" (untuk diisi), "Referensi" (daftar id→nama tiap tabel
 * rujukan), dan "Petunjuk" (penjelasan tiap kolom).
 */
class WargaTemplateBuilder
{
    /**
     * Definisi kolom data: [judul, wajib, keterangan].
     *
     * @var list<array{0:string,1:bool,2:string}>
     */
    private const COLUMNS = [
        ['nama', true, 'Nama lengkap warga.'],
        ['nik', true, '16 digit angka. Unik — dipakai warga untuk login portal.'],
        ['sex', false, '1 = Laki-laki, 2 = Perempuan.'],
        ['tempatlahir', false, 'Kota/kabupaten kelahiran. Contoh: Padang.'],
        ['tanggallahir', false, 'Format yyyy-mm-dd (tahun-bulan-tanggal). Contoh: 1990-05-17. Ketik sebagai teks, bukan tanggal otomatis Excel.'],
        ['agama_id', false, 'ID dari sheet "Referensi".'],
        ['pendidikan_id', false, 'ID dari sheet "Referensi" — pendidikan terakhir yang SELESAI ditempuh.'],
        ['pekerjaan_id', false, 'ID dari sheet "Referensi".'],
        ['status_kawin_id', false, 'ID dari sheet "Referensi".'],
    ];

    /** @var array<string, class-string<Model>> tabel referensi ber-kolom id+nama untuk sheet "Referensi" */
    private const REFS = [
        'agama_id' => Agama::class,
        'pendidikan_id' => Pendidikan::class,
        'pekerjaan_id' => Pekerjaan::class,
        'status_kawin_id' => StatusPerkawinan::class,
    ];

    public function download(User $actor): StreamedResponse
    {
        $spreadsheet = $this->build();

        return response()->streamDownload(
            function () use ($spreadsheet): void {
                (new Xlsx($spreadsheet))->save('php://output');
            },
            'template-impor-warga.xlsx',
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        );
    }

    public function build(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet;

        $dataSheet = $spreadsheet->getActiveSheet();
        $dataSheet->setTitle('Data Warga');
        $referensiSheet = $spreadsheet->createSheet();
        $referensiSheet->setTitle('Referensi');
        $petunjukSheet = $spreadsheet->createSheet();
        $petunjukSheet->setTitle('Petunjuk');

        $this->fillData($dataSheet);
        $this->fillReferensi($referensiSheet);
        $this->fillPetunjuk($petunjukSheet);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /** Sheet "Data Warga": header saja (kolom berisi ID mentah, tanpa dropdown). */
    private function fillData(Worksheet $sheet): void
    {
        foreach (self::COLUMNS as $index => [$title, $wajib, $keterangan]) {
            $letter = Coordinate::stringFromColumnIndex($index + 1);

            $sheet->setCellValue("{$letter}1", $wajib ? "{$title} *" : $title);
            $sheet->getColumnDimension($letter)->setWidth(max(16, mb_strlen($title) + 6));

            $tip = ($wajib ? 'WAJIB DIISI. ' : 'Opsional. ').$keterangan;
            $comment = $sheet->getComment("{$letter}1");
            $comment->getText()->createText($tip);
            $comment->setWidth('260px')->setHeight('90px')->setMarginLeft('120px');
        }

        // Kolom yang harus tetap teks apa adanya: nik/tanggallahir (hindari notasi
        // ilmiah / hilang nol depan / auto-konversi tanggal sesuai locale Excel).
        foreach (['nik', 'tanggallahir'] as $col) {
            $idx = $this->columnIndex($col);
            if ($idx !== null) {
                $letter = Coordinate::stringFromColumnIndex($idx + 1);
                $sheet->getStyle("{$letter}:{$letter}")
                    ->getNumberFormat()->setFormatCode('@');
            }
        }

        $headerRange = 'A1:'.Coordinate::stringFromColumnIndex(count(self::COLUMNS)).'1';
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8EEF2');
        $sheet->getStyle($headerRange)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(22);
        $sheet->freezePane('A2');
    }

    /** Sheet "Referensi": satu blok id→nama per tabel rujukan. */
    private function fillReferensi(Worksheet $sheet): void
    {
        $colIndex = 1;

        foreach (self::REFS as $key => $model) {
            $idLetter = Coordinate::stringFromColumnIndex($colIndex);
            $namaLetter = Coordinate::stringFromColumnIndex($colIndex + 1);

            $sheet->setCellValue("{$idLetter}1", $key);
            $sheet->setCellValue("{$namaLetter}1", 'nama');
            $sheet->getStyle("{$idLetter}1:{$namaLetter}1")->getFont()->setBold(true);

            $rows = $model::query()->where('aktif', true)->orderBy('id')->pluck('nama', 'id');

            $i = 2;
            foreach ($rows as $id => $nama) {
                $sheet->setCellValue("{$idLetter}{$i}", $id);
                $sheet->setCellValue("{$namaLetter}{$i}", $nama);
                $i++;
            }

            $sheet->getColumnDimension($idLetter)->setWidth(10);
            $sheet->getColumnDimension($namaLetter)->setWidth(32);
            $colIndex += 3; // 1 kolom kosong pemisah antar blok
        }
    }

    /** Sheet "Petunjuk": penjelasan tiap kolom. */
    private function fillPetunjuk(Worksheet $sheet): void
    {
        $sheet->setCellValue('A1', 'Petunjuk Pengisian — Impor Warga');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $notes = [
            'Isi data pada sheet "Data Warga". Jangan ubah/menghapus baris header (baris 1).',
            'Kolom bertanda * wajib diisi. Kolom lain boleh kosong.',
            'Kolom berakhiran "_id" diisi ANGKA ID sesuai sheet "Referensi" — bukan nama teksnya.',
            'nik harus 16 digit & unik. Baris dengan NIK yang sudah terdaftar akan dilewati.',
            'File ekspor mentah dari sistem penduduk nagari (kolom lebih banyak) juga bisa langsung diunggah — kolom yang tak dikenal akan diabaikan.',
            'Akun baru memakai password awal bersama untuk warga dan wajib menggantinya setelah login pertama.',
        ];

        $row = 3;
        foreach ($notes as $note) {
            $sheet->setCellValue("A{$row}", '•  '.$note);
            $sheet->mergeCells("A{$row}:D{$row}");
            $row++;
        }

        $row++;
        $headers = ['Kolom', 'Wajib', 'Keterangan'];
        foreach ($headers as $i => $h) {
            $letter = Coordinate::stringFromColumnIndex($i + 1);
            $sheet->setCellValue("{$letter}{$row}", $h);
            $sheet->getStyle("{$letter}{$row}")->getFont()->setBold(true);
            $sheet->getStyle("{$letter}{$row}")->getFill()
                ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E8EEF2');
        }
        $row++;

        foreach (self::COLUMNS as [$title, $wajib, $keterangan]) {
            $sheet->setCellValue("A{$row}", $title);
            $sheet->setCellValue("B{$row}", $wajib ? 'Wajib' : 'Opsional');
            $sheet->setCellValue("C{$row}", $keterangan);
            $sheet->getStyle("A{$row}:C{$row}")->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
            $row++;
        }

        $sheet->getColumnDimension('A')->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(10);
        $sheet->getColumnDimension('C')->setWidth(60);
    }

    private function columnIndex(string $title): ?int
    {
        foreach (self::COLUMNS as $index => [$colTitle]) {
            if ($colTitle === $title) {
                return $index;
            }
        }

        return null;
    }
}
