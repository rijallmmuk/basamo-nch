<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Referensi global pekerjaan. ID diselaraskan PERSIS dengan
 * `tweb_penduduk_pekerjaan` OpenSID (89 jenis, termasuk id 89 = catch-all
 * "Lainnya") — bukan daftar 99 item versi lama proyek ini.
 */
return new class extends Migration
{
    private const PEKERJAAN = [
        'Belum/Tidak Bekerja', 'Mengurus Rumah Tangga', 'Pelajar/Mahasiswa', 'Pensiunan',
        'Pegawai Negeri Sipil', 'Tentara Nasional Indonesia', 'Kepolisian RI', 'Perdagangan',
        'Petani/Pekebun', 'Peternak', 'Nelayan/Perikanan', 'Industri', 'Konstruksi', 'Transportasi',
        'Karyawan Swasta', 'Karyawan BUMN', 'Karyawan BUMD', 'Karyawan Honorer', 'Buruh Harian Lepas',
        'Buruh Tani/Perkebunan', 'Buruh Nelayan/Perikanan', 'Buruh Peternakan', 'Pembantu Rumah Tangga',
        'Tukang Cukur', 'Tukang Listrik', 'Tukang Batu', 'Tukang Kayu', 'Tukang Sol Sepatu',
        'Tukang Las/Pandai Besi', 'Tukang Jahit', 'Tukang Gigi', 'Penata Rias', 'Penata Busana',
        'Penata Rambut', 'Mekanik', 'Seniman', 'Tabib', 'Paraji', 'Perancang Busana', 'Penerjemah',
        'Imam Masjid', 'Pendeta', 'Pastur', 'Wartawan', 'Ustadz/Mubaligh', 'Juru Masak', 'Promotor Acara',
        'Anggota DPR-RI', 'Anggota DPD', 'Anggota BPK', 'Presiden', 'Wakil Presiden',
        'Anggota Mahkamah Konstitusi', 'Anggota Kabinet Kementerian', 'Duta Besar', 'Gubernur',
        'Wakil Gubernur', 'Bupati', 'Wakil Bupati', 'Walikota', 'Wakil Walikota',
        'Anggota DPRD Provinsi', 'Anggota DPRD Kabupaten/Kota', 'Dosen', 'Guru', 'Pilot', 'Pengacara',
        'Notaris', 'Arsitek', 'Akuntan', 'Konsultan', 'Dokter', 'Bidan', 'Perawat', 'Apoteker',
        'Psikiater/Psikolog', 'Penyiar Televisi', 'Penyiar Radio', 'Pelaut', 'Peneliti', 'Sopir',
        'Pialang', 'Paranormal', 'Pedagang', 'Perangkat Nagari', 'Kepala Nagari', 'Biarawati', 'Wiraswasta',
        'Lainnya',
    ];

    public function up(): void
    {
        Schema::create('pekerjaan', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 4)->unique();
            $table->string('nama', 100);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        $now = now();
        $rows = [];
        foreach (self::PEKERJAAN as $i => $nama) {
            $rows[] = [
                'kode' => str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT),
                'nama' => $nama,
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
    }
};
