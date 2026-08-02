<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** 18 Poin (goal) SDGs Desa, masing-masing di bawah satu pilar. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sdg_goals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sdg_pillar_id')->constrained('sdg_pillars')->cascadeOnDelete();
            $table->unsignedTinyInteger('nomor')->unique();  // 1..18
            $table->string('nama', 150);
            $table->string('slug', 160)->unique();
            $table->text('deskripsi')->nullable();
            $table->string('warna', 7)->nullable();
            $table->string('ikon', 100)->nullable();         // path aset ikon resmi SDGs
            $table->timestamps();

            $table->index('sdg_pillar_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sdg_goals');
    }
};
