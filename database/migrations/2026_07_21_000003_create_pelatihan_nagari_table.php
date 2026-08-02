<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Sasaran audiens satu PELAKSANAAN pelatihan (nagari mana yang disasar). Diabaikan
 * bila `pelatihans.semua_nagari` = true (sasaran dinamis ke seluruh nagari).
 *
 * Seluruh modul di dalam pelaksanaan mewarisi sasaran ini; modul tidak punya
 * sasaran sendiri.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pelatihan_nagari', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pelatihan_id')->constrained('pelatihans')->cascadeOnDelete();
            $table->foreignId('nagari_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['pelatihan_id', 'nagari_id']);
            $table->index('nagari_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pelatihan_nagari');
    }
};
