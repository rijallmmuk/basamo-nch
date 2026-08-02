<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tema Pelatihan = data referensi RINGAN, isinya identitas tema saja (tanpa
 * deskripsi, tanpa cover). Satu tema dipakai berkali-kali oleh pelaksanaan
 * (tabel `pelatihans`) milik pengajar dan nagari yang berbeda.
 *
 * Keunikan dijaga di `nama_normal` (hasil normalisasi App\Support\TemaNormalizer),
 * BUKAN di `nama` mentah — supaya "Digital Marketing UMKM" dan "digital  marketing
 * umkm" jatuh ke baris yang sama, sementara simbol bermakna (C++, UI/UX) tetap
 * dibedakan. Tanpa softDeletes: tema tak pernah dihapus selama masih dipakai
 * pelaksanaan (scope `tidakDipakai`), sehingga unique biasa sudah cukup.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tema_pelatihans', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 200);
            $table->string('nama_normal', 200)->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tema_pelatihans');
    }
};
