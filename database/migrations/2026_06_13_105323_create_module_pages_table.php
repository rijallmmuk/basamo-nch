<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('module_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->enum('type', ['text', 'pdf', 'video']);
            $table->longText('content')->nullable();
            $table->string('video_url', 500)->nullable();
            $table->string('file_path', 500)->nullable();
            $table->unsignedSmallInteger('order')->default(0);
            $table->timestamps();

            $table->index('module_id');
            $table->index('order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('module_pages');
    }
};
