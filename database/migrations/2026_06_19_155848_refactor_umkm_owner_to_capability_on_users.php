<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Akses UMKM bukan persona terpisah, melainkan kapabilitas tambahan di atas
     * peran `warga`. Drop nilai role `umkm_owner`, ganti dengan kolom kapabilitas
     * `umkm_access_granted_at`. Role jadi string (set persona stabil, bukan enum
     * kaku yang butuh ALTER tiap kali nilai berubah).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('umkm_access_granted_at')->nullable()->after('role');
        });

        // Pemilik UMKM lama → warga + tandai punya akses UMKM.
        DB::table('users')
            ->where('role', 'umkm_owner')
            ->update(['umkm_access_granted_at' => now(), 'role' => 'warga']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 20)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['super_admin', 'nagari_admin', 'warga', 'umkm_owner'])->nullable()->change();
        });

        DB::table('users')
            ->whereNotNull('umkm_access_granted_at')
            ->update(['role' => 'umkm_owner']);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('umkm_access_granted_at');
        });
    }
};
