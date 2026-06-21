<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Referensi global (lintas-desa, dikelola super_admin) penyebutan administratif
        // setingkat desa: Desa/Kelurahan/Nagari/Gampong/… Skala nasional.
        Schema::create('jenis_desa', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 50)->unique();
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });

        // Referensi global sebutan sub-unit di bawah desa: Jorong/Dusun/Lingkungan/RW/…
        Schema::create('jenis_sub_unit', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 50)->unique();
            $table->unsignedSmallInteger('urutan')->default(0);
            $table->boolean('aktif')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jenis_sub_unit');
        Schema::dropIfExists('jenis_desa');
    }
};
