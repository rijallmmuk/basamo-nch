<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Buang kata "webinar" dari nama tabel.
 *
 * Tidak ada dua jenis pelatihan. Yang ada hanya pelatihan, dan sebagiannya kebetulan
 * diisi pemateri lewat pertemuan daring. Nama `webinar_attendances` menanamkan
 * pemisahan yang tidak pernah ada, jadi ia diganti alih-alih dibiarkan.
 *
 * Lewat migrasi rename tersendiri, bukan dengan menyunting migrasi pembuatnya, supaya
 * aman baik pada lingkungan yang sudah menjalankan migrasi itu maupun yang belum.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('webinar_attendances') && ! Schema::hasTable('pelatihan_attendances')) {
            Schema::rename('webinar_attendances', 'pelatihan_attendances');
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pelatihan_attendances') && ! Schema::hasTable('webinar_attendances')) {
            Schema::rename('pelatihan_attendances', 'webinar_attendances');
        }
    }
};
