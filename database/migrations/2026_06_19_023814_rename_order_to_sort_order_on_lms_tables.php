<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    /** Tabel LMS yang punya kolom urutan. */
    private array $tables = ['modules', 'module_pages', 'quiz_questions', 'quiz_options'];

    public function up(): void
    {
        // `order` adalah kata kunci SQL (perlu backtick terus-menerus). Pakai `sort_order`.
        foreach ($this->tables as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->renameColumn('order', 'sort_order');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        foreach ($this->tables as $name) {
            Schema::table($name, function (Blueprint $table) {
                $table->renameColumn('sort_order', 'order');
            });
        }
    }
};
