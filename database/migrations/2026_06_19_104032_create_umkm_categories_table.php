<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Taksonomi UMKM global (dikelola, bukan hardcode) — di-seed langsung di migrasi. */
    private const CATEGORIES = [
        ['slug' => 'kuliner', 'nama' => 'Kuliner', 'icon' => 'heroicon-o-cake'],
        ['slug' => 'kerajinan', 'nama' => 'Kerajinan', 'icon' => 'heroicon-o-sparkles'],
        ['slug' => 'fashion', 'nama' => 'Fashion & Tekstil', 'icon' => 'heroicon-o-swatch'],
        ['slug' => 'pertanian', 'nama' => 'Pertanian & Perkebunan', 'icon' => 'heroicon-o-sun'],
        ['slug' => 'peternakan', 'nama' => 'Peternakan & Perikanan', 'icon' => 'heroicon-o-beaker'],
        ['slug' => 'jasa', 'nama' => 'Jasa', 'icon' => 'heroicon-o-briefcase'],
        ['slug' => 'lainnya', 'nama' => 'Lainnya', 'icon' => 'heroicon-o-ellipsis-horizontal-circle'],
    ];

    public function up(): void
    {
        Schema::create('umkm_categories', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->string('slug', 100)->unique();
            $table->string('icon', 60)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        foreach (self::CATEGORIES as $i => $cat) {
            DB::table('umkm_categories')->insert([
                ...$cat,
                'sort_order' => $i + 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('umkm_categories');
    }
};
