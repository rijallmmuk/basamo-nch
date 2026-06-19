<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Alamat warga: unit wilayah (jorong/dusun/…) dalam nagarinya. Opsional.
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('wilayah_id')->nullable()->after('nagari_id')
                ->constrained('wilayah')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['wilayah_id']);
            $table->dropColumn('wilayah_id');
        });
    }
};
