<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Antrean berbasis database (QUEUE_CONNECTION=database). Produksi WAJIB
 * menjalankan worker; tanpa itu notifikasi modul/kuis dan impor warga menggantung.
 *
 * job_batches (Bus::batch) sengaja TIDAK dibuat — tak ada pemakaian batching;
 * buat lagi lewat migrasi baru bila suatu saat dibutuhkan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedSmallInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};
