<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Taksonomi UMKM global (dikelola, bukan hardcode). */
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

        Schema::table('umkm_profiles', function (Blueprint $table) {
            $table->foreignId('umkm_category_id')->nullable()->after('user_id')
                ->constrained('umkm_categories')->nullOnDelete();
        });

        // Petakan nilai kategori lama (mis. "Kuliner") ke kategori via slug.
        // Subquery berkorelasi → portabel (MySQL & SQLite).
        DB::statement('
            UPDATE umkm_profiles
            SET umkm_category_id = (
                SELECT id FROM umkm_categories WHERE slug = LOWER(umkm_profiles.kategori)
            )
        ');

        Schema::table('umkm_profiles', function (Blueprint $table) {
            $table->dropIndex('umkm_profiles_kategori_index');
            $table->dropColumn('kategori');
        });
    }

    public function down(): void
    {
        Schema::table('umkm_profiles', function (Blueprint $table) {
            $table->string('kategori', 100)->default('Lainnya')->after('user_id');
            $table->index('kategori');
        });

        DB::statement('
            UPDATE umkm_profiles
            SET kategori = (
                SELECT nama FROM umkm_categories WHERE id = umkm_profiles.umkm_category_id
            )
        ');

        Schema::table('umkm_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('umkm_category_id');
        });

        Schema::dropIfExists('umkm_categories');
    }
};
