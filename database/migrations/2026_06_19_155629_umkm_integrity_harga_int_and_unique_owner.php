<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rupiah tak punya pecahan: simpan sebagai integer rupiah, bukan decimal.
        // Bulatkan nilai lama agar aman saat tipe berubah.
        DB::table('umkm_products')->update(['harga' => DB::raw('ROUND(harga)')]);

        Schema::table('umkm_products', function (Blueprint $table) {
            $table->unsignedBigInteger('harga')->nullable()->change();
        });

        // 1 warga = 1 lapak (sesuai relasi HasOne). Tegakkan di DB.
        Schema::table('umkm_profiles', function (Blueprint $table) {
            $table->unique('user_id');
        });
    }

    public function down(): void
    {
        Schema::table('umkm_profiles', function (Blueprint $table) {
            $table->dropUnique(['user_id']);
        });

        Schema::table('umkm_products', function (Blueprint $table) {
            $table->decimal('harga', 12, 2)->nullable()->change();
        });
    }
};
