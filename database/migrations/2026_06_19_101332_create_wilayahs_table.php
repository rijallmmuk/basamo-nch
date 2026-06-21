<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Unit wilayah dalam desa (1 tingkat). Sebutannya (Jorong/Dusun/Korong/…)
        // diatur per desa via desas.wilayah_label.
        Schema::create('wilayahs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('desa_id')->constrained()->cascadeOnDelete();
            $table->string('nama');
            $table->timestamps();
            $table->softDeletes();

            $table->index('desa_id');
            $table->unique(['desa_id', 'nama']); // nama unik dalam satu desa
        });

        // Alamat warga: unit wilayah dalam desanya (opsional).
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('wilayah_id')->references('id')->on('wilayahs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['wilayah_id']);
        });

        Schema::dropIfExists('wilayahs');
    }
};
