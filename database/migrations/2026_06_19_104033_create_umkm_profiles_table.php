<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('umkm_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nagari_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete(); // 1 warga = 1 lapak
            $table->foreignId('umkm_category_id')->nullable()->constrained('umkm_categories')->nullOnDelete();
            $table->string('nama_usaha');
            $table->string('slug')->unique();
            $table->text('deskripsi')->nullable();
            $table->text('alamat')->nullable();
            $table->string('whatsapp', 20);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->index('nagari_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('umkm_profiles');
    }
};
