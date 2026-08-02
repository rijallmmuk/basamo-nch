<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * FAQ beranda situs publik (base URL, bukan per-nagari), dikelola superadmin.
 * Tabel GLOBAL (tanpa nagari_id) — bukan data milik satu nagari.
 */
return new class extends Migration
{
    /** FAQ awal — dipindah dari array statis di resources/views/public/home.blade.php. */
    private const FAQ_AWAL = [
        ['Bagaimana nagari kami bisa menjadi mitra?', 'Hubungi tim SLC Basamo NCH. Setelah terdaftar, nagari mendapat situs resminya sendiri, akun operator nagari, serta akses penuh ke empat layanan platform.'],
        ['Bagaimana cara mendaftarkan UMKM saya?', 'Masuk ke portal warga dengan akun warga nagari Anda, lalu ajukan akses UMKM. Setelah disetujui operator nagari, Anda dapat mengelola profil usaha dan produk di situs nagari.'],
        ['Apakah pelatihan di LMS Hub berbayar?', 'Tidak. Seluruh materi pelatihan di LMS Hub dapat diakses gratis oleh warga nagari yang telah terdaftar dalam sistem.'],
        ['Bagaimana sistem kerja Smart IoT?', 'Sensor dipasang di titik strategis nagari untuk mengukur suhu, kelembaban tanah, curah hujan, dan kualitas udara. Datanya terekam otomatis dan menghasilkan peringatan dini bagi nagari.'],
        ['Di mana saya bisa melihat produk UMKM nagari?', 'Buka peta nagari mitra di halaman ini, pilih nagari yang ingin dikunjungi, lalu jelajahi katalog produknya langsung di situs resmi nagari tersebut.'],
    ];

    public function up(): void
    {
        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->string('pertanyaan', 255);
            $table->text('jawaban');
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();

            $table->index(['aktif', 'urutan']);
        });

        foreach (self::FAQ_AWAL as $i => [$pertanyaan, $jawaban]) {
            DB::table('faqs')->insert([
                'pertanyaan' => $pertanyaan,
                'jawaban' => $jawaban,
                'urutan' => $i + 1,
                'aktif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
    }
};
