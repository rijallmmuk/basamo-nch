<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Konsistensi konvensi: semua tabel plural (nagaris, modules, …). `wilayah`
 * → `wilayahs`. FK users.wilayah_id mengikuti otomatis (InnoDB & SQLite ≥3.26).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('wilayah', 'wilayahs');
    }

    public function down(): void
    {
        Schema::rename('wilayahs', 'wilayah');
    }
};
