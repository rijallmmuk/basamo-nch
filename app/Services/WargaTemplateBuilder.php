<?php

namespace App\Services;

use App\Models\Agama;
use App\Models\Pekerjaan;
use App\Models\Pendidikan;
use App\Models\StatusPerkawinan;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Membangun template Excel impor warga.
 *
 * Kolom pilihan (sex, agama_id, pendidikan_id, pekerjaan_id, status_kawin_id) diisi
 * lewat DROPDOWN berisi nama pilihan, bukan angka. Sebelumnya kolom-kolom itu hanya
 * menerima ID mentah dan pengisi harus bolak-balik ke sheet "Referensi" untuk tahu
 * angka 13 itu pekerjaan apa. Daftar pilihannya dibangun dari tabel referensi saat
 * berkas diunduh, jadi selalu sama persis dengan isi sistem.
 *
 * Nama kolom tetap berakhiran "_id" dan {@see WargaImportService} tetap menerima ID
 * mentah: skema ini SENGAJA disamakan dengan ekspor penduduk OpenSID milik nagari
 * supaya berkas ekspor mentah nagari bisa langsung diunggah tanpa diubah.
 *
 * Tiga sheet: "Data Warga" (untuk diisi), "Referensi" (daftar id→nama tiap tabel
 * rujukan, sekaligus sumber dropdown), dan "Petunjuk" (penjelasan tiap kolom).
 */
class WargaTemplateBuilder
{
    /**
     * Banyak baris yang dipasangi dropdown. Impor sendiri tidak dibatasi angka ini:
     * baris ke-1001 dan seterusnya tetap terbaca, hanya tanpa bantuan dropdown.
     */
    private const BARIS_DROPDOWN = 1000;

    /**
     * Definisi kolom data: [judul, wajib, keterangan].
     *
     * @var list<array{0:string,1:bool,2:string}>
     */
    private const COLUMNS = [
        ['nama', true, 'Nama lengkap warga.'],
        ['nik', true, '16 digit angka. Unik, dipakai warga untuk login portal.'],
        ['sex', false, 'Pilih dari dropdown: Laki-laki atau Perempuan. Angka 1 (Laki-laki) dan 2 (Perempuan) juga diterima.'],
        ['tempatlahir', false, 'Kota/kabupaten kelahiran. Contoh: Padang.'],
        ['tanggallahir', false, 'Format yyyy-mm-dd (tahun-bulan-tanggal). Contoh: 1990-05-17. Ketik sebagai teks, bukan tanggal otomatis Excel.'],
        ['agama_id', false, 'Pilih dari dropdown. Angka ID dari sheet "Referensi" juga diterima.'],
        ['pendidikan_id', false, 'Pilih dari dropdown, yaitu pendidikan terakhir yang SELESAI ditempuh. Angka ID dari sheet "Referensi" juga diterima.'],
        ['pekerjaan_id', false, 'Pilih dari dropdown. Angka ID dari sheet "Referensi" juga diterima.'],
        ['status_kawin_id', false, 'Pilih dari dropdown. Angka ID dari sheet "Referensi" juga diterima.'],
    ];

    /** Pilihan tetap untuk kolom sex; nilainya dikenali {@see WargaImportService}. */
    private const PILIHAN_SEX = ['Laki-laki', 'Perempuan'];

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

        // Referensi ditulis lebih dulu: sheet itulah sumber daftar dropdown di
        // sheet data, jadi rentang selnya harus sudah diketahui.
        $sumberPilihan = $this->fillReferensi($referensiSheet);

