<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Riwayat pembacaan sensor EWS, direkam penjadwal secara berkala. Halaman publik
 * membaca nilai TERKINI dari cache (lihat EwsBlynkService); tabel ini yang
 * memungkinkan grafik tren, yaitu melihat tinggi air merangkak naik sebelum
 * banjir, bukan hanya angka sesaat.
 *
 * Seluruh kolom sensor NULLABLE, dan itu disengaja. Alat bisa mati, satu pin bisa
 * gagal terbaca sementara yang lain berhasil, dan Blynk mengembalikan apa adanya.
 * Merekam null jauh lebih jujur daripada merekam 0, yang pada tinggi air berarti
 * "sungai kering" dan pada grafik akan tampak seperti penurunan drastis.
 *
 * `terhubung` merekam jawaban isHardwareConnected saat pembacaan diambil. Tanpa
 * itu, nilai terakhir yang diingat Blynk dari alat yang sudah mati berminggu-minggu
 * tak bisa dibedakan dari pembacaan sungguhan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ews_readings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ews_device_id')->constrained('ews_devices')->cascadeOnDelete();
            $table->decimal('tinggi_air', 8, 2)->nullable();   // cm
            $table->decimal('curah_hujan', 8, 2)->nullable();  // mm/jam
            $table->decimal('ph_air', 5, 2)->nullable();       // 0..14; di luar itu = sensor belum terkalibrasi
            $table->decimal('getaran', 8, 2)->nullable();      // skala mentah sensor
            $table->string('status_sungai', 30)->nullable();   // teks bebas dari alat, dinormalkan di App\Enums\StatusSungai
            $table->boolean('terhubung')->default(false);
            $table->timestamp('direkam_pada');
            $table->timestamps();

            // Grafik tren selalu bertanya "pembacaan satu perangkat, rentang waktu
            // tertentu, urut waktu" — indeks ini melayani persis itu, sekaligus
            // dipakai pemangkasan data lama.
            $table->index(['ews_device_id', 'direkam_pada']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ews_readings');
    }
};
