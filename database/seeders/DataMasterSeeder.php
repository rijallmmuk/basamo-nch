<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Data master kependudukan: agama, pendidikan, status perkawinan, dan jenis
 * pekerjaan. Mengacu daftar referensi Dukcapil, dengan penyesuaian istilah
 * nagari (Perangkat/Kepala Nagari) sesuai nomenklatur Sumatera Barat.
 *
 * Idempotent: upsert memakai kunci alami (`nama`, atau `kode` untuk pekerjaan),
 * jadi aman dijalankan berulang tanpa menggandakan baris.
 *
 * `id` sengaja ditulis eksplisit dan mengikuti urutan daftar di bawah supaya
 * nilainya sama di semua lingkungan. Kolom penduduk (`agama_id`, `pendidikan_id`,
 * `status_perkawinan_id`, `pekerjaan_id`) merujuk id ini, sehingga hasil ekspor
 * dari satu lingkungan tetap valid saat diimpor di lingkungan lain.
 *
 * Menambah entri: cukup tambahkan di AKHIR daftar. JANGAN menyisipkan di tengah
 * atau mengurutkan ulang, karena id diturunkan dari posisi dan penduduk
 * lama akan ikut bergeser rujukannya.
 */
class DataMasterSeeder extends Seeder
{
    /** @var list<string> */
    private const AGAMA = [
        'Islam',
        'Kristen',
        'Katolik',
        'Hindu',
        'Buddha',
        'Konghucu',
        'Kepercayaan Terhadap Tuhan YME/Lainnya',
    ];

    /** @var list<string> */
    private const PENDIDIKAN = [
        'Tidak/Belum Sekolah',
        'Belum Tamat SD/Sederajat',
        'Tamat SD/Sederajat',
        'SLTP/Sederajat',
        'SLTA/Sederajat',
        'Diploma I/II',
        'Akademi/Diploma III/S. Muda',
        'Diploma IV/Strata I',
        'Strata II',
        'Strata III',
    ];

    /** @var list<string> */
    private const STATUS_PERKAWINAN = [
        'Belum Kawin',
        'Kawin',
        'Cerai Hidup',
        'Cerai Mati',
    ];

    /**
     * Jenis pekerjaan Dukcapil. `kode` = posisi di-zero-pad dua digit (01..89).
     *
     * @var list<string>
     */
    private const PEKERJAAN = [
        'Belum/Tidak Bekerja',
        'Mengurus Rumah Tangga',
        'Pelajar/Mahasiswa',
        'Pensiunan',
        'Pegawai Negeri Sipil',
        'Tentara Nasional Indonesia',
        'Kepolisian RI',
        'Perdagangan',
        'Petani/Pekebun',
        'Peternak',
        'Nelayan/Perikanan',
        'Industri',
        'Konstruksi',
        'Transportasi',
        'Karyawan Swasta',
        'Karyawan BUMN',
        'Karyawan BUMD',
        'Karyawan Honorer',
        'Buruh Harian Lepas',
        'Buruh Tani/Perkebunan',
        'Buruh Nelayan/Perikanan',
        'Buruh Peternakan',
        'Pembantu Rumah Tangga',
        'Tukang Cukur',
        'Tukang Listrik',
        'Tukang Batu',
        'Tukang Kayu',
        'Tukang Sol Sepatu',
        'Tukang Las/Pandai Besi',
        'Tukang Jahit',
        'Tukang Gigi',
        'Penata Rias',
        'Penata Busana',
        'Penata Rambut',
        'Mekanik',
        'Seniman',
        'Tabib',
        'Paraji',
        'Perancang Busana',
        'Penerjemah',
        'Imam Masjid',
        'Pendeta',
        'Pastur',
        'Wartawan',
        'Ustadz/Mubaligh',
        'Juru Masak',
        'Promotor Acara',
        'Anggota DPR-RI',
        'Anggota DPD',
        'Anggota BPK',
        'Presiden',
        'Wakil Presiden',
        'Anggota Mahkamah Konstitusi',
        'Anggota Kabinet Kementerian',
        'Duta Besar',
        'Gubernur',
        'Wakil Gubernur',
        'Bupati',
        'Wakil Bupati',
        'Walikota',
        'Wakil Walikota',
        'Anggota DPRD Provinsi',
        'Anggota DPRD Kabupaten/Kota',
        'Dosen',
        'Guru',
        'Pilot',
        'Pengacara',
        'Notaris',
        'Arsitek',
        'Akuntan',
        'Konsultan',
        'Dokter',
        'Bidan',
        'Perawat',
        'Apoteker',
        'Psikiater/Psikolog',
        'Penyiar Televisi',
        'Penyiar Radio',
        'Pelaut',
        'Peneliti',
        'Sopir',
        'Pialang',
        'Paranormal',
        'Pedagang',
        'Perangkat Nagari',
        'Kepala Nagari',
        'Biarawati',
        'Wiraswasta',
        'Lainnya',
    ];

    public function run(): void
    {
        $this->seedLookup('agama', self::AGAMA);
        $this->seedLookup('pendidikan', self::PENDIDIKAN);
        $this->seedLookup('status_perkawinan', self::STATUS_PERKAWINAN);
        $this->seedPekerjaan();

        $this->command?->info(sprintf(
            'Data master: %d agama, %d pendidikan, %d status perkawinan, %d pekerjaan.',
            count(self::AGAMA),
            count(self::PENDIDIKAN),
            count(self::STATUS_PERKAWINAN),
            count(self::PEKERJAAN),
        ));
    }

    /**
     * Tabel referensi sederhana (id, nama, aktif), unik pada `nama`.
     *
     * @param  list<string>  $names
     */
    private function seedLookup(string $table, array $names): void
    {
        $now = now();

        $rows = [];
        foreach ($names as $index => $nama) {
            $rows[] = [
                'id' => $index + 1,
                'nama' => $nama,
                'aktif' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table($table)->upsert($rows, ['nama'], ['aktif', 'updated_at']);
    }

    /** Pekerjaan punya kolom `kode` tambahan dan unik pada `kode`, bukan `nama`. */
    private function seedPekerjaan(): void
    {
        $now = now();

        $rows = [];
        foreach (self::PEKERJAAN as $index => $nama) {
            $rows[] = [
                'id' => $index + 1,
                'kode' => str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT),
                'nama' => $nama,
                'aktif' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('pekerjaan')->upsert($rows, ['kode'], ['nama', 'aktif', 'updated_at']);
    }
}