        $this->fillData($dataSheet, $sumberPilihan);
        $this->fillPetunjuk($petunjukSheet);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * Sheet "Data Warga": header, plus dropdown pada kolom pilihan.
     *
     * @param  array<string, string>  $sumberPilihan  judul kolom => rentang sel sumber di sheet "Referensi"
     */
    private function fillData(Worksheet $sheet, array $sumberPilihan): void
    {
        foreach (self::COLUMNS as $index => [$title, $wajib, $keterangan]) {
            $letter = Coordinate::stringFromColumnIndex($index + 1);

            $sheet->setCellValue("{$letter}1", $wajib ? "{$title} *" : $title);
            $sheet->getColumnDimension($letter)->setWidth(max(16, mb_strlen($title) + 6));

            $tip = ($wajib ? 'WAJIB DIISI. ' : 'Opsional. ').$keterangan;
            $comment = $sheet->getComment("{$letter}1");
            $comment->getText()->createText($tip);
            $comment->setWidth('260px')->setHeight('90px')->setMarginLeft('120px');

            if ($title === 'sex') {
                // Daftar pendek ditulis inline; Excel membatasi cara ini pada 255
                // karakter, jadi daftar panjang tetap harus lewat rentang sel.
                $this->pasangDropdown($sheet, $letter, '"'.implode(',', self::PILIHAN_SEX).'"');

                continue;
            }

            if (isset($sumberPilihan[$title])) {
                $this->pasangDropdown($sheet, $letter, $sumberPilihan[$title]);
            }
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

    /**
     * Sheet "Referensi": satu blok id→nama per tabel rujukan. Kolom namanya sekaligus
     * menjadi sumber dropdown di sheet data, jadi daftar pilihan tak pernah bisa
     * menyimpang dari isi sistem.
     *
     * @return array<string, string> judul kolom => rentang sel sumber dropdown
     */
    private function fillReferensi(Worksheet $sheet): array
    {
        $colIndex = 1;
        $sumber = [];

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

            // Tabel referensi kosong tidak boleh menghasilkan rentang terbalik
            // (mis. B2:B1), karena Excel menolak membuka berkasnya.
            if ($rows->isNotEmpty()) {
                $sumber[$key] = sprintf("'%s'!\$%s\$2:\$%s\$%d", $sheet->getTitle(), $namaLetter, $namaLetter, $i - 1);
            }

            $sheet->getColumnDimension($idLetter)->setWidth(10);
            $sheet->getColumnDimension($namaLetter)->setWidth(32);
            $colIndex += 3; // 1 kolom kosong pemisah antar blok
        }

        return $sumber;
    }

    /** Dropdown wajib-pilih pada satu kolom sheet data, sepanjang {@see BARIS_DROPDOWN} baris. */
    private function pasangDropdown(Worksheet $sheet, string $letter, string $formula): void
    {
        $validation = new DataValidation;
        $validation->setType(DataValidation::TYPE_LIST)
            ->setErrorStyle(DataValidation::STYLE_STOP)
            ->setAllowBlank(true)
            ->setShowDropDown(true)
            ->setShowErrorMessage(true)
            ->setErrorTitle('Pilihan tidak dikenali')
            ->setError('Pilih salah satu isian dari daftar. Isian di luar daftar akan ditolak saat impor.')
            ->setFormula1($formula);

        $sheet->setDataValidation("{$letter}2:{$letter}".(self::BARIS_DROPDOWN + 1), $validation);
    }

    /** Sheet "Petunjuk": penjelasan tiap kolom. */
    private function fillPetunjuk(Worksheet $sheet): void
    {
        $sheet->setCellValue('A1', 'Petunjuk Pengisian Impor Warga');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

        $notes = [
            'Isi data pada sheet "Data Warga". Jangan ubah/menghapus baris header (baris 1).',
            'Kolom bertanda * wajib diisi. Kolom lain boleh kosong.',
            'Kolom sex, agama_id, pendidikan_id, pekerjaan_id, dan status_kawin_id punya DROPDOWN: klik selnya lalu pilih, tidak perlu mengetik angka.',
            'Daftar pilihan diambil dari sheet "Referensi" dan sudah sesuai isi sistem saat template ini diunduh.',
            'Angka ID dari sheet "Referensi" tetap diterima, jadi berkas lama yang sudah berisi ID tidak perlu diubah.',
            'Dropdown terpasang sampai baris '.number_format(self::BARIS_DROPDOWN + 1, 0, ',', '.').'. Baris berikutnya tetap bisa diimpor, hanya tanpa dropdown.',
            'nik harus 16 digit & unik. Baris dengan NIK yang sudah terdaftar akan dilewati.',
            'File ekspor mentah dari sistem penduduk nagari (kolom lebih banyak) juga bisa langsung diunggah. Kolom yang tak dikenal akan diabaikan.',
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
