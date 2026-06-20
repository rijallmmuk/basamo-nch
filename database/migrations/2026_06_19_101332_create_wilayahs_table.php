<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Unit wilayah dalam nagari (1 tingkat). Sebutannya (Jorong/Dusun/Korong/…)
        // diatur per nagari via nagaris.wilayah_label.
        Schema::create('wilayahs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nagari_id')->constrained()->cascadeOnDelete();
            $table->string('nama');
            $table->timestamps();
            $table->softDeletes();

            $table->index('nagari_id');
            $table->unique(['nagari_id', 'nama']); // nama unik dalam satu nagari
        });

        // Alamat warga: unit wilayah dalam nagarinya (opsional).
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
