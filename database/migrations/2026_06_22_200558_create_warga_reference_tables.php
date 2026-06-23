<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Referensi global data master kependudukan (lintas-desa, dikelola super_admin):
 * - `agama`             : 6 agama resmi (Dukcapil).
 * - `status_perkawinan` : 4 status perkawinan resmi.
 * - `pekerjaan`         : 99 jenis pekerjaan resmi (kode 01–99 Dukcapil).
 *
 * Himpunan baku & jarang berubah → di-seed langsung di migrasi (sama seperti
 * `jenis_desa`/`jenis_sub_unit`), agar selalu tersedia tanpa menjalankan seeder.
 */
return new class extends Migration
{
    private const AGAMA = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha', 'Konghucu'];

    private const STATUS_PERKAWINAN = ['Belum Kawin', 'Kawin', 'Cerai Hidup', 'Cerai Mati'];

    private const PEKERJAAN = [
        'Belum/Tidak Bekerja', 'Mengurus Rumah Tangga', 'Pelajar/Mahasiswa', 'Pensiunan',
        'Pegawai Negeri Sipil', 'Tentara Nasional Indonesia', 'Kepolisian RI', 'Perdagangan',
        'Petani/Pekebun', 'Peternak', 'Nelayan/Perikanan', 'Industri', 'Konstruksi', 'Transportasi',
        'Karyawan Swasta', 'Karyawan BUMN', 'Karyawan BUMD', 'Karyawan Honorer', 'Buruh Harian Lepas',
        'Buruh Tani/Perkebunan', 'Buruh Nelayan/Perikanan', 'Buruh Peternakan', 'Pembantu Rumah Tangga',
        'Tukang Cukur', 'Tukang Listrik', 'Tukang Batu', 'Tukang Kayu', 'Tukang Sol Sepatu',
        'Tukang Las/Pandai Besi', 'Tukang Jahit', 'Penata Rambut', 'Penata Rias', 'Penata Busana',
        'Mekanik', 'Tukang Gigi', 'Seniman', 'Tabib', 'Paraji', 'Perancang Busana', 'Penerjemah',
        'Imam Masjid', 'Pendeta', 'Pastur', 'Wartawan', 'Ustadz/Mubaligh', 'Juru Masak', 'Promotor Acara',
        'Anggota DPR-RI', 'Anggota DPD', 'Anggota BPK', 'Presiden', 'Wakil Presiden',
        'Anggota Mahkamah Konstitusi', 'Anggota Kabinet/Kementerian', 'Duta Besar', 'Gubernur',
        'Wakil Gubernur', 'Bupati', 'Wakil Bupati', 'Walikota', 'Wakil Walikota',
        'Anggota DPRD Provinsi', 'Anggota DPRD Kabupaten/Kota', 'Dosen', 'Guru', 'Pilot', 'Pengacara',
        'Notaris', 'Arsitek', 'Akuntan', 'Konsultan', 'Dokter', 'Bidan', 'Perawat', 'Apoteker',
        'Psikiater/Psikolog', 'Penyiar Televisi', 'Penyiar Radio', 'Pelaut', 'Peneliti', 'Sopir',
        'Pialang', 'Paranormal', 'Pedagang', 'Perangkat Desa', 'Kepala Desa', 'Biarawati', 'Wiraswasta',
        'Anggota Lembaga Tinggi Lainnya', 'Artis', 'Atlet', 'Chef', 'Manajer', 'Tenaga Tata Usaha',
        'Operator', 'Pekerja Pengolahan, Kerajinan', 'Teknisi', 'Asisten Ahli', 'Lainnya',
    ];

    public function up(): void
    {
        Schema::create('agama', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 50)->unique();
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('status_perkawinan', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 50)->unique();
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        Schema::create('pekerjaan', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 4)->unique();
            $table->string('nama', 100);
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        $this->seedSimple('agama', self::AGAMA);
        $this->seedSimple('status_perkawinan', self::STATUS_PERKAWINAN);
        $this->seedPekerjaan();
    }

    /** @param  array<int, string>  $names */
    private function seedSimple(string $table, array $names): void
    {
        $now = now();
        $rows = [];

        foreach ($names as $i => $nama) {
            $rows[] = ['nama' => $nama, 'urutan' => $i + 1, 'aktif' => true, 'created_at' => $now, 'updated_at' => $now];
        }

        DB::table($table)->insert($rows);
    }

    private function seedPekerjaan(): void
    {
        $now = now();
        $rows = [];

        foreach (self::PEKERJAAN as $i => $nama) {
            $urutan = $i + 1;
            $rows[] = [
                'kode' => str_pad((string) $urutan, 2, '0', STR_PAD_LEFT),
                'nama' => $nama,
                'urutan' => $urutan,
                'aktif' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        DB::table('pekerjaan')->insert($rows);
    }

    public function down(): void
    {
        Schema::dropIfExists('pekerjaan');
        Schema::dropIfExists('status_perkawinan');
        Schema::dropIfExists('agama');
    }
};
